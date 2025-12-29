<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StorePlatformRequest;
use App\Http\Requests\UpdatePlatformRequest;
use App\Http\Resources\PlatformsResource;
use App\Models\Platform;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

/**
 * @group Platform management
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
//    /**
//     * Display the specified user's platforms.
//     *
//     * @param User $user
//     *
//     * @return \Illuminate\Http\JsonResponse
//     */
//    public function show(User $user)
//    {


    /**
     * Remove platform.
     * @authenticated
     */
    public function destroy(Request $request , $id)
    {
        try {
            $user = $request->user();

            $platform = $user->platforms()->findOrFail($id);

            $platform->delete();

            $user->load('platforms');
            $data = new PlatformsResource($user);
            return $this->sendResponse($data, 'all platforms');

        }catch (ModelNotFoundException $e){
            return $this->sendError('not found' , []);
        }

    }
}
