<?php

namespace App\Http\Controllers\Api;

use App\Enums\Tournaments\TournamentEnum;
use App\Http\Requests\TournamentRequest;
use App\Http\Resources\TournamentResource;
use App\Models\Tournament;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * @group Tournament Management
 */
class TournamentController extends BaseController
{
    use AuthorizesRequests;

    /**
     * create a tournament
     * @authenticated
     * @bodyParam game string required
     * @param TournamentRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
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
     *
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
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


    /**
     * show players of tournament
     * @urlParam id required the id of tournament
     * @param Tournament $tournament
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function players(Tournament $tournament)
    {
        return $this->sendResponse($tournament->players,"tournament's players");
    }

    /**
     * sign up for tournament
     * @urlParam id required the id of tournament
     * @param Tournament $tournament
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function signUp(Tournament $tournament , Request $request)
    {
        $this->authorize('signUp' , $tournament);
        $user = $request->user();

        if ($tour = $user->tournaments()->where('tournament_id' , $tournament->id)->first()){
            switch ($tour->status){
                case (TournamentEnum::COMPLETED) : $err = 'tournament already finished';
                break;
                case (TournamentEnum::GAMING) : $err = 'tournament just playing';
                break;
                default : $err = 'you already in this tournament';
            }
            return $this->sendError([],$err);
        }
        $user->tournaments()->attach($tournament);


        return $this->sendResponse($tournament->players , 'you are successfully signed up' , 201);
    }

}
