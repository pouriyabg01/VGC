<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

/**
 * @group Plan Management
 */
class PlanController extends BaseController
{
    use AuthorizesRequests;

    /**
     * all plans
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function index()
    {
        return Plan::all();
    }

    /**
     * show plan
     * @urlParam id required the id of Plan
     * @param Plan $plan
     * @return Plan
     */
    public function show(Plan $plan)
    {
        return $plan;
    }

    /**
     * store plan
     *
     * @bodyParam title string required
     * @bodyParam description string required
     * @bodyParam price integer required
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create' , Plan::class);
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|integer'
        ]);

        $plan = Plan::create($data);

        return $this->sendResponse($plan , 'plan successfully created' , 201);
    }

    /**
     * update plan
     * @urlParam id required the id of plan
     * @bodyParam title string required
     * @bodyParam description string required
     * @bodyParam price integer required
     *
     * @param Request $request
     * @param Plan $plan
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request , Plan $plan)
    {
        $this->authorize('update' , Plan::class);
        $data = $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|integer'
        ]);

        $plan->update($data);

        return $this->sendResponse($plan,'plan successfully updated');
    }

    /**
     * delete plan
     * @urlParam id required the id of plan
     * @param Plan $plan
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Plan $plan)
    {
        $this->authorize('delete' , Plan::class);

        $plan->delete();

        return $this->sendResponse([],'plan successfully deleted');
    }
}
