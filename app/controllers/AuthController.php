<?php


class AuthController extends \Controller
{
    public function register()
    {
        $this->populate_json_input();
        $fv = load_class('Form_validation', 'libraries');
        $fv->name('email')->required()->valid_email();
        $fv->name('username')->required()->min_length(3);
        $fv->name('password')->required()->min_length(8);
        $fv->name('role')->required()->in_list(['student', 'faculty', 'staff', 'admin']);

        if (!empty($fv->errors)) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $fv->errors,
            ], 400);
        }

        $email = $this->request->post('email');
        $username = $this->request->post('username');
        $password = $this->request->post('password');
        $role = $this->request->post('role');

        $existing = User::find_by('email', $email);
        if ($existing) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Email already registered',
            ], 400);
        }

        $existingUser = User::find_by('username', $username);
        if ($existingUser) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Username already taken',
            ], 400);
        }

        try {
            $userId = User::insert([
                'email' => $email,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => $role,
                'is_active' => 1,
            ]);

            UserProfile::insert([
                'user_id' => $userId,
                'violations_count' => 0,
                'is_suspended' => 0,
            ]);

            $user = User::find($userId);

            return $this->response_json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function login()
    {
        $this->populate_json_input();
        $fv = load_class('Form_validation', 'libraries');
        $fv->name('email')->required()->valid_email();
        $fv->name('password')->required();

        if (!empty($fv->errors)) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $fv->errors,
            ], 400);
        }

        $email = $this->request->post('email');
        $password = $this->request->post('password');

        $user = User::find_by('email', $email);

        if (!$user || !password_verify($password, $user['password'])) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (!$user['is_active']) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Account is inactive',
            ], 403);
        }

        $token = $this->generateToken($user);

        return $this->response_json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 200);
    }

    public function logout()
    {
        return $this->response_json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ], 200);
    }

    private function generateToken($user)
    {
        $payload = json_encode([
            'iss' => env('APP_URL'),
            'sub' => $user['id'],
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'],
            ],
        ]);

        $secret = env('JWT_SECRET');
        $signature = base64_encode(hash_hmac('sha256', $payload, $secret, true));
        return base64_encode($payload) . '.' . base64_encode($signature);
    }

    public function refreshToken()
    {
        try {
            $user = $this->getCurrentUser();
            if (!$user) {
                return $this->response_json([
                    'status' => 'error',
                    'message' => 'Invalid token',
                ], 401);
            }

            $token = $this->generateToken($user);

            return $this->response_json([
                'status' => 'success',
                'data' => ['token' => $token],
            ], 200);
        } catch (\Exception $e) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Token refresh failed',
            ], 401);
        }
    }

    private function getCurrentUser()
    {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s+(.*)/', $authHeader, $matches)) {
            $token = $matches[1];
            $parts = explode('.', $token);
            if (count($parts) !== 2) return null;

            $payload = json_decode(base64_decode($parts[0]), true);
            if (!$payload || !isset($payload['sub'])) return null;

            return User::find($payload['sub']);
        }
        return null;
    }

    private function response_json($data, $status = 200)
    {
        $response = load_class('Response', 'kernel');
        $response->set_status_code($status);
        $response->set_json_content($data);
        $response->send();
        exit;
    }

    private function populate_json_input()
    {
        if (empty($_POST) && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $input = $this->request->json(true);
            if (is_array($input)) {
                $_POST = $input;
            }
        }
    }
}
