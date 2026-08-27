<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

/**
 * @group Subscription Management
 *
 * APIs for subscribing to plans and viewing the current subscription.
 */
class SubscriptionController extends BaseController
{
    /**
     * Subscribe to a plan
     *
     * Activates a subscription for the authenticated user. Only one active subscription is allowed at a time.
     *
     * @authenticated
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "you successfully get a subscription",
     *   "data": {
     *     "sub_id": 1,
     *     "user_id": 1,
     *     "plan_id": 1,
     *     "plan_title": "Basic",
     *     "status": true,
     *     "started_at": "2026-07-14T00:00:00.000000Z"
     *   }
     * }
     * @response 404 scenario="Already subscribed" {
     *   "success": false,
     *   "message": "you already have subscription!"
     * }
     */
    public function store(Request $request,Plan $plan)
    {
        $user = $request->user();

        if (app(SubscriptionService::class)->activeFor($user)){
            return $this->sendError('you already have subscription!');
        }

        return $this->sendResponse(
            new SubscriptionResource(app(SubscriptionService::class)->subscribe($user, $plan)),
            'you successfully get a subscription',
            200
        );
    }


    /**
     * Show current subscription
     *
     * Returns the authenticated user's active subscription, or an empty response if none exists.
     *
     * @authenticated
     *
     * @response 200 scenario="Active subscription" {
     *   "success": true,
     *   "message": "user's subscription",
     *   "data": {
     *     "sub_id": 1,
     *     "user_id": 1,
     *     "plan_id": 1,
     *     "plan_title": "Basic",
     *     "status": true,
     *     "started_at": "2026-07-14T00:00:00.000000Z"
     *   }
     * }
     * @response 404 scenario="No subscription" {
     *   "success": false,
     *   "message": "have no subscription"
     * }
     */
    public function show(Request $request)
    {
        $subscription = app(SubscriptionService::class)->activeFor($request->user());

        if (! $subscription) {
            return $this->sendError('have no subscription', [], 404);
        }

        return $this->sendResponse(
            new SubscriptionResource($subscription),
            "user's subscription",
            200
        );
    }


}
