<?php

namespace App\Livewire\Plan;

use App\Http\Controllers\Actions\PlanController;
use App\Models\Plan;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public function delete(Plan $plan, PlanController $action)
    {
        $this->authorize('delete', Plan::class);

        $action->delete($plan);

        session()->flash('message', 'Plan successfully deleted.');
    }

    public function render()
    {
        return view('livewire.plan.index', [
            'plans' => Plan::paginate(10),
        ])->layout('components.layouts.app');
    }
}
