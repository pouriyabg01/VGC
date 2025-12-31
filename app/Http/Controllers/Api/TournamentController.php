<?php

namespace App\Http\Controllers\Api;

use App\Enums\Tournaments\TournamentEnum;
use App\Http\Requests\TournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Tournament management
 */
class TournamentController extends BaseController
{
    use AuthorizesRequests;

    /**
     *
     * create a tournament
     * @bodyParam game string required
     * @param TournamentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function store(TournamentRequest $request)
    {
        $this->authorize('create',Tournament::class);

        $tournament = Tournament::create($request->validated());

        return $this->sendResponse(new TournamentResource($tournament) , 'Tournament created successfully');

    }

    /**
     * update tournament
     * @bodyParam game string required
     * @bodyParam status string required Example 'COMPLETED,PENDING,CANCELED,GAMING'
     * @param Request $request
     * @param Tournament $tournament
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request , Tournament $tournament)
    {
        $this->authorize('update',$tournament);

        $data = $request->validate([
            'game' => 'required|string',
            'status' => ['required' , new Enum(TournamentEnum::class)]
        ]);

        $tournament->update($data);
        return $this->sendResponse($tournament,'successfully updated');
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
     * show tournament
     * @urlParam id integer required the id of tournament
     * @param Tournament $tournament
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Tournament $tournament)
    {
        return $this->sendResponse(new TournamentResource($tournament) , 'specified tournament' , 200);
    }

    /**
     * delete tournament
     * @urlParam id integer required the id of tournament
     * @param Tournament $tournament
     * @return \Illuminate\Http\JsonResponse
     * @authenticated
     */
    public function destroy(Tournament $tournament)
    {
        $this->authorize('delete',$tournament);

        if ($tournament->status === TournamentEnum::COMPLETED){
            return $this->sendError(new TournamentResource($tournament) , 'Tournament completed and cannot be deleted' , 403);
        }
        $tournament->delete();
        return $this->sendResponse([],'deleted was successfully');
    }

}
