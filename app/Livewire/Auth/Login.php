<?php

namespace App\Livewire\Auth;

use App\Http\Controllers\Actions\LoginController;
use App\Http\Requests\LoginRequest;
use Livewire\Component;

class Login extends Component
{
    public string $email = '';
    public string $password = '';
    public bool $remember = false;


    public function authenticate(LoginController $action)
    {
        $validated = $this->validate((new LoginRequest())->rules());

        $action->execute($validated, $this->remember);

        request()->session()->regenerate();

        $this->redirect(route('home'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login')
            ->layout('components.layouts.guest');
    }
}
