<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Plans extends Component
{
    /**
     * The pass the viewer already holds, if any. Lets the card for that plan
     * say so instead of offering a purchase the API would refuse.
     */
    public function activeSubscription(): ?Plan
    {
        $user = Auth::user();

        return $user ? app(SubscriptionService::class)->activeFor($user) : null;
    }

    public function render()
    {
        return view('livewire.plans', [
            'plans' => Plan::query()->latest()->get(),
        ])->layout('components.layouts.app');
    }
}
