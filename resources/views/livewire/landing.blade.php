<div>
    {{-- Hero --}}
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        <div class="max-w-2xl">
            <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-4">Bracket tournaments for players who came to win</p>
            <h1 class="font-display text-5xl sm:text-6xl leading-[1.05] tracking-tight text-frost">
                Enter the bracket.<br>
                <span class="text-plasma">Take the crown.</span>
            </h1>
            <p class="mt-6 text-lg text-mist leading-relaxed">
                Single-elimination tournaments on PC, PlayStation, Xbox and mobile.
                Sign up with your gamertag, get drawn into the bracket, and play your way to the final.
            </p>
            <div class="mt-8 flex items-center gap-4">
                @auth
                    {{-- Already in: an account CTA is noise, so point at the
                         thing they came for. --}}
                    <a href="#tournaments"
                       class="bg-neon text-void px-6 py-3 rounded-sm hover:bg-neon/90 transition-colors">
                        View tournaments
                    </a>
                @else
                    <a href="{{ route('register') }}" wire:navigate
                       class="bg-neon text-void px-6 py-3 rounded-sm hover:bg-neon/90 transition-colors">
                        Create your account
                    </a>
                    <a href="{{ route('login') }}" wire:navigate class="text-mist hover:text-frost transition-colors text-sm">
                        I already have an account &rarr;
                    </a>
                @endauth
            </div>
        </div>

        {{-- Signature element: the three games players are asking for most.
             Was three hardcoded rows; it is now the same records the Games
             section votes on, so the bars move when people vote. --}}
        @if ($mostWanted->isNotEmpty())
            <div class="mt-16 space-y-3">
                <p class="font-mono text-xs tracking-widest uppercase text-mist mb-4">Most wanted</p>
                @foreach ($mostWanted as $game)
                    <div class="flex items-center gap-4" wire:key="hero-game-{{ $game->id }}">
                        <span class="w-44 shrink-0 text-sm text-mist truncate">{{ $game->title }}</span>
                        <div class="flex-1 h-2 bg-steel rounded-full overflow-hidden">
                            <div class="h-full bg-plasma rounded-full transition-[width] duration-500"
                                 style="width: {{ $game->votePercent() }}%"></div>
                        </div>
                        <span class="w-20 shrink-0 text-right font-mono text-xs text-mist">
                            {{-- A row of zeros reads as a dead poll, so an
                                 unvoted game asks for the first vote instead. --}}
                            @if ($game->voteCount() > 0)
                                {{ $game->voteCount() }}/{{ $game->votes_target }}
                            @else
                                Be first
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Tournaments --}}
    {{-- Games. A shelf for the catalogue: cover and title only, and nothing
         links out yet, because no tournament points at a game record. --}}
    @if ($games->isNotEmpty())
        <section class="border-t border-steel">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="mb-10">
                    <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Games</p>
                    <h2 class="font-display text-3xl text-frost">
                        What we run.
                        {{-- The catalogue is on show before anything runs on it:
                             no tournament points at a game record yet. --}}
                        <span class="animate-blink align-middle ml-2 font-mono text-xs uppercase tracking-widest text-plasma border border-plasma/40 bg-plasma/10 rounded-sm px-2 py-1">
                            Coming soon
                        </span>
                    </h2>
                </div>

                <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($games as $game)
                        <article class="bg-carbon border border-steel rounded-sm overflow-hidden"
                                 wire:key="landing-game-{{ $game->id }}">
                            <div class="relative aspect-[3/4] bg-void">
                                @if ($game->imageUrl())
                                    <img src="{{ $game->imageUrl() }}"
                                         alt="{{ $game->title }}"
                                         loading="lazy"
                                         class="h-full w-full object-cover">
                                @else
                                    {{-- No cover uploaded: hold the shape rather than
                                         collapsing the card or showing a broken image. --}}
                                    <div class="flex h-full items-center justify-center">
                                        <span class="font-mono text-xs uppercase tracking-widest text-mist">No cover</span>
                                    </div>
                                @endif
                            </div>
                            <div class="p-4 space-y-3">
                                <h3 class="font-display text-sm text-frost truncate">{{ $game->title }}</h3>

                                <div class="space-y-1.5">
                                    <div class="h-1.5 bg-steel rounded-full overflow-hidden">
                                        <div class="h-full bg-plasma rounded-full transition-[width] duration-500"
                                             style="width: {{ $game->votePercent() }}%"></div>
                                    </div>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-mist">
                                        @if ($game->voteCount() > 0)
                                            {{ $game->voteCount() }}/{{ $game->votes_target }} want this
                                        @else
                                            Nobody has asked yet
                                        @endif
                                    </p>
                                </div>

                                @php($voted = (bool) ($game->voted_by_viewer ?? false))

                                <button
                                    type="button"
                                    wire:click="toggleVote({{ $game->id }})"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleVote({{ $game->id }})"
                                    aria-pressed="{{ $voted ? 'true' : 'false' }}"
                                    class="w-full font-mono text-[10px] uppercase tracking-widest px-3 py-2 border rounded-sm transition-colors
                                        {{ $voted
                                            ? 'text-plasma border-plasma/40 bg-plasma/10 hover:bg-plasma/20'
                                            : 'text-mist border-steel hover:text-frost hover:border-neon/60' }}">
                                    {{ $voted ? "\u{2713} You're in" : "I'd play this" }}
                                </button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section id="tournaments" class="border-t border-steel scroll-mt-8">
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
                                        <dd class="mt-1 text-frost">{{ $tournament->matchesLabel() }}</dd>
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
    <section id="plans" class="border-t border-steel bg-void scroll-mt-8">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-10">
                <div>
                    <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Plans</p>
                    <h2 class="font-display text-3xl text-frost">Pick the pass that fits your run.</h2>
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

                                <a href="{{ route('checkout', $plan) }}" wire:navigate
                                   class="mt-6 block w-full text-center bg-neon text-void px-6 py-3 rounded-sm font-mono text-xs uppercase tracking-widest transition-colors hover:bg-neon/90">
                                    Get {{ $plan->title }}
                                </a>
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
            <h2 class="font-display text-3xl sm:text-4xl text-frost mb-4">Your first bracket is waiting.</h2>
            <p class="text-mist mb-8 max-w-md mx-auto">Grab a pass, add your gamertag, and enter your first tournament.</p>
            {{-- Sending somebody who is already signed in to the register page
                 is a dead end. The copy asks them to grab a pass, so that is
                 where the button goes. --}}
            @auth
                <a href="{{ route('plans') }}" wire:navigate
                   class="inline-block bg-plasma text-void px-6 py-3 rounded-sm hover:bg-plasma/90 transition-colors font-medium">
                    Browse plans
                </a>
            @else
                <a href="{{ route('register') }}" wire:navigate
                   class="inline-block bg-plasma text-void px-6 py-3 rounded-sm hover:bg-plasma/90 transition-colors font-medium">
                    Create your account
                </a>
            @endauth
        </div>
    </section>
</div>
