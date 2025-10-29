<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RegistrationController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Show the registration form
     */
    public function show(): Response
    {
        return Inertia::render('Welcome');
    }

    /**
     * Handle registration form submission
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:male,female,other',
            'password' => 'required|string|min:6|confirmed',
        ]);

        // Prepare data for user-service
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'gender' => $request->gender,
            'password' => $request->password,
            'password_confirmation' => $request->password_confirmation,
        ];

        // Call user-service to register the userr
        $result = $this->userService->register($userData);

        if ($result['success']) {
            // Registration successful - redirect back with success message
            return redirect()->back()->with([
                'success' => true,
                'message' => 'Registration successful! Welcome aboard!',
                'user' => $result['data']['data']['user'] ?? null,
                'token' => $result['data']['data']['token'] ?? null,
            ]);
        } else {
            // Registration failed - redirect back with errors
            return redirect()->back()->withErrors($result['errors'] ?? ['general' => 'Registration failed']);
        }
    }
}
