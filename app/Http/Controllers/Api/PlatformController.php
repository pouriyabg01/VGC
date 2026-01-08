<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Http\Resources\PlatformsResource;
use App\Models\Platform;
use Illuminate\Http\Request;

/**
 * @group Platform Management
 */
class PlatformController extends BaseController
{
    /**
     * logged-in user's platforms.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     *
     */
    public function index(Request $request)
    {
        $user = $request->user()->load('platforms');

        $data = new PlatformsResource($user);

        return $this->sendResponse($data ,'all platforms');
    }

    /**
     * Update platform.
     * @bodyParam platform enum:xbox,pc,ps,mobile required example:xbox
     * @bodyParam nickname string required
     *
     * @urlParam id integer required the specified platform id
     *
     * @param UpdatePlatformRequest $request
     * @param Platform $platform
     * @authenticated
     * @return \Illuminate\Http\JsonResponse
     *
     */
    public function update(UpdatePlatformRequest $request, Platform $platform)
    {
        $platform->update($request->validated());
        $user = $request->user()->load('platforms');
        $data = new PlatformsResource($user);
        return $this->sendResponse($data, 'Platform updated successfully');
    }

    /**
     * store platform
     * @bodyParam platform enum:xbox,pc,ps,mobile required Example:xbox
     * @bodyParam nickname string required
     * @authenticated
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StorePlatformRequest $request)
    {
        $user = $request->user();

        $user->platforms()->create($request->validated());

        $data = new PlatformsResource($user->load('platforms'));

        return $this->sendResponse($data , 'platform created successfully!' , 201);
    }


    /**
     * Remove platform
     * @urlParam platformid integer
     * @param Request $request
     * @param Platform $platform
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request , Platform $platform)
    {
        $platform->delete();

        return $this->sendResponse([], 'deleted successfully!');
    }
}
