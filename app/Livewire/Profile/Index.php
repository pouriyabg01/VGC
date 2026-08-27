<?php

namespace App\Livewire\Profile;

use App\Livewire\Forms\PlatformForm;
use App\Models\Plan;
use App\Models\Platform;
use App\Services\SubscriptionService;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    // Properties for Platform Management
    public PlatformForm $form;

    public function addPlatform()
    {
        $this->form->store();
    }

    public function removePlatform($id)
    {
        Platform::find($id)->delete();
    }

    /**
     * The pass the user currently holds, as a Plan carrying its pivot row, or
     * null. Same source the header icon and checkout use.
     */
    public function activeSubscription(): ?Plan
    {
        return app(SubscriptionService::class)->activeFor(Auth::user());
    }

    public function render()
    {
        return view('livewire.profile.index', [
            // Load both tournaments and platforms to keep it fast
            'user' => Auth::user()->load(['tournaments', 'platforms'])
        ])->layout('components.layouts.app');
    }
}
