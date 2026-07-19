<div>
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        <div class="max-w-2xl">
            <p class="font-mono text-xs tracking-widest uppercase text-brass mb-4">For people who bill by the hour, not the meeting</p>
            <h1 class="font-display text-5xl sm:text-6xl leading-[1.05] tracking-tight text-ink">
                Track your work in <span class="italic text-slate">lanes</span>,
                not to-do lists.
            </h1>
            <p class="mt-6 text-lg text-ink/70 leading-relaxed">
                Focuslane turns your day into a set of focused lanes — one per client,
                one per project — so you always know where your hours actually went.
            </p>
            <div class="mt-8 flex items-center gap-4">
                <a href="{{ route('register') }}" wire:navigate
                   class="bg-ink text-cream px-6 py-3 rounded-sm hover:bg-ink/90 transition-colors">
                    Start tracking free
                </a>
                <a href="{{ route('login') }}" wire:navigate class="text-ink/70 hover:text-ink transition-colors text-sm">
                    I already have an account &rarr;
                </a>
            </div>
        </div>

        {{-- Signature element: live-looking lane bars --}}
        <div class="mt-16 space-y-3">
            @foreach ([
                ['label' => 'Client — Redwood Studio', 'pct' => 82, 'time' => '3h 24m'],
                ['label' => 'Client — North & Co.', 'pct' => 54, 'time' => '2h 01m'],
                ['label' => 'Internal — Admin', 'pct' => 21, 'time' => '0h 47m'],
            ] as $lane)
                <div class="flex items-center gap-4">
                    <span class="w-44 shrink-0 text-sm text-ink/70 truncate">{{ $lane['label'] }}</span>
                    <div class="flex-1 h-2 bg-ink/10 rounded-full overflow-hidden">
                        <div class="h-full bg-brass rounded-full" style="width: {{ $lane['pct'] }}%"></div>
                    </div>
                    <span class="w-16 shrink-0 text-right font-mono text-xs text-slate">{{ $lane['time'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Tournaments --}}
    <section class="border-t border-ink/10">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-10">
                <p class="font-mono text-xs tracking-widest uppercase text-brass mb-3">Tournaments</p>
                <h2 class="font-display text-3xl text-ink">All tournaments.</h2>
            </div>

            @if ($tournaments->isNotEmpty())
                <div class="grid gap-px overflow-hidden rounded-sm bg-ink/10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tournaments as $tournament)
                        <a href="{{ route('tournament', $tournament) }}" wire:navigate class="block hover:bg-ink/[0.02] transition-colors">
                        <article class="flex min-h-64 flex-col bg-cream p-8" wire:key="landing-tournament-{{ $tournament->id }}">
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-bold text-xl text-brass">#{{ $tournament->platform }}</span>
                                <span class="font-mono text-xs uppercase text-slate">{{ $tournament->status->value }}</span>
                            </div>

                            <div class="mt-5 flex-1">
                                <h3 class="font-display text-2xl text-ink">{{ $tournament->game }}</h3>
                                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-slate">Players</dt>
                                        <dd class="mt-1 text-ink">{{ $tournament->players_count }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-slate">Matches</dt>
                                        <dd class="mt-1 text-ink">
                                        @if($tournament->status === \App\Enums\Tournaments\TournamentEnum::COMPLETED)
                                            Done
                                        @else
                                            {{ count($tournament->matches()->latestRound()) }}
                                        @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="mt-8 border-t border-ink/10 pt-5 text-sm text-ink/70">
                                @if ($tournament->winner)
                                    Winner: <span class="text-ink">{{ $tournament->winner->name }}</span>
                                @elseif ($tournament->end_at)
                                    Ends: <span class="text-ink">{{ $tournament->end_at->format('M j, Y') }}</span>
                                @else
                                    Waiting for results
                                @endif
                            </div>
                        </article>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="border border-dashed border-ink/20 bg-cream px-6 py-12 text-center">
                    <h3 class="font-display text-2xl text-ink">No tournaments yet.</h3>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-ink/70">
                        Created tournaments will appear here on the landing page.
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- Plans --}}
    <section class="border-t border-ink/10 bg-white">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-10">
                <div>
                    <p class="font-mono text-xs tracking-widest uppercase text-brass mb-3">Plans</p>
                    <h2 class="font-display text-3xl text-ink">Choose the lane that fits your work.</h2>
                </div>
            </div>

            @if ($plans->isNotEmpty())
                <div class="grid gap-px overflow-hidden rounded-sm bg-ink/10 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        <article class="flex min-h-64 flex-col bg-white p-8" wire:key="landing-plan-{{ $plan->id }}">
                            <div class="flex-1">
                                <h3 class="font-display text-2xl text-ink">{{ $plan->title }}</h3>
                                <p class="mt-4 text-sm leading-6 text-ink/70">
                                    {{ Str::limit($plan->description, 130) }}
                                </p>
                            </div>

                            <div class="mt-8 border-t border-ink/10 pt-6">
                                <p class="font-mono text-xs uppercase tracking-widest text-slate">Starting at</p>
                                <p class="mt-2 font-display text-4xl text-ink">
                                    ${{ number_format($plan->price) }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="border border-dashed border-ink/20 bg-cream px-6 py-12 text-center">
                    <h3 class="font-display text-2xl text-ink">No plans published yet.</h3>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-ink/70">
                        Plans created in the admin panel will appear here on the landing page.
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-ink/10 bg-ink">
        <div class="mx-auto max-w-6xl px-6 py-20 text-center">
            <h2 class="font-display text-3xl sm:text-4xl text-cream mb-4">Open your first lane today.</h2>
            <p class="text-cream/60 mb-8 max-w-md mx-auto">Free while you're the only one on your team. No card required.</p>
            <a href="{{ route('register') }}" wire:navigate
               class="inline-block bg-brass text-ink px-6 py-3 rounded-sm hover:bg-brass/90 transition-colors font-medium">
                Create your account
            </a>
        </div>
    </section>
</div>
