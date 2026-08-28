<?php

namespace App\Livewire\Profile;

use App\Enums\Tournaments\TournamentMatchEnum;
use App\Models\TournamentMatch;
use App\Support\MatchScreenshot;
use App\Traits\TournamentMatchTrait;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class Matches extends Component
{
    use TournamentMatchTrait, WithFileUploads;

    /**
     * Score inputs keyed by match id, so several open forms keep their own
     * values: ['12' => ['scored' => '3', 'conceded' => '1']].
     *
     * @var array<int, array<string, mixed>>
     */
    public array $scores = [];

    /**
     * Proof screenshots keyed by match id, so each open form holds its own
     * upload: ['12' => TemporaryUploadedFile].
     *
     * @var array<int, mixed>
     */
    public array $screenshots = [];

    public function submit(int $matchId): void
    {
        $match = TournamentMatch::find($matchId);

        if (! $match) {
            $this->addError('match.'.$matchId, 'That match no longer exists.');

            return;
        }

        $validated = validator(
            [
                'scored' => $this->scores[$matchId]['scored'] ?? null,
                'conceded' => $this->scores[$matchId]['conceded'] ?? null,
                'screenshot' => $this->screenshots[$matchId] ?? null,
            ],
            [
                'scored' => ['required', 'integer', 'min:0'],
                'conceded' => ['required', 'integer', 'min:0'],
                // Read from the same place the API reads, so a file the page
                // accepts is one the API would accept too.
                'screenshot' => MatchScreenshot::rules(),
            ],
            MatchScreenshot::messages(),
            [
                'scored' => 'score',
                'conceded' => 'conceded',
                'screenshot' => 'screenshot',
            ],
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
                $validated->validated()['screenshot'],
            );
        } catch (\Exception $e) {
            $this->addError('match.'.$matchId, $e->getMessage());

            return;
        }

        unset($this->scores[$matchId], $this->screenshots[$matchId]);
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
            // Told to the player up front, and enforced by the same constants.
            'screenshotHint' => MatchScreenshot::hint(),
            'screenshotAccept' => MatchScreenshot::accept(),
        ]);
    }
}
