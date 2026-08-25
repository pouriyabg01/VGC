<div>
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        <div class="max-w-2xl">
            <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-4">For people who bill by the hour, not the meeting</p>
            <h1 class="font-display text-5xl sm:text-6xl leading-[1.05] tracking-tight text-frost">
                Track your work in <span class="italic text-mist">lanes</span>,
                not to-do lists.
            </h1>
            <p class="mt-6 text-lg text-mist leading-relaxed">
                Focuslane turns your day into a set of focused lanes — one per client,
                one per project — so you always know where your hours actually went.
            </p>
            <div class="mt-8 flex items-center gap-4">
                <a href="{{ route('register') }}" wire:navigate
                   class="bg-neon text-void px-6 py-3 rounded-sm hover:bg-neon/90 transition-colors">
                    Start tracking free
                </a>
                <a href="{{ route('login') }}" wire:navigate class="text-mist hover:text-frost transition-colors text-sm">
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
                    <span class="w-44 shrink-0 text-sm text-mist truncate">{{ $lane['label'] }}</span>
                    <div class="flex-1 h-2 bg-steel rounded-full overflow-hidden">
                        <div class="h-full bg-plasma rounded-full" style="width: {{ $lane['pct'] }}%"></div>
                    </div>
                    <span class="w-16 shrink-0 text-right font-mono text-xs text-mist">{{ $lane['time'] }}</span>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Tournaments --}}
    <section class="border-t border-steel">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-10">
                <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Tournaments</p>
                <h2 class="font-display text-3xl text-frost">All tournaments.</h2>
            </div>

            @if ($tournaments->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tournaments as $tournament)
                        <a href="{{ route('tournament', $tournament) }}" wire:navigate class="block group">
                        <article class="flex min-h-64 flex-col bg-carbon border border-steel rounded-sm p-8 transition-colors group-hover:border-neon/60" wire:key="landing-tournament-{{ $tournament->id }}">
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-bold text-xl text-plasma">#{{ $tournament->platform }}</span>
                                <span class="font-mono text-xs uppercase text-mist">{{ $tournament->status->value }}</span>
                            </div>

                            <div class="mt-5 flex-1">
                                <h3 class="font-display text-2xl text-frost">{{ $tournament->game }}</h3>
                                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-mist">Players</dt>
                                        <dd class="mt-1 text-frost">{{ $tournament->players_count }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-mist">Matches</dt>
                                        <dd class="mt-1 text-frost">
                                        @if($tournament->status === \App\Enums\Tournaments\TournamentEnum::COMPLETED)
                                            Done
                                        @else
                                            {{ count($tournament->matches()->latestRound()) }}
                                        @endif
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="mt-8 border-t border-steel pt-5 text-sm text-mist">
                                @if ($tournament->winner)
                                    Winner: <span class="text-frost">{{ $tournament->winner->name }}</span>
                                @elseif ($tournament->end_at)
                                    Ends: <span class="text-frost">{{ $tournament->end_at->format('M j, Y') }}</span>
                                @else
                                    Waiting for results
                                @endif
                            </div>
                        </article>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="border border-dashed border-steel bg-carbon px-6 py-12 text-center">
                    <h3 class="font-display text-2xl text-frost">No tournaments yet.</h3>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-mist">
                        Created tournaments will appear here on the landing page.
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- Plans --}}
    <section class="border-t border-steel bg-void">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-10">
                <div>
                    <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Plans</p>
                    <h2 class="font-display text-3xl text-frost">Choose the lane that fits your work.</h2>
                </div>
            </div>

            @if ($plans->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        <article class="flex min-h-64 flex-col bg-carbon border border-steel rounded-sm p-8 transition-colors group-hover:border-neon/60" wire:key="landing-plan-{{ $plan->id }}">
                            <div class="flex-1">
                                <h3 class="font-display text-2xl text-frost">{{ $plan->title }}</h3>
                                <p class="mt-4 text-sm leading-6 text-mist">
                                    {{ Str::limit($plan->description, 130) }}
                                </p>
                            </div>

                            <div class="mt-8 border-t border-steel pt-6">
                                <p class="font-mono text-xs uppercase tracking-widest text-mist">Starting at</p>
                                <p class="mt-2 font-display text-4xl text-frost">
                                    ${{ number_format($plan->price) }}
                                </p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="border border-dashed border-steel bg-carbon px-6 py-12 text-center">
                    <h3 class="font-display text-2xl text-frost">No plans published yet.</h3>
                    <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-mist">
                        Plans created in the admin panel will appear here on the landing page.
                    </p>
                </div>
            @endif
        </div>
    </section>

    {{-- CTA --}}
    <section class="border-t border-steel bg-void">
        <div class="mx-auto max-w-6xl px-6 py-20 text-center">
            <h2 class="font-display text-3xl sm:text-4xl text-frost mb-4">Open your first lane today.</h2>
            <p class="text-mist mb-8 max-w-md mx-auto">Free while you're the only one on your team. No card required.</p>
            <a href="{{ route('register') }}" wire:navigate
               class="inline-block bg-plasma text-void px-6 py-3 rounded-sm hover:bg-plasma/90 transition-colors font-medium">
                Create your account
            </a>
        </div>
    </section>
</div>
