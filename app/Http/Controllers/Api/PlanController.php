<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\PlanRequest;
use App\Http\Resources\PlanResource;
use App\Http\Controllers\Actions\PlanController as PlanAction;
use App\Models\Plan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;

/**
 * @group Plan Management
 */
class PlanController extends BaseController
{
    use AuthorizesRequests;

    public function index()
    {
        return $this->sendResponse(PlanResource::collection(Plan::all()),'all plans');
    }

    /**
     * show plan
     * @urlParam id required the id of Plan
     * @param Plan $plan
     */
    public function show(Plan $plan)
    {
        return $this->sendResponse(new PlanResource($plan) , 'plan');
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
    public function store(PlanRequest $request , PlanAction $action)
    {
        $plan = $action->store($request->validated());

        return $this->sendResponse(new PlanResource($plan) , 'plan successfully created' , 201);
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
    public function update(PlanRequest $request , Plan $plan , PlanAction $action)
    {
        $plan = $action->update($request->validated() , $plan);

        return $this->sendResponse(new PlanResource($plan),'plan successfully updated');
    }

    /**
     * delete plan
     * @urlParam id required the id of plan
     * @param Plan $plan
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Plan $plan , PlanAction $action)
    {
        $action->destroy($plan);
        return $this->sendResponse([],'plan successfully deleted');
    }
}
