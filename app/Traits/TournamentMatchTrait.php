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
    /** Said the same way by the player form, the admin panel and the API. */
    public const DRAW_REFUSED = 'A draw cannot settle a knockout match. Play it out — extra time or penalties — and report the decisive score.';

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

        // A knockout match has to send somebody through, so a level score is
        // not a result the bracket can use. Refused here rather than stored,
        // so the players go and settle it instead of the match dead-ending.
        if ($scored === $conceded) {
            throw new \Exception(self::DRAW_REFUSED);
        }

        // Written only after the rules above pass, so a refused submission
        // leaves nothing behind on disk.
        $submission = $match->submissions()->create([
            'user_id' => $user->id,
            'screenshot' => $this->storeScreenshot($screenshot, $match, $user),
            'scored' => $scored,
            'conceded' => $conceded,
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

    /**
     * When a match drawn now stops waiting on its players.
     *
     * Read off the tournament so a weekend cup can run tighter than one that
     * spans weeks; 24 hours when the tournament says nothing.
     */
    private function deadlineFor(Tournament $tournament): \Illuminate\Support\Carbon
    {
        return now()->addHours($tournament->result_deadline_hours ?: 24);
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

        // Ordered by match id, so the winner of the first match meets the
        // winner of the second. Loading the users instead returned them in id
        // order, which paired the bracket by who registered first rather than
        // by where they actually sat in the draw.
        $winnerIds = $matches->sortBy('id')->pluck('winner_id')->values();

        // A completed match always names a winner now that draws are refused.
        // One without a winner means the round is not really settled, and
        // silently dropping it is what used to delete players from the draw.
        if ($winnerIds->contains(null)) {
            throw new \RuntimeException(
                "Tournament {$tournament->id} has a completed match with no winner; the round cannot be drawn."
            );
        }

        // Tournament finished
        if ($winnerIds->count() === 1) {
            $champion = User::findOrFail($winnerIds->first());
            app(TournamentService::class)->finalizeTournament($tournament, $champion);

            // The pass is not touched here any more. An entry is spent when a
            // player signs up, so closing their pass again at the final would
            // charge them twice for one tournament — and would wipe the VS
            // games they had not used.

            return true;
        }

        // An odd count cannot be paired, so somebody would be left out. That
        // is a broken bracket, not a round to draw.
        if ($winnerIds->count() % 2 !== 0) {
            throw new \RuntimeException(
                "Tournament {$tournament->id} has {$winnerIds->count()} winners in round {$currentRound}; a round cannot be drawn from an odd count."
            );
        }

        $deadline = $this->deadlineFor($tournament);

        $winnerIds->chunk(2)->each(function ($pair) use ($tournament, $nextRound, $deadline) {
            $pair = $pair->values();
            $tournament->matches()->create([
                'player1_id'  => $pair[0],
                'player2_id'  => $pair[1],
                'round'       => $nextRound,
                'deadline_at' => $deadline,
            ]);
        });

        return false;
    }


    /**
     * Settles every match whose reporting deadline has passed.
     *
     * One report standing unanswered is taken at its word — the player who
     * did not turn up does not get to stall the bracket. No report at all is
     * nobody's word to take, so it goes to an admin instead of being decided
     * by a coin toss.
     *
     * @return array{settled: int, disputed: int}
     */
    private function forfeitOverdueMatches(): array
    {
        $overdue = TournamentMatch::query()
            ->where('status', TournamentMatchEnum::PENDING)
            ->whereNotNull('deadline_at')
            ->where('deadline_at', '<=', now())
            ->with(['submissions', 'tournament'])
            ->get();

        $settled = $disputed = 0;

        foreach ($overdue as $match) {
            $reports = $match->submissions;

            if ($reports->count() !== 1) {
                $match->status = TournamentMatchEnum::DISPUTED;
                $match->save();
                $disputed++;

                continue;
            }

            $report = $reports->first();

            // The report is written from the reporter's side, so it only maps
            // onto player1/player2 once we know which of them sent it.
            [$p1, $p2] = $report->user_id === $match->player1_id
                ? [$report->scored, $report->conceded]
                : [$report->conceded, $report->scored];

            $this->finalizeMatch($match, $p1, $p2);
            $this->generateNextRound($match->tournament);
            $settled++;
        }

        return ['settled' => $settled, 'disputed' => $disputed];
    }

    /*
     * store the match result
     */
    private function finalizeMatch(TournamentMatch $match, int $p1Score, int $p2Score)
    {
        $match->player1_score = $p1Score;
        $match->player2_score = $p2Score;
        $match->match_date = now();

        // Callers validate before reaching here; this is the backstop that
        // keeps a level score from ever being written as a settled match.
        if ($p1Score === $p2Score) {
            throw new \Exception(self::DRAW_REFUSED);
        }

        $match->winner_id = $p1Score > $p2Score
            ? $match->player1_id
            : $match->player2_id;

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

        $agree = $p1->scored === $p2->conceded
            && $p2->scored === $p1->conceded;

        // Draws are refused on the way in, so agreeing on one means something
        // upstream let it through. An admin judging it beats an exception
        // thrown at whichever player happened to report second.
        if ($agree && $p1->scored !== $p2->scored) {
            $this->finalizeMatch(
                $match,
                $p1->scored,
                $p2->scored
            );
        } else {
            $match->status = TournamentMatchEnum::DISPUTED;
            $match->save();
        }
    }
}
