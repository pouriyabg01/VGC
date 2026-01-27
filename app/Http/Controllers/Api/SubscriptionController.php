<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use Illuminate\Http\Request;

/**
 * @group Subscription Management
 */
class SubscriptionController extends BaseController
{
    /**
     * get a subscription
     * @urlParam id required the id of Plan
     * @param Request $request
     * @param Plan $plan
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request,Plan $plan)
    {
        $user = $request->user();
        $latest = $user->plan()->orderByPivot('created_at' , 'desc')->first();

        if ($latest && $latest->pivot->status === 1){
            return $this->sendError('you already have subscription!');
        }

        $user->plan()->attach($plan , ['status' => true]);


        return $this->sendResponse(new SubscriptionResource(
            $user->plan()->orderByPivot('created_at' , 'desc')->first()
        ) , 'you have successfully get a subscription');
    }

    /**
     * show user's subscription
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request)
    {
        if ($request->user()->plan()->orderByPivot('created_at' , 'desc')->first()->pivot->status) {
            return $this->sendResponse(new SubscriptionResource(
                $request->user()->plan()->orderByPivot('created_at', 'desc')->first()
            ),
                "user's subscription");
        }
        return $this->sendResponse([],'have not any subscription');
    }
}
