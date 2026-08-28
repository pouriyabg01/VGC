<?php
use App\Livewire\Checkout;
use App\Livewire\Landing;
use App\Livewire\Plans;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\Register;
use App\Livewire\Profile\Index;
use Illuminate\Support\Facades\Route;

Route::get('/', Landing::class)->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/register', Register::class)->name('register');
});

Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->middleware('auth')->name('logout');

Route::get('profile' , Index::class)->middleware('auth')->name('profile');

Route::get('plans', Plans::class)->name('plans');

// Public: a guest can read the plan; only confirming requires an account.
Route::get('checkout/{plan}', Checkout::class)->name('checkout');

Route::get('tournament/{tournament}', \App\Livewire\Tournament::class)->name('tournament');
