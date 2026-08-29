{{--
    Filament v5 ships only its own fi-* component classes; the generic Tailwind
    utilities are not in the panel's stylesheet. So the layout here is written
    in inline styles, and anything that has to match the panel's theme — the
    section, the badges — is a Filament component rather than markup of ours.
--}}
@php($pollingInterval = $this->getPollingInterval())

{{-- Polls itself: a like cast on the landing page has to land here without
     the panel being reloaded. --}}
<x-filament-widgets::widget
    :attributes="
        (new \Filament\Support\View\ComponentAttributeBag)
            ->merge(['wire:poll.'.$pollingInterval => $pollingInterval ? true : null], escape: false)
    ">
    <x-filament::section>
        <x-slot name="heading">Most wanted</x-slot>
        <x-slot name="description">
            What players have liked, and how close each one is to being worth putting on
        </x-slot>

        @php($games = $this->getGames())

        @if ($games->isEmpty() || $games->sum('voters_count') === 0)
            <p style="font-size: 0.875rem; opacity: 0.7; margin: 0;">
                Nobody has liked a game yet. The button is on the landing page, under every
                game that is live.
            </p>
        @else
            <div style="display: flex; flex-direction: column; gap: 1.125rem;">
                @foreach ($games as $rank => $game)
                    @php($count = (int) $game->voters_count)
                    @php($target = max(1, (int) $game->votes_target))
                    @php($percent = min(100, (int) round($count / $target * 100)))
                    @php($ready = $count >= $target)
                    {{-- The first three are the ones a decision gets made from,
                         so they are marked rather than merely first. --}}
                    @php($medal = [0 => '#f59e0b', 1 => '#94a3b8', 2 => '#b45309'][$rank] ?? null)

                    <div style="display: flex; align-items: center; gap: 0.875rem;">
                        <span style="flex: none; width: 1.75rem; height: 1.75rem; border-radius: 9999px;
                                     display: flex; align-items: center; justify-content: center;
                                     font-size: 0.75rem; font-weight: 700; font-variant-numeric: tabular-nums;
                                     {{ $medal
                                        ? 'background:'.$medal.'; color: #fff;'
                                        : 'background: rgb(148 163 184 / 0.25);' }}">
                            {{ $rank + 1 }}
                        </span>

                        @if ($game->imageUrl())
                            <img src="{{ $game->imageUrl() }}" alt=""
                                 style="flex: none; width: 2.25rem; height: 3rem; object-fit: cover;
                                        border-radius: 0.375rem; {{ $game->is_active ? '' : 'opacity: 0.55;' }}">
                        @else
                            <span style="flex: none; width: 2.25rem; height: 3rem; border-radius: 0.375rem;
                                         background: rgb(148 163 184 / 0.2);"></span>
                        @endif

                        <div style="flex: 1 1 auto; min-width: 0;">
                            <div style="display: flex; align-items: baseline; justify-content: space-between; gap: 0.75rem;">
                                <span style="font-weight: 600; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $game->title }}
                                </span>
                                <span style="flex: none; font-size: 0.75rem; opacity: 0.7; font-variant-numeric: tabular-nums;">
                                    {{ $count }} / {{ $target }}
                                </span>
                            </div>

                            <div style="margin-top: 0.4rem; height: 0.5rem; border-radius: 9999px;
                                        background: rgb(148 163 184 / 0.25); overflow: hidden;">
                                <div style="height: 100%; border-radius: 9999px; width: {{ $percent }}%;
                                            background: {{ $ready ? '#10b981' : '#f59e0b' }};"></div>
                            </div>
                        </div>

                        <span style="flex: none;">
                            @if ($ready)
                                <x-filament::badge color="success">Ready to run</x-filament::badge>
                            @elseif ($game->is_active)
                                <x-filament::badge color="info">Live</x-filament::badge>
                            @else
                                <x-filament::badge color="gray">Coming soon</x-filament::badge>
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
