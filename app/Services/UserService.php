<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserService
{
    protected $baseUrl;
    protected $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.user_service.url', 'http://127.0.0.1:8003');
        $this->timeout = config('services.user_service.timeout', 30);
    }

    /**
     * Register a new user
     */
    public function register(array $data)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/users/register', $data);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'User registered successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Registration failed',
                'errors' => $response->json()['errors'] ?? ['general' => 'Registration failed']
            ];

        } catch (\Exception $e) {
            Log::error('User registration failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Service unavailable. Please try again later.',
                'errors' => ['general' => 'Service unavailable']
            ];
        }
    }

    /**
     * Login user
     */
    public function login(array $credentials)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post($this->baseUrl . '/api/users/login', $credentials);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Login successful'
                ];
            }

            return [
                'success' => false,
                'message' => 'Login failed',
                'errors' => $response->json()['errors'] ?? ['general' => 'Invalid credentials']
            ];

        } catch (\Exception $e) {
            Log::error('User login failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Service unavailable. Please try again later.',
                'errors' => ['general' => 'Service unavailable']
            ];
        }
    }

    /**
     * Get user profile
     */
    public function getProfile(string $token)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->get($this->baseUrl . '/api/users/profile');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Profile retrieved successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retrieve profile',
                'errors' => ['general' => 'Failed to retrieve profile']
            ];

        } catch (\Exception $e) {
            Log::error('Get profile failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Service unavailable. Please try again later.',
                'errors' => ['general' => 'Service unavailable']
            ];
        }
    }

    /**
     * Logout user
     */
    public function logout(string $token)
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withHeaders(['Authorization' => 'Bearer ' . $token])
                ->post($this->baseUrl . '/api/users/logout');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Logout successful'
                ];
            }

            return [
                'success' => false,
                'message' => 'Logout failed',
                'errors' => ['general' => 'Logout failed']
            ];

        } catch (\Exception $e) {
            Log::error('User logout failed: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Service unavailable. Please try again later.',
                'errors' => ['general' => 'Service unavailable']
            ];
        }
    }
}
