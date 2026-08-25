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

        $this->authorize('signUp', $this->tournament);

        try {
            app(TournamentService::class)->signUp($user, $this->tournament);
            $this->loadTournament($this->tournament);
            session()->flash('message', 'You have successfully signed up!');
        } catch (\Exception $e) {
            $this->addError('signUp', $e->getMessage());
        }
    }

//    public function signOut(): void
//    {
//        $user = Auth::user();
//        if (!$user) return;
//
//        try {
//            app(TournamentService::class)->signOut($this->tournament);
//            $this->loadTournament($this->tournament);
//            session()->flash('message', 'You have successfully signed out!');
//        } catch (\Exception $e) {
//            $this->addError('signOut', $e->getMessage());
//        }
//    }

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
            'players',
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
