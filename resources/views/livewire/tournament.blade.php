<div>
    <section class="mx-auto max-w-6xl px-6 pt-16 pb-10">
        <a href="{{ route('home') }}" wire:navigate class="font-mono text-xs uppercase tracking-widest text-slate hover:text-ink transition-colors">
            &larr; All tournaments
        </a>

        <div class="mt-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="font-mono text-xs tracking-widest uppercase text-brass mb-3">#{{ $tournament->platform }}</p>
                <h1 class="font-display text-4xl sm:text-5xl text-ink tracking-tight">{{ $tournament->game }}</h1>
            </div>
            <div class="flex flex-col items-end gap-3">
                <span class="font-mono text-xs uppercase text-slate border border-ink/10 px-3 py-2">{{ $tournament->status->value }}</span>

                @if ($tournament->status === \App\Enums\Tournaments\TournamentEnum::PENDING)
                    @auth
                        @if ($this->isSignedUp())
                            <span>signed in</span>
{{--                            TODO tournament should not be leaveable--}}
{{--                            <button--}}
{{--                                type="button"--}}
{{--                                wire:click="signOut"--}}
{{--                                wire:target="signOut"--}}
{{--                                class="font-mono text-xs text-slate border border-ink/10 px-3 py-2"--}}
{{--                            >Sign Out--}}
{{--                            </button>--}}
                        @elseif ($this->canSignUp())
                            <button
                                type="button"
                                wire:click="signUp"
                                wire:loading.attr="disabled"
                                wire:target="signUp"
                                class="font-mono text-xs uppercase text-slate border border-ink/10 px-3 py-2"
                            >
                                <span wire:loading.remove wire:target="signUp">Sign up</span>
                                <span wire:loading wire:target="signUp">Signing up&hellip;</span>
                            </button>
                        @else
                            <p class="text-sm text-ink/70">You need an active subscription to sign up.</p>
                        @endif
                    @else
                        <a href="{{ route('login') }}" wire:navigate
                           class="font-mono text-xs uppercase text-slate border border-ink/10 px-3 py-2">
                            Sign up
                        </a>
                    @endauth
                @endif

                @if (session()->has('message'))
                    <span class="font-mono text-xs text-green-600">{{ session('message') }}</span>
                @endif
                @error('signUp')
                    <span class="font-mono text-xs text-red-500">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <dl class="mt-10 grid grid-cols-2 gap-6 sm:grid-cols-4 text-sm">
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-slate">Players</dt>
                <dd class="mt-1 text-ink">{{ $tournament->players->count() }}</dd>
            </div>
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-slate">Matches</dt>
                <dd class="mt-1 text-ink">{{ $tournament->matches->count() }}</dd>
            </div>
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-slate">Winner</dt>
                <dd class="mt-1 text-ink">{{ $tournament->winner?->name ?? '—' }}</dd>
            </div>
            <div>
                <dt class="font-mono text-xs uppercase tracking-widest text-slate">Ends</dt>
                <dd class="mt-1 text-ink">{{ $tournament->end_at?->format('M j, Y') ?? '—' }}</dd>
            </div>
        </dl>
    </section>

    <section class="border-t border-ink/10">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h2 class="font-display text-2xl text-ink mb-8">Players</h2>

            @if ($tournament->players->isNotEmpty())
                <ul class="grid gap-px overflow-hidden rounded-sm bg-ink/10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tournament->players as $player)
                        <li class="bg-cream px-6 py-5" wire:key="player-{{ $player->id }}">
                            <p class="font-display text-lg text-ink">{{ $player->name }}</p>
                            <p class="mt-1 font-mono text-xs text-slate">{{ $player->platform?->plaform }}</p>
                            <p class="mt-1 font-mono text-xs text-slate">{{ $player->platform?->nickname }}</p>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="text-sm text-ink/70">No players signed up yet.</p>
            @endif
        </div>
    </section>

    <section class="border-t border-ink/10">
        <div class="mx-auto max-w-6xl px-6 py-16">
            <h2 class="font-display text-2xl text-ink mb-8">Matches</h2>

            @if ($tournament->matches->isNotEmpty())
                @php
                    $matchesByRound = $tournament->matches->sortBy('round')->groupBy('round');
                @endphp
                <div class="space-y-12">
                    @foreach ($matchesByRound as $round => $matches)
                        <div wire:key="round-{{ $round }}">
                            <h3 class="font-display text-xl text-ink mb-4">Round {{ $round }}</h3>
                            <div class="space-y-px overflow-hidden rounded-sm bg-ink/10">
                                @foreach ($matches as $match)
                                    <article class="bg-cream px-6 py-5 flex flex-wrap items-center justify-between gap-4" wire:key="match-{{ $match->id }}">
                                        <div class="flex flex-wrap items-center gap-6 text-ink">
                                            <div>
                                                <p class="font-display text-lg">{{ $match->player1?->name ?? 'TBD' }}</p>
                                                <p class="mt-1 font-mono text-xs uppercase tracking-widest text-slate">
                                                    Goal: {{ $match->player1_goal ?? '—' }}
                                                </p>
                                            </div>
                                            <span class="font-mono text-xs uppercase text-ink/40">vs</span>
                                            <div>
                                                <p class="font-display text-lg">{{ $match->player2?->name ?? 'TBD' }}</p>
                                                <p class="mt-1 font-mono text-xs uppercase tracking-widest text-slate">
                                                    Goal: {{ $match->player2_goal ?? '—' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right text-sm">
                                            <p class="font-mono text-xs uppercase text-slate">{{ $match->status->value ?? 'pending' }}</p>
                                            @if ($match->winner)
                                                <p class="mt-1 text-ink">Winner: {{ $match->winner->name }}</p>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-ink/70">No matches scheduled yet.</p>
            @endif
        </div>
    </section>
</div>
