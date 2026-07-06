<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Actions\LoginController;
use App\Http\Controllers\Actions\RegisterController;
use App\Http\Requests\AuthRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\BaseController;

/**
 * @group User Management
 *
 *
 */
class AuthController extends BaseController
{
    /**
     *  Create User
     *
     * @bodyParam name required string the user's name
     * @bodyParam email required string the user's email
     * @bodyParam password required string password
     * @bodyParam password_confirmation required string password confirmation
     *
     * @param AuthRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(AuthRequest $request , RegisterController $action)
    {

        $user = $action->execute($request->validated());

        $token = $user->createToken('api')->plainTextToken;

        $user->token_type = 'Bearer';
        $user->token = $token;

        return $this->sendResponse(new UserResource($user) , 'user registered successfully');
    }

    /**
     * User Login
     *
     * @bodyParam email required string the user's email
     * @bodyParam password required string password
     * @param AuthRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request , LoginController $action)
    {
        $user = $action->execute($request->validated() , );

        $user->tokens()->delete();
        $user->token = $user->createToken('api')->plainTextToken;
        $user->token_type = 'Bearer';

        return $this->sendResponse(new UserResource($user) , 'Logged in successfully');
    }

    /**
     * Log out
     * @authenticated
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->sendResponse('' , 'Logged out');
    }


}
