<?php

namespace App\Livewire\Auth;

use App\Http\Controllers\Actions\RegisterController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Http\Requests\AuthRequest;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(RegisterController $action)
    {
        $validated = $this->validate((new AuthRequest())->rules());

        $action->execute($validated);

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.register')
            ->layout('components.layouts.guest');
    }
}
