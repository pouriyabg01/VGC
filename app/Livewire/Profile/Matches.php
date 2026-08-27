<?php

namespace App\Livewire\Profile;

use App\Enums\Tournaments\TournamentMatchEnum;
use App\Models\TournamentMatch;
use App\Traits\TournamentMatchTrait;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Matches extends Component
{
    use TournamentMatchTrait;

    /**
     * Goal inputs keyed by match id, so several open forms keep their own
     * values: ['12' => ['scored' => '3', 'conceded' => '1']].
     *
     * @var array<int, array<string, mixed>>
     */
    public array $goals = [];

    public function submit(int $matchId): void
    {
        $match = TournamentMatch::find($matchId);

        if (! $match) {
            $this->addError('match.'.$matchId, 'That match no longer exists.');

            return;
        }

        $validated = validator(
            [
                'scored' => $this->goals[$matchId]['scored'] ?? null,
                'conceded' => $this->goals[$matchId]['conceded'] ?? null,
            ],
            [
                'scored' => ['required', 'integer', 'min:0'],
                'conceded' => ['required', 'integer', 'min:0'],
            ],
            [],
            ['scored' => 'goals scored', 'conceded' => 'goals conceded'],
        );

        if ($validated->fails()) {
            $this->addError('match.'.$matchId, $validated->errors()->first());

            return;
        }

        try {
            // Same path the API uses, so both enforce the same rules.
            $this->submitResultFor(
                Auth::user(),
                $match,
                (int) $validated->validated()['scored'],
                (int) $validated->validated()['conceded'],
            );
        } catch (\Exception $e) {
            $this->addError('match.'.$matchId, $e->getMessage());

            return;
        }

        unset($this->goals[$matchId]);
    }

    public function render()
    {
        $user = Auth::user();

        $matches = $user->matches()
            ->with(['tournament', 'player1', 'player2', 'winner', 'submissions'])
            ->orderByDesc('round')
            ->orderByDesc('id')
            ->get();

        return view('livewire.profile.matches', [
            'matches' => $matches,
            'userId' => $user->id,
            'pending' => TournamentMatchEnum::PENDING,
        ]);
    }
}
