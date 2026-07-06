<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function execute(array $request)
    {
        $request['password'] = Hash::make($request['password']);

        $user = User::create($request);

        event(new Registered($user));

        Auth::login($user);

        return $user;
    }
}
