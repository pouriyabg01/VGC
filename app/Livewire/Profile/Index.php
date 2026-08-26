<?php

namespace App\Livewire\Profile;

use App\Livewire\Forms\PlatformForm;
use App\Models\Platform;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    // Properties for Platform Management
    public PlatformForm $form;

    public function addPlatform()
    {
        $this->form->store();
        session()->flash('message', 'Platform added successfully!');
    }

    public function removePlatform($id)
    {
        Platform::find($id)->delete();
        session()->flash('message', 'Platform deleted successfully!');
    }

    public function render()
    {
        return view('livewire.profile.index', [
            // Load both tournaments and platforms to keep it fast
            'user' => Auth::user()->load(['tournaments', 'platforms'])
        ])->layout('components.layouts.app');
    }
}
