<?php

namespace App\Livewire;

use App\Enums\Platforms\PlatformEnum;
use App\Enums\Tournaments\TournamentEnum;
use App\Models\Tournament;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The full list of tournaments.
 *
 * The landing page only ever showed a handful, and once there were more than a
 * screenful there was nowhere to send somebody looking for the rest. This is
 * that place: every tournament, narrowed by status and platform.
 */
class Tournaments extends Component
{
    use WithPagination;

    /** How many fit on a page before the list stops being readable. */
    private const PER_PAGE = 12;

    /** Kept in the query string so a filtered list can be linked and shared. */
    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $platform = '';

    /** A narrowed list starts again from the first page, not page 4 of nothing. */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function updatedPlatform(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->status = '';
        $this->platform = '';
        $this->resetPage();
    }

    public function render()
    {
        $tournaments = Tournament::query()
            ->withCount(['players', 'matches'])
            ->with('winner')
            // Anything the query string made up is ignored rather than run as a
            // filter that quietly matches nothing.
            ->when($this->validStatus(), fn ($query, $status) => $query->where('status', $status))
            ->when($this->validPlatform(), fn ($query, $platform) => $query->where('platform', $platform))
            // Id breaks the tie: several tournaments put on in the same
            // second order arbitrarily otherwise, and an arbitrary order
            // across pages shows one row twice and hides another.
            ->latest()
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE);

        return view('livewire.tournaments', [
            'tournaments' => $tournaments,
            'statuses' => TournamentEnum::cases(),
            'platforms' => PlatformEnum::cases(),
        ])->layout('components.layouts.app');
    }

    private function validStatus(): ?string
    {
        return array_key_exists($this->status, TournamentEnum::values()) ? $this->status : null;
    }

    private function validPlatform(): ?string
    {
        return array_key_exists($this->platform, PlatformEnum::values()) ? $this->platform : null;
    }
}
