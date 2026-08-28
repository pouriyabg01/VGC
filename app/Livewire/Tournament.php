<?php

namespace App\Livewire;

use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament as TournamentModel;
use App\Services\TournamentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Tournament extends Component
{
    use AuthorizesRequests;

    public TournamentModel $tournament;

    public function mount(TournamentModel $tournament): void
    {
        $this->loadTournament($tournament);
    }

    public function signUp(): void
    {
        $user = Auth::user();

        if (! $user) {
            $this->redirectRoute('login', navigate: true);
            return;
        }

        try {
            // The service authorizes as its first step, so the denial arrives
            // here as an AuthorizationException and becomes an inline message.
            // Calling authorize() out here as well threw past this catch and
            // replaced the page with a 403.
            app(TournamentService::class)->signUp($user, $this->tournament);
            $this->loadTournament($this->tournament);
            session()->flash('message', 'You have successfully signed up!');
        } catch (\Exception $e) {
            $this->addError('signUp', $e->getMessage());
        }
    }

    public function signOut(): void
    {
        if (! Auth::check()) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        try {
            // The service holds the rule about when leaving is still allowed,
            // so a refusal arrives here as a message rather than a 403 page.
            app(TournamentService::class)->signOut($this->tournament);
            $this->loadTournament($this->tournament->fresh());
            session()->flash('message', 'You have left this tournament.');
        } catch (\Exception $e) {
            $this->addError('signOut', $e->getMessage());
        }
    }

    /**
     * Whether the viewer can still take their seat back.
     *
     * Mirrors the service so the page can hide the button rather than offer a
     * click that is going to be refused.
     */
    public function canSignOut(): bool
    {
        if (! $this->isSignedUp()) {
            return false;
        }

        return ! $this->tournament->matches()->exists()
            && in_array($this->tournament->status, [TournamentEnum::PENDING, TournamentEnum::READY], true);
    }

    public function isSignedUp(): bool
    {
        $user = Auth::user();

        if (! $user) return false;

        return $user->tournaments()->where('tournament_id', $this->tournament->id)->exists();
    }

    public function hasActiveSubscription(): bool
    {
        $user = Auth::user();

        return $user !== null && $user->latestActiveSub()->exists();
    }

    /**
     * TournamentService refuses a sign-up without an account on the
     * tournament's platform. Mirrored here so the page can say so up front
     * instead of failing after the click.
     */
    public function hasMatchingPlatform(): bool
    {
        $user = Auth::user();

        return $user !== null
            && $user->platforms()->where('platform', $this->tournament->platform)->exists();
    }

    public function canSignUp(): bool
    {
        if (! Auth::check() || $this->isSignedUp()) {
            return false;
        }

        if ($this->tournament->status !== TournamentEnum::PENDING) {
            return false;
        }

        return $this->hasActiveSubscription() && $this->hasMatchingPlatform();
    }

    private function loadTournament(TournamentModel $tournament): void
    {
        $this->tournament = $tournament->load([
            'winner',
            'players.platforms',
            'matches.player1',
            'matches.player2',
            'matches.winner',
        ]);
    }

    public function render()
    {
        return view('livewire.tournament')->layout('components.layouts.app');
    }
}
