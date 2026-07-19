<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @group User Management
 *
 * APIs for registering, logging in, and logging out users.
 */
class AuthController extends BaseController
{
    /**
     * Register a new user
     *
     * Creates a new user account and returns an API token.
     *
     * @unauthenticated
     *
     * @bodyParam name string required The user's display name. Example: John Doe
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password (minimum 8 characters). Example: secret123
     * @bodyParam password_confirmation string required Must match the password field. Example: secret123
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "user registered successfully",
     *   "data": {
     *     "id": 1,
     *     "token_type": "Bearer",
     *     "token": "1|abcdef123456",
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2026-07-14T00:00:00.000000Z",
     *     "updated_at": "2026-07-14T00:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Validation error" {
     *   "message": "The email has already been taken.",
     *   "errors": {
     *     "email": ["The email has already been taken."]
     *   }
     * }
     */
    public function register(AuthRequest $request)
    {
        $request['password'] = Hash::make($request['password']);

        $user = User::create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        $token = $user->createToken('api')->plainTextToken;

        $user->token_type = 'Bearer';
        $user->token = $token;

        return $this->sendResponse(new UserResource($user) , 'user registered successfully');
    }

    /**
     * Log in
     *
     * Authenticates a user and returns a new API token. Previous tokens are revoked.
     *
     * @unauthenticated
     *
     * @bodyParam email string required The user's email address. Example: john@example.com
     * @bodyParam password string required The user's password. Example: secret123
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Logged in successfully",
     *   "data": {
     *     "id": 1,
     *     "token_type": "Bearer",
     *     "token": "2|abcdef123456",
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "created_at": "2026-07-14T00:00:00.000000Z",
     *     "updated_at": "2026-07-14T00:00:00.000000Z"
     *   }
     * }
     * @response 422 scenario="Invalid credentials" {
     *   "message": "These credentials do not match our records.",
     *   "errors": {
     *     "email": ["These credentials do not match our records."]
     *   }
     * }
     */
    public function login(LoginRequest $request)
    {
        if (! Auth::attempt($request->validated())){
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $user = Auth::user();

        $user->tokens()->delete();
        $user->token = $user->createToken('api')->plainTextToken;
        $user->token_type = 'Bearer';

        return $this->sendResponse(new UserResource($user) , 'Logged in successfully');
    }

    /**
     * Log out
     *
     * Revokes the current user's API token.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Logged out",
     *   "data": ""
     * }
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse('' , 'Logged out');
    }


}
