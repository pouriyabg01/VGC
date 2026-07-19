<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Models\Plan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

/**
 * @group Plan Management
 *
 * APIs for listing and managing subscription plans.
 */
class PlanController extends BaseController
{
    use AuthorizesRequests;

    /**
     * List all plans
     *
     * Returns every available subscription plan.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "all plans",
     *   "data": [
     *     {
     *       "id": 1,
     *       "title": "Basic",
     *       "description": "Monthly basic plan",
     *       "price": 1000
     *     }
     *   ]
     * }
     */
    public function index()
    {
        return $this->sendResponse(PlanResource::collection(Plan::all()),'all plans');
    }

    /**
     * Show a plan
     *
     * Returns details of a specific subscription plan.
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "plan",
     *   "data": {
     *     "id": 1,
     *     "title": "Basic",
     *     "description": "Monthly basic plan",
     *     "price": 1000
     *   }
     * }
     */
    public function show(Plan $plan)
    {
        return $this->sendResponse(new PlanResource($plan) , 'plan');
    }

    /**
     * Create a plan
     *
     * Creates a new subscription plan. Admin only.
     *
     * @authenticated
     *
     * @bodyParam title string required The plan title. Example: Pro
     * @bodyParam description string required The plan description. Example: Full access plan
     * @bodyParam price integer required The plan price in the smallest currency unit. Example: 2500
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "plan successfully created",
     *   "data": {
     *     "id": 2,
     *     "title": "Pro",
     *     "description": "Full access plan",
     *     "price": 2500
     *   }
     * }
     */
    public function store(PlanRequest $request)
    {
        $this->authorize('create' , Plan::class);

        $plan = Plan::create($request->validated());

        return $this->sendResponse(new PlanResource($plan) , 'plan successfully created' , 201);
    }

    /**
     * Update a plan
     *
     * Updates an existing subscription plan. Admin only.
     *
     * @authenticated
     *
     *
     * @bodyParam title string required The plan title. Example: Pro Plus
     * @bodyParam description string required The plan description. Example: Updated full access plan
     * @bodyParam price integer required The plan price. Example: 3000
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "plan successfully updated",
     *   "data": {
     *     "id": 1,
     *     "title": "Pro Plus",
     *     "description": "Updated full access plan",
     *     "price": 3000
     *   }
     * }
     */
    public function update(PlanRequest $request , Plan $plan)
    {
        $this->authorize('update' , Plan::class);

        $plan = $plan->update($request->validated());

        return $this->sendResponse(new PlanResource($plan),'plan successfully updated');
    }

    /**
     * Delete a plan
     *
     * Permanently removes a subscription plan. Admin only.
     *
     * @authenticated
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "plan successfully deleted",
     *   "data": []
     * }
     */
    public function destroy(Plan $plan)
    {
        $this->authorize('delete' , Plan::class);
        $plan->delete();
        return $this->sendResponse([],'plan successfully deleted');
    }
}
