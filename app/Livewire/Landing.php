<?php

namespace App\Livewire;

use App\Models\Plan;
use App\Models\Tournament;
use Livewire\Component;

class Landing extends Component
{
    public function render()
    {
        return view('livewire.landing', [
            'plans' => Plan::query()
                ->latest()
                ->get(),
            'tournaments' => Tournament::query()
                ->withCount(['players', 'matches'])
                ->with('winner')
                ->latest()
                ->get(),
        ])->layout('components.layouts.app');
    }
}
