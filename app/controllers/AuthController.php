<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\UserProfile;

class AuthController extends \Lava\Routing\Controller
{
    public function register()
    {
        $this->validate([
            'email' => 'required|email|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:student,faculty,staff,admin',
        ]);

        try {
            $user = User::create([
                'email' => request()->get('email'),
                'username' => request()->get('username'),
                'password' => bcrypt(request()->get('password')),
                'role' => request()->get('role'),
                'is_active' => 1,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'violations_count' => 0,
                'is_suspended' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function login()
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $user = User::where('email', request()->get('email'))->first();

            if (!$user || !verify_password(request()->get('password'), $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                ], 401);
            }

            if (!$user->is_active) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Account is inactive',
                ], 403);
            }

            $token = $this->generateToken($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function logout()
    {
        // Invalidate token logic here
        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully',
        ], 200);
    }

    private function generateToken($user)
    {
        $payload = [
            'iss' => env('APP_URL'),
            'sub' => $user->id,
            'iat' => time(),
            'exp' => time() + (7 * 24 * 60 * 60),
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => $user->role,
            ],
        ];

        return jwt_encode($payload, env('JWT_SECRET'));
    }

    public function refreshToken()
    {
        try {
            $user = auth()->user();
            $token = $this->generateToken($user);

            return response()->json([
                'status' => 'success',
                'data' => ['token' => $token],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token refresh failed',
            ], 401);
        }
    }
}
