<?php

namespace App\Http\Controllers\Actions;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

class PlanController extends Controller
{
    use AuthorizesRequests;
    /**
     * Store a newly created resource in storage.
     */
    public function store(array $request): Plan
    {
        $this->authorize('create' , Plan::class);

        return Plan::create($request);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(array $request, Plan $plan): Plan
    {
        $this->authorize('update' , Plan::class);

        $plan->update($request);

        return $plan->fresh();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plan $plan): void
    {
        $this->authorize('delete' , Plan::class);

        $plan->delete();
    }
}
