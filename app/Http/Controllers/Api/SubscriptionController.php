<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\SubscriptionResource;
use App\Models\Plan;
use App\Models\User;
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
        $latest = $this->latestSubscription($user);

        if ($latest && $latest->pivot->status){
            return $this->sendError('you already have subscription!');
        }

        $user->plan()->attach($plan , ['status' => true]);

        return $this->sendResponse(
            new SubscriptionResource($this->latestSubscription($user)),
            'you successfully get a subscription',
            200
        );
    }

    /**
     * The user's most recent subscription, as a Plan carrying its pivot row.
     *
     * SubscriptionResource reads $this->pivot, so the subscription has to be
     * resolved through the plan() belongsToMany. The Subscription model has no
     * pivot and no title, and $user->latest_active_sub resolves to null because
     * the matching accessor is commented out on User.
     */
    private function latestSubscription(User $user): ?Plan
    {
        return $user->plan()
            ->orderByPivot('created_at', 'desc')
            ->first();
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
     * @response 401 scenario="No subscription" {
     *   "success": true,
     *   "message": "have no subscription",
     *   "data": []
     * }
     */
    public function show(Request $request)
    {
        $subscription = $this->latestSubscription($request->user());

        if (! $subscription || ! $subscription->pivot->status) {
            return $this->sendResponse([], 'have no subscription', 401);
        }

        return $this->sendResponse(
            new SubscriptionResource($subscription),
            "user's subscription",
            200
        );
    }


}
