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

        // HIGH PRIORITY FIX #18: UserProfile creation transaction
        $transactionModel = new User();
        $transactionModel->_transaction();
        try {
            $userId = User::insert([
                'email' => $email,
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'student',
                'is_active' => 1,
            ]);

            $profile = UserProfile::insert([
                'user_id' => $userId,
                'violations_count' => 0,
                'is_suspended' => 0,
            ]);

            if (!$profile) {
                throw new \Exception('Failed to create user profile');
            }

            $user = User::find($userId);
            $transactionModel->_commit();

            return $this->response_json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            $transactionModel->_rollback();
            error_log('Registration transaction failed: ' . $e->getMessage());
            return $this->response_json([
                'status' => 'error',
                'message' => 'Registration failed. Please try again.',
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

    public function forgotPassword()
    {
        $this->populate_json_input();
        $fv = load_class('Form_validation', 'libraries');
        $fv->name('email')->required()->valid_email();

        if (!empty($fv->errors)) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $fv->errors,
            ], 400);
        }

        $email = $this->request->post('email');

        try {
            $user = User::find_by('email', $email);
            
            if ($user) {
                // HIGH PRIORITY FIX #12: Password reset functionality
                $token = bin2hex(random_bytes(32));
                $tokenHash = hash('sha256', $token);
                
                // Delete existing tokens
                PasswordReset::where('user_id', $user['id'])->delete();
                
                // Create new password reset token
                PasswordReset::insert([
                    'user_id' => $user['id'],
                    'token' => $tokenHash,
                    'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                ]);

                // TODO: Send email with reset token
                // For now, log the token for testing
                error_log("Password reset token for {$email}: {$token}");
            }

            // Always return 200 to avoid email enumeration
            return $this->response_json([
                'status' => 'success',
                'message' => 'If an account exists for this email, a password reset link will be sent.',
            ], 200);
        } catch (\Exception $e) {
            error_log('Forgot password error: ' . $e->getMessage());
            return $this->response_json([
                'status' => 'success',
                'message' => 'If an account exists for this email, a password reset link will be sent.',
            ], 200);
        }
    }

    public function resetPassword()
    {
        $this->populate_json_input();
        $fv = load_class('Form_validation', 'libraries');
        $fv->name('token')->required();
        $fv->name('password')->required()->min_length(8);

        if (!empty($fv->errors)) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $fv->errors,
            ], 400);
        }

        $token = $this->request->post('token');
        $password = $this->request->post('password');

        try {
            // HIGH PRIORITY FIX #12: Password reset functionality
            $tokenHash = hash('sha256', $token);
            $reset = PasswordReset::isTokenValid($tokenHash);

            if (!$reset) {
                return $this->response_json([
                    'status' => 'error',
                    'message' => 'Invalid or expired password reset token',
                ], 400);
            }

            $transactionModel = new User();
            $transactionModel->_transaction();
            try {
                User::where('id', $reset['user_id'])->update([
                    'password' => password_hash($password, PASSWORD_DEFAULT),
                ]);

                PasswordReset::where('id', $reset['id'])->delete();
                $transactionModel->_commit();

                return $this->response_json([
                    'status' => 'success',
                    'message' => 'Password reset successfully. Please log in.',
                ], 200);
            } catch (\Exception $e) {
                $transactionModel->_rollback();
                throw $e;
            }
        } catch (\Exception $e) {
            error_log('Reset password error: ' . $e->getMessage());
            return $this->response_json([
                'status' => 'error',
                'message' => 'Password reset failed',
            ], 400);
        }
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

        try {
            $tokens = $this->api->refresh_access_token($refreshToken);
            if (!$tokens) {
                return $this->response_json([
                    'status' => 'error',
                    'message' => 'Token refresh failed',
                ], 401);
            }

            return $this->response_json([
                'status' => 'success',
                'data' => [
                    'access_token' => $tokens['access_token'],
                    'refresh_token' => $tokens['refresh_token'],
                    'expires_in' => $tokens['expires_in'],
                ],
            ], 200);
        } catch (\Exception $e) {
            return $this->response_json([
                'status' => 'error',
                'message' => 'Token refresh failed',
            ], 401);
        }
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

    private function checkRateLimit($type, $identifier = null)
    {
        $cache = load_class('Cache', 'libraries');
        $cacheKey = $type . '_' . ($identifier ?? $_SERVER['REMOTE_ADDR']);
        
        $attempts = $cache->get($cacheKey) ?? 0;
        
        if ($attempts >= 5) {
            return false;
        }

        $cache->set($cacheKey, $attempts + 1, 900);
        return true;
    }

    private function clearRateLimit($type, $identifier = null)
    {
        $cache = load_class('Cache', 'libraries');
        $cacheKey = $type . '_' . ($identifier ?? $_SERVER['REMOTE_ADDR']);
        $cache->delete($cacheKey);
    }
}
