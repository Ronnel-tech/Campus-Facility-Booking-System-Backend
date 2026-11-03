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
                'role' => 'student',
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

        $tokens = $this->api->issue_tokens([
            'id' => $user['id'],
            'role' => $user['role'],
        ]);

        return $this->response_json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $tokens['access_token'],
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => $tokens['expires_in'],
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

    public function refreshToken()
    {
        $this->populate_json_input();
        $refreshToken = $this->request->post('refresh_token');

        if (!is_string($refreshToken) || $refreshToken === '') {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Refresh token is required',
            ], 400);
        }

        $this->api->refresh_access_token($refreshToken);
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
            $input = json_decode(lavalust_raw_input(), true);
            if (is_array($input)) {
                $_POST = $input;
            }
        }
    }
}
