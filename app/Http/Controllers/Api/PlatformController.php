<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Http\Resources\PlatformsResource;
use App\Models\Platform;
use Illuminate\Http\Request;

/**
 * @group Platform Management
 *
 * APIs for managing the authenticated user's gaming platform accounts.
 */
class PlatformController extends BaseController
{
    /**
     * List user's platforms
     *
     * Returns all gaming platforms linked to the authenticated user.
     *
     * @authenticated
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "all platforms",
     *   "data": {
     *     "user_id": 1,
     *     "platforms": [
     *       {
     *         "id": 1,
     *         "nickname": "GamerX",
     *         "platform": "xbox"
     *       }
     *     ]
     *   }
     * }
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('platforms');

        $data = new PlatformsResource($user);

        return $this->sendResponse($data ,'all platforms' , 200);
    }

    /**
     * Update a platform
     *
     * Updates an existing platform entry for the authenticated user.
     *
     * @authenticated
     *
     *
     * @bodyParam platform string required The platform type. Must be one of: xbox, pc, ps, mobile. Example: xbox
     * @bodyParam nickname string required The in-game nickname (3–50 characters). Example: GamerX
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Platform updated successfully",
     *   "data": {
     *     "user_id": 1,
     *     "platforms": [
     *       {
     *         "id": 1,
     *         "nickname": "GamerX",
     *         "platform": "xbox"
     *       }
     *     ]
     *   }
     * }
     */
    public function update(UpdatePlatformRequest $request, Platform $platform)
    {
        $platform->update($request->validated());
        $user = $request->user()->load('platforms');
        $data = new PlatformsResource($user);
        return $this->sendResponse($data, 'Platform updated successfully' ,200);
    }

    /**
     * Create a platform
     *
     * Adds a new gaming platform entry for the authenticated user.
     *
     * @authenticated
     *
     * @bodyParam platform string required The platform type. Must be one of: xbox, pc, ps, mobile. Example: xbox
     * @bodyParam nickname string required The in-game nickname (max 50 characters). Example: GamerX
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "platform created successfully!",
     *   "data": {
     *     "user_id": 1,
     *     "platforms": [
     *       {
     *         "id": 1,
     *         "nickname": "GamerX",
     *         "platform": "xbox"
     *       }
     *     ]
     *   }
     * }
     */
    public function store(StorePlatformRequest $request)
    {
        $user = $request->user();

        $user->platforms()->create($request->validated());

        $data = new PlatformsResource($user->load('platforms'));

        return $this->sendResponse($data , 'platform created successfully!' , 201);
    }


    /**
     * Delete a platform
     *
     * Removes a gaming platform entry for the authenticated user.
     *
     * @authenticated
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "deleted successfully!",
     *   "data": []
     * }
     */
    public function destroy(Request $request , Platform $platform)
    {
        $platform->delete();

        return $this->sendResponse([], 'deleted successfully!' ,204);
    }
}
