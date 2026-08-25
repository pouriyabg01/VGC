<?php

namespace App\Http\Controllers\Api;

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Http\Requests\TournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use App\Services\TournamentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Tournament Management
 *
 * APIs for creating, updating, viewing, and signing up for tournaments.
 */
class TournamentController extends BaseController
{
    use AuthorizesRequests;

    /**
     * Create a tournament
     *
     * Creates a new tournament.
     *
     * @authenticated
     *
     * @bodyParam platform string required The gaming platform. Must be one of: PC, PLAYSTATION, XBOX, MOBILE. Example: PC
     * @bodyParam game string required The game title (max 40 characters). Example: FIFA 24
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Tournament created successfully",
     *   "data": {
     *     "id": 1,
     *     "platform": "PC",
     *     "game": "FIFA 24",
     *     "end_at": null,
     *     "status": "PENDING",
     *     "winner_id": null
     *   }
     * }
     * @response 403 scenario="Unauthorized" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function store(TournamentRequest $request)
    {
        $this->authorize('create',Tournament::class);

        $tournament = Tournament::create($request->validated());

        return $this->sendResponse(new TournamentResource($tournament) , 'Tournament created successfully' ,201);

    }

    /**
     * Update a tournament
     *
     * Updates tournament details. Only the tournament owner can perform this action.
     *
     * @authenticated
     *
     *
     * @bodyParam platform string required The gaming platform. Must be one of: PC, PLAYSTATION, XBOX, MOBILE. Example: XBOX
     * @bodyParam game string required The game title. Example: FIFA 24
     * @bodyParam status string required Tournament status. Must be one of: PENDING, CANCELED, COMPLETED, GAMING. Example: GAMING
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "successfully updated",
     *   "data": {
     *     "id": 1,
     *     "platform": "Xbox",
     *     "game": "FIFA 24",
     *     "status": "GAMING"
     *   }
     * }
     */
    public function update(Request $request , Tournament $tournament)
    {
        $this->authorize('update',$tournament);

        $data = $request->validate([
            'platform' => ['required', 'string', new Enum(PlatformEnum::class)],
            'game' => 'required|string',
            'status' => ['required' , new Enum(TournamentEnum::class)]
        ]);

        $tournament->update($data);
        return $this->sendResponse($tournament,'successfully updated' ,200);
    }

//    /**
//     * complete tournament
//     *
//     * @param Request $request
//     * @param Tournament $tournament
//     * @return \Illuminate\Http\JsonResponse
//     * @authenticated
//     */
//    public function complete(Request $request , Tournament $tournament)
//    {
//        $request->validate([
//            'winner_id' => 'required|exists:users,id'
//        ]);
//        if ($tournament->status === TournamentEnum::COMPLETED){
//            return $this->sendError('tournament already completed' , new TournamentResource($tournament) ,422);
//        }
//
//        $tournament->update([
//            'status' => TournamentEnum::COMPLETED,
//            'winner_id' => $request->winner_id,
//            'end_at' => Carbon::now()
//        ]);
//
//        return $this->sendResponse(new TournamentResource($tournament) , 'tournament completed');
//    }

    /**
     * Show a tournament
     *
     * Returns details of a specific tournament.
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "specified tournament",
     *   "data": {
     *     "id": 1,
     *     "platform": "PC",
     *     "game": "FIFA 24",
     *     "end_at": null,
     *     "status": "PENDING",
     *     "winner_id": null
     *   }
     * }
     */
    public function show(Tournament $tournament)
    {
        return $this->sendResponse(new TournamentResource($tournament) , 'specified tournament' , 200);
    }

    /**
     * Delete a tournament
     *
     * Deletes a tournament. Completed tournaments cannot be deleted.
     *
     * @authenticated
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "deleted was successfully",
     *   "data": []
     * }
     * @response 403 scenario="Tournament completed" {
     *   "success": false,
     *   "message": {
     *     "id": 1,
     *     "platform": "PC",
     *     "game": "FIFA 24",
     *     "status": "COMPLETED"
     *   },
     *   "data": "Tournament completed and cannot be deleted"
     * }
     */
    public function destroy(Tournament $tournament)
    {
        $this->authorize('delete',$tournament);

        $tournament->delete();

        return $this->sendResponse([],'deleted was successfully' , 204);
    }


    /**
     * List tournament players
     *
     * Returns all users signed up for the given tournament.
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "tournament's players",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     }
     *   ]
     * }
     */
    public function players(Tournament $tournament)
    {
        return $this->sendResponse($tournament->players,"tournament's players" , 200);
    }

    /**
     * Sign up for a tournament
     *
     * Registers the authenticated user as a player in the tournament.
     *
     * @authenticated
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "you are successfully signed up",
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "John Doe",
     *       "email": "john@example.com"
     *     }
     *   ]
     * }
     * @response 404 scenario="Already signed up" {
     *   "success": false,
     *   "message": [],
     *   "data": "you already in this tournament"
     * }
     */
    public function signUp(Tournament $tournament , Request $request , TournamentService $tournamentService)
    {
        try {
            $tournamentService->signUp($request->user() , $tournament);
            return $this->sendResponse($tournament->players , 'you are successfully signed up' , 200);
        }catch (\Exception $e){
            return $this->sendError($e->getMessage() , [] , 422);
        }
    }

//    /**
//     * Signed out tournament
//     *
//     * signed out the authenticated user as a player of the tournament.
//     *
//     * @authenticated
//     *
//     *
//     * @response 200 scenario="Success" {
//     *   "success": true,
//     *   "message": "you are successfully signed out",
//     *   "data": [
//     *     {
//     *       "id": 1,
//     *       "name": "John Doe",
//     *       "email": "john@example.com"
//     *     }
//     *   ]
//     * }
//     * @response 404 scenario="Already signed out" {
//     *   "success": false,
//     *   "message": [],
//     *   "data": "You are not registered in this tournament"
//     * }
//     */
//    public function signOut(Tournament $tournament,TournamentService $tournamentService)
//    {
//        try {
//            $tournamentService->signOut($tournament);
//            return $this->sendResponse([],'you are successfully signed out');
//        }catch (\Exception $e){
//            return $this->sendError($e->getMessage() , [] , 422);
//        }
//    }

}
