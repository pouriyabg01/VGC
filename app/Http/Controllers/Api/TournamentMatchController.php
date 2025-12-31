<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Helper\CreateMatches;
use App\Http\Requests\MatchRequest;
use App\Http\Resources\MatchResultResource;
use App\Models\Tournament;
use App\Traits\TournamentMatchTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\TournamentMatch;

/**
 * @group match management
 */
class TournamentMatchController extends BaseController
{
    use TournamentMatchTrait,AuthorizesRequests;
    /**
     * show matches of tournament
     * @urlParam tournament integer required
     * @param Tournament $tournament
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Tournament $tournament)
    {
        return $this->sendResponse(
            $tournament->matches,
            'match list'
        );
    }

    /**
     * create matches for tournament
     * @authenticated
     * @bodyParam tournament_id integer required
     * @bodyParam players array required min:2
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(MatchRequest $request , CreateMatches $matches)
    {
        $this->authorize('create' , TournamentMatch::class);
        return $this->sendResponse($matches($request->validated()), 'matches created successfully', 201);
    }

    /**
     * Admin/manual result
     * @bodyParam player1_goal integer required
     * @bodyParam player2_goal integer required
     * @authenticated
     * @param Request $request
     * @param TournamentMatch $tournamentMatch
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, TournamentMatch $tournamentMatch)
    {
        $this->authorize('submit' , TournamentMatch::class);
        $data = $request->validate([
            'player1_goal' => 'required|integer|min:0',
            'player2_goal' => 'required|integer|min:0',
        ]);

        $this->finalizeMatch(
            $tournamentMatch,
            $data['player1_goal'],
            $data['player2_goal']
        );


        return $this->sendResponse($tournamentMatch, 'نتیجه مسابقه ثبت شد');
    }

    /**
     * Player submitted result
     * @bodyParam scored_goals integer required
     * @bodyParam conceded_goals integer required
     * @authenticated
     * @param Request $request
     * @param TournamentMatch $tournamentMatch
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitResult(Request $request, TournamentMatch $tournamentMatch)
    {
        //TODO add screenshot photo input
        $user = $request->user();

        //get players of this match
        $matchUsers = [
            $tournamentMatch->player1,
            $tournamentMatch->player2
        ];

        //check if loggedin user is one of player of match
        if (!in_array($user->id , $matchUsers , true)){
            return $this->sendError([],'you not in this match',422);
        }

        $data = $request->validate([
//TODO            'image' => 'required|file',
            'scored_goals' => 'required|integer|min:0',
            'conceded_goals' => 'required|integer|min:0',
        ]);

        //check for player submit result
        if ($tournamentMatch->submissions()->where('user_id', $user->id)->exists()) {
            return $this->sendError([], 'You already submitted result', 422);
        }

        $match = $tournamentMatch->submissions()->create([
//TODO            'image' => $data['image'],
            'user_id' => $user->id,
            'scored_goals' => $data['scored_goals'],
            'conceded_goals' => $data['conceded_goals'],
        ]);

        if ($tournamentMatch->submissions()->count() === 2) {
            $this->resolveBySubmissions($tournamentMatch);
        }

        $this->generateNextRound($tournamentMatch->tournament);

        return $this->sendResponse(new MatchResultResource($match), 'Result submitted');
    }
}
