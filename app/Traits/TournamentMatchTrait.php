<?php

namespace App\Traits;

use App\Enums\Tournaments\TournamentEnum;
use App\Enums\Tournaments\TournamentMatchEnum;
use App\Enums\Tournaments\TournamentMatchResultEnum;
use App\Models\Tournament;
use App\Models\TournamentMatch;

trait TournamentMatchTrait
{
    /**
     * ======================
     *      Shared Logic
     * ======================
     */
    private function generateNextRound(Tournament $tournament)
    {
        // Get current round number for THIS tournament
        $currentRound = $tournament->matches()->max('round');

        if ($currentRound === null) {
            return;
        }

        // Get all matches of this round
        $matches = $tournament->matches()
            ->where('round', $currentRound)
            ->get();

        // All matches must be completed
        if (! $matches->every(fn ($match) => $match->status === TournamentMatchEnum::COMPLETED)) {
            return;
        }

        // Prevent duplicate generation
        $nextRound = $currentRound + 1;

        if (
            $tournament->matches()
                ->where('round', $nextRound)
                ->exists()
        ) {
            return;
        }

        $winners = $matches->pluck('winner_id')->filter()->values();

        // Tournament finished
        if ($winners->count() === 1) {
            $tournament->update([
                'winner_id' => $winners->first(),
                'status'    => TournamentEnum::COMPLETED,
                'end_at'    => now(),
            ]);
            return;
        }

        // Create next round matches
        for ($i = 0; $i < $winners->count(); $i += 2) {
            $tournament->matches()->create([
                'player1' => $winners[$i],
                'player2' => $winners[$i + 1],
                'round'   => $nextRound,
            ]);
        }
    }

    private function resolveBySubmissions(TournamentMatch $match)
    {
        $subs = $match->submissions;

        $p1 = $subs->where('user_id', $match->player1)->first();
        $p2 = $subs->where('user_id', $match->player2)->first();

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

    private function finalizeMatch(TournamentMatch $match, int $p1Goals, int $p2Goals)
    {
        $match->player1_goal = $p1Goals;
        $match->player2_goal = $p2Goals;
        $match->match_date = now();

        if ($p1Goals > $p2Goals) {
            $match->winner_id = $match->player1;
        } elseif ($p2Goals > $p1Goals) {
            $match->winner_id = $match->player2;
        } else {
            $match->winner_id = null;
        }

        $match->submissions()->update(['status' => TournamentMatchResultEnum::CONFIRMED]);

        $match->status = TournamentMatchEnum::COMPLETED;
        $match->save();
    }
}
