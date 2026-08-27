<?php

namespace App\Http\Controllers\Api;

use App\Enums\Tournaments\TournamentEnum;
use App\Services\CreateMatches;
use App\Http\Resources\MatchResultResource;
use App\Models\Tournament;
use App\Support\MatchScreenshot;
use App\Traits\TournamentMatchTrait;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use App\Models\TournamentMatch;
use function PHPUnit\Framework\isEmpty;

/**
 * @group Match Management
 *
 * APIs for listing, creating, and submitting tournament match results.
 */
class TournamentMatchController extends BaseController
{
    use TournamentMatchTrait,AuthorizesRequests;

    /**
     * List tournament matches
     *
     * Returns all matches for the given tournament.
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Match list",
     *   "data": [
     *     {
     *       "id": 1,
     *       "tournament_id": 1,
     *       "player1_id": 1,
     *       "player2_id": 2,
     *       "winner_id": null,
     *       "player1_goal": null,
     *       "player2_goal": null,
     *       "round": 1
     *     }
     *   ]
     * }
     * @response 404 scenario="No matches" {
     *   "success": false,
     *   "message": "There are no matches for this tournament"
     * }
     */
    public function index(Tournament $tournament)
    {
        $matches = $tournament->matches;

        if ($matches->isEmpty()) {
            return $this->sendError('There are no matches for this tournament');
        }

        return $this->sendResponse(
            $matches,
            'Match list'
         , 200);
    }

    /**
     * Create tournament matches
     *
     * Generates bracket matches from signed-up players. The number of players must be a power of 2 (minimum 2). Sets the tournament status to GAMING on success.
     *
     * @authenticated
     *
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "matches created successfully",
     *   "data": [
     *     {
     *       "id": 1,
     *       "tournament_id": 1,
     *       "player1_id": 1,
     *       "player2_id": 2,
     *       "round": 1
     *     }
     *   ]
     * }
     * @response 422 scenario="Invalid player count" {
     *   "success": false,
     *   "message": "number of players should be power of 2",
     *   "data": []
     * }
     */
    public function store(CreateMatches $matches , Tournament $tournament)
    {
        $this->authorize('create' , TournamentMatch::class);
        $result = $matches->execute($tournament);
        if ($result['error'] !== null){
            return $this->sendError($result['error']['message'],[],422);
        }
        $tournament->update([
            'status' => TournamentEnum::GAMING
        ]);
        return $this->sendResponse($result['matches'], 'matches created successfully', 201);
    }

    /**
     * Submit match result (admin)
     *
     * Allows an admin to manually set the final score for a match.
     *
     * @authenticated
     *
     *
     * @bodyParam player1_goal integer required Goals scored by player 1. Example: 2
     * @bodyParam player2_goal integer required Goals scored by player 2. Example: 1
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Match result submitted successfully",
     *   "data": {
     *     "id": 1,
     *     "tournament_id": 1,
     *     "player1_id": 1,
     *     "player2_id": 2,
     *     "player1_goal": 2,
     *     "player2_goal": 1,
     *     "winner_id": 1
     *   }
     * }
     */
    public function submitByAdmin(Request $request, TournamentMatch $tournamentMatch)
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

        $this->generateNextRound($tournamentMatch->tournament);

        return $this->sendResponse($tournamentMatch, 'Match result submitted successfully' , 201);
    }

    /**
     * Submit match result (player)
     *
     * Allows a match participant to submit their reported score. When both players submit, the result is resolved automatically.
     *
     * @authenticated
     *
     *
     * @bodyParam scored_goals integer required Goals scored by the submitting player. Example: 3
     * @bodyParam conceded_goals integer required Goals conceded by the submitting player. Example: 1
     * @bodyParam screenshot file required Proof of the final score. JPG, PNG or WEBP, up to 5 MB.
     *
     * @response 200 scenario="Success" {
     *   "success": true,
     *   "message": "Result submitted",
     *   "data": {
     *     "tournament_id": 1,
     *     "match_id": 1,
     *     "user_id": 1,
     *     "screenshot": "conclusion-screenshot/1/1/9kQ2m0XcVb.jpg",
     *     "screenshot_url": "http://localhost/storage/conclusion-screenshot/1/1/9kQ2m0XcVb.jpg",
     *     "scored_goals": 3,
     *     "conceded_goals": 1
     *   }
     * }
     * @response 422 scenario="Unacceptable screenshot" {
     *   "message": "The screenshot must be a JPG, PNG or WEBP image.",
     *   "errors": {
     *     "screenshot": ["The screenshot must be a JPG, PNG or WEBP image."]
     *   }
     * }
     * @response 422 scenario="Not a participant" {
     *   "success": false,
     *   "message": "you not in this match",
     *   "data": []
     * }
     */
    public function submitByPlayer(Request $request, TournamentMatch $tournamentMatch)
    {
        $data = $request->validate([
            // Shared with the profile form, so both sides accept the same files.
            'screenshot' => MatchScreenshot::rules(),
            'scored_goals' => 'required|integer|min:0',
            'conceded_goals' => 'required|integer|min:0',
        ], MatchScreenshot::messages());

        try {
            $match = $this->submitResultFor(
                $request->user(),
                $tournamentMatch,
                (int) $data['scored_goals'],
                (int) $data['conceded_goals'],
                $data['screenshot'],
            );
        } catch (\Exception $e) {
            return $this->sendError($e->getMessage(), [], 422);
        }

        $finished = $tournamentMatch->tournament->fresh()->status === TournamentEnum::COMPLETED;

        return $this->sendResponse(
            new MatchResultResource($match),
            $finished ? 'Result submitted. The tournament is finito!' : 'Result submitted',
            200
        );
    }

}
