<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function execute(array $credential ,bool $remember = false)
    {
        if (! Auth::attempt($credential , $remember)){
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }
        return Auth::user();
    }
}
