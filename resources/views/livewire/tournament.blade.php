<div>
    <section class="mx-auto max-w-6xl px-6 pt-16 pb-10">
        <a href="{{ route('home') }}" wire:navigate class="font-mono text-xs uppercase tracking-widest text-mist hover:text-frost transition-colors">
            &larr; All tournaments
        </a>

        <div class="mt-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">#{{ $tournament->platform }}</p>
                <h1 class="font-display text-4xl sm:text-5xl text-frost tracking-tight">{{ $tournament->game }}</h1>
            </div>
            <div class="flex flex-col items-end gap-3">
                <span class="font-mono text-xs uppercase text-mist border border-steel px-3 py-2">{{ $tournament->status->value }}</span>

                @if ($tournament->status === \App\Enums\Tournaments\TournamentEnum::PENDING)
                    @auth
                        @if ($this->isSignedUp())
                            <span class="font-mono text-xs uppercase text-plasma border border-plasma/40 bg-plasma/10 px-3 py-2">
                                Signed up
                            </span>
                        @elseif ($this->canSignUp())
                            <button
                                type="button"
                                wire:click="signUp"
                                wire:loading.attr="disabled"
                                wire:target="signUp"
                                class="font-mono text-xs uppercase text-mist border border-steel px-3 py-2"
                            >
                                <span wire:loading.remove wire:target="signUp">Sign up</span>
                                <span wire:loading wire:target="signUp">Signing up&hellip;</span>
                            </button>
                        @elseif (! $this->hasActiveSubscription())
                            <p class="max-w-xs text-right text-sm text-mist">
                                You need an active subscription to sign up.
                            </p>
                        @else
                            {{-- Subscribed, but has no account on this tournament's platform. --}}
                            <div class="max-w-xs text-right">
                                <p class="font-mono text-xs uppercase tracking-widest text-ember">
                                    {{ $tournament->platform->label() }} account required
                                </p>
                                <p class="mt-2 text-sm text-mist">
                                    This tournament is played on {{ $tournament->platform->label() }}.
                                    Add that account to your profile to sign up.
                                </p>
                                <a href="{{ route('profile') }}" wire:navigate
                                   class="mt-3 inline-block font-mono text-xs uppercase text-plasma border border-plasma/40 px-3 py-2 hover:bg-plasma/10 transition-colors">
                                    Add {{ $tournament->platform->label() }} account
                                </a>
                            </div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="font-mono text-xs uppercase text-mist border border-steel px-3 py-2">
                            Sign up
                        </a>
                    @endauth
                @endif

                @if (session()->has('message'))
                    <span class="font-mono text-xs text-plasma">{{ session('message') }}</span>
                @endif
                @error('signUp')
                    <span class="font-mono text-xs text-ember">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <dl class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-4 text-sm">
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-mist">Players</dt>
                <dd class="mt-1 text-frost">{{ $tournament->players->count() }}</dd>
            </div>
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-mist">Matches</dt>
                @if($tournament->matches->count() === 1)
                    <dd class="mt-1 text-plasma font-bold">DONE!</dd>
                @else
                    <dd class="mt-1 text-frost">{{ $tournament->matches->count() }}</dd>
                @endif
            </div>
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-mist">Winner</dt>
                <dd class="mt-1 text-frost">{{ $tournament->winner?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-mist">Ends</dt>
                <dd class="mt-1 text-frost">{{ $tournament->end_at?->format('M j, Y') ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="border-t border-steel">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h2 class="font-display text-2xl text-frost mb-8">Players</h2>

            @if ($tournament->players->isNotEmpty())
                <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tournament->players as $player)
                        <li class="bg-carbon border border-steel rounded-sm px-6 py-5" wire:key="player-{{ $player->id }}">
                            <p class="font-display text-lg text-frost">{{ $player->name }}</p>
                            <p class="mt-1 font-mono text-xs text-mist">{{ $player->platform?->platform }}</p>
                            <p class="mt-1 font-mono text-xs text-mist">{{ $player->platform?->nickname }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-mist">No players signed up yet.</p>
            @endif
        </div>
    </section>

    <section class="border-t border-steel">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h2 class="font-display text-2xl text-frost mb-8">Matches</h2>

            @if ($tournament->matches->isNotEmpty())
                @php
                    $matchesByRound = $tournament->matches->sortBy('round')->groupBy('round');
                @endphp
                <div class="space-y-12">
                    @foreach ($matchesByRound as $round => $matches)
                        <div wire:key="round-{{ $round }}">
                            <h3 class="font-display text-xl text-frost mb-4">Round {{ $round }}</h3>
                            <div class="space-y-px overflow-hidden rounded-sm bg-steel">
                                @foreach ($matches as $match)
                                    <article class="bg-carbon px-6 py-5 flex flex-wrap items-center justify-between gap-4" wire:key="match-{{ $match->id }}">
                                        <div class="flex flex-wrap items-center gap-6 text-frost">
                                            <div>
                                                <p class="font-display text-lg">{{ $match->player1?->name ?? 'TBD' }}</p>
                                                <p class="mt-1 font-mono text-xs uppercase tracking-widest text-mist">
                                                    Goal: {{ $match->player1_goal ?? '—' }}
                                                </p>
                                            </div>
                                            <span class="font-mono text-xs uppercase text-mist/60">vs</span>
                                            <div>
                                                <p class="font-display text-lg">{{ $match->player2?->name ?? 'TBD' }}</p>
                                                <p class="mt-1 font-mono text-xs uppercase tracking-widest text-mist">
                                                    Goal: {{ $match->player2_goal ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm">
                                            <p class="font-mono text-xs uppercase text-mist">{{ $match->status->value ?? 'pending' }}</p>
                                            @if ($match->winner)
                                                <p class="mt-1 text-frost">Winner: {{ $match->winner->name }}</p>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-mist">No matches scheduled yet.</p>
            @endif
        </div>
    </section>
</div>
