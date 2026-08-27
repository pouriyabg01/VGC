<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Checkout extends Component
{
    public Plan $plan;

    public function mount(Plan $plan): void
    {
        $this->plan = $plan;
    }

    /**
     * The subscription the viewer already holds, if any. Drives the notice that
     * replaces the confirm button, so nobody clicks into a refusal.
     */
    public function activeSubscription(): ?Plan
    {
        $user = Auth::user();

        return $user ? app(SubscriptionService::class)->activeFor($user) : null;
    }

    public function confirm(): void
    {
        $user = Auth::user();

        // The page is public so a guest can read the plan; only confirming
        // needs an account.
        if (! $user) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $subscriptions = app(SubscriptionService::class);

        if ($subscriptions->activeFor($user)) {
            $this->addError('confirm', 'You already have an active subscription.');

            return;
        }

        // TODO payment: take payment here before the subscription is activated.
        $subscriptions->subscribe($user, $this->plan);

        $this->redirectRoute('profile', navigate: true);
    }

    public function render()
    {
        return view('livewire.checkout')->layout('components.layouts.app');
    }
}
