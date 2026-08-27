<?php

namespace App\Traits;

use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use App\Enums\Tournaments\TournamentMatchResultEnum;
use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\TournamentResource;
use App\Models\MatchResult;
use App\Models\Tournament;
use App\Models\TournamentMatch;
use App\Models\User;
use App\Services\Subscription;
use App\Services\SubscriptionService;
use App\Services\TournamentService;
use Illuminate\Http\UploadedFile;

trait TournamentMatchTrait
{
    /**
     * ======================
     *      Shared Logic
     * ======================
     */

    /**
     * Records one player's result for a match and settles the match once both
     * sides have reported.
     *
     * Lives here so the API and the profile page run the same rules rather
     * than each keeping their own copy.
     *
     * @throws \Exception when the user is not in the match, the match is not
     *                     open, they have already reported, or the screenshot
     *                     cannot be written to disk.
     */
    private function submitResultFor(User $user, TournamentMatch $match, int $scored, int $conceded, UploadedFile $screenshot): MatchResult
    {
        if (! in_array($user->id, [$match->player1_id, $match->player2_id], true)) {
            throw new \Exception('you not in this match');
        }

        if ($match->status !== TournamentMatchEnum::PENDING) {
            throw new \Exception('This match is no longer open for results.');
        }

        if ($match->submissions()->where('user_id', $user->id)->exists()) {
            throw new \Exception('You already submitted result');
        }

        // Written only after the rules above pass, so a refused submission
        // leaves nothing behind on disk.
        $submission = $match->submissions()->create([
            'user_id' => $user->id,
            'screenshot' => $this->storeScreenshot($screenshot, $match, $user),
            'scored_goals' => $scored,
            'conceded_goals' => $conceded,
        ]);

        if ($match->submissions()->count() === 2) {
            $this->resolveBySubmissions($match);
            $this->generateNextRound($match->tournament);
        }

        return $submission;
    }

    /**
     * Puts a player's proof screenshot on the public disk and hands back the
     * path to record against the submission.
     *
     * The path stays relative to the disk root, because cleanup:screenshots
     * compares the stored value against Storage::allFiles('conclusion-screenshot'),
     * which yields that same form. Storing a URL or an absolute path here would
     * make every file look orphaned and get it deleted.
     *
     * @throws \Exception when the disk refuses the write.
     */
    private function storeScreenshot(UploadedFile $screenshot, TournamentMatch $match, User $user): string
    {
        $path = $screenshot->store(
            'conclusion-screenshot/'.$match->id.'/'.$user->id,
            'public'
        );

        // The public disk is configured with 'throw' => false, so a failed
        // write comes back as false rather than as an exception.
        if (! is_string($path) || $path === '') {
            throw new \Exception('Could not save your screenshot. Please try again.');
        }

        return $path;
    }

    private function generateNextRound(Tournament $tournament)
    {
        // Get current round number for THIS tournament
        $currentRound = $tournament->matches()->max('round');

        if (is_null($currentRound)) return null;


        // Get all matches of this round
        $matches = $tournament->matches()
            ->where('round', $currentRound)
            ->get();

        // All matches must be completed
        if (! $matches->every(fn ($match) => $match->status === TournamentMatchEnum::COMPLETED)) {
            return null;
        }

        // Prevent duplicate generation
        $nextRound = $currentRound + 1;

        if ($tournament->matches()
                ->where('round', $nextRound)
                ->exists()) {
            return null;
        }

        $winnersId = $matches->pluck('winner_id')->filter()->values();
        $winners = User::whereIn('id' , $winnersId)->get();

        // Tournament finished
        if ($winners->count() === 1) {
            app(TournamentService::class)->finalizeTournament($tournament,$winners->first());
            app(SubscriptionService::class)->deactivateFor($tournament->players);
            return true;
        }

        // Create next round matches
        $winners->chunk(2)->each(function ($pair) use ($tournament, $nextRound) {
            if ($pair->count() === 2) {
                $tournament->matches()->create([
                    'player1_id' => $pair[0]->id,
                    'player2_id' => $pair[1]->id,
                    'round'      => $nextRound,
                ]);
            }
        });
        return false;
    }


    /*
     * store the match result
     */
    private function finalizeMatch(TournamentMatch $match, int $p1Goals, int $p2Goals)
    {
        $match->player1_goal = $p1Goals;
        $match->player2_goal = $p2Goals;
        $match->match_date = now();

        if ($p1Goals > $p2Goals) {
            $match->winner_id = $match->player1->id;
        } elseif ($p2Goals > $p1Goals) {
            $match->winner_id = $match->player2->id;
        } else {
            $match->winner_id = null;
        }

        $match->submissions()->update(['status' => TournamentMatchResultEnum::CONFIRMED]);

        $match->status = TournamentMatchEnum::COMPLETED;
        $match->save();
    }

    /*
     * resolve the match result
     */
    private function resolveBySubmissions(TournamentMatch $match)
    {
        $subs = $match->submissions;

        $p1 = $subs->where('user_id', $match->player1->id)->first();
        $p2 = $subs->where('user_id', $match->player2->id)->first();

        if (
            $p1->scored_goals === $p2->conceded_goals &&
            $p2->scored_goals === $p1->conceded_goals
        ) {
            $this->finalizeMatch(
                $match,
                $p1->scored_goals,
                $p2->scored_goals
            );
        } else {
            $match->status = TournamentMatchEnum::DISPUTED;
            $match->save();
        }
    }
}
