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
        </div>

        {{-- The bracket and the call to action on one line: the diagram leads,
             the buttons sit beside it. --}}
        <div class="mt-10 flex flex-col gap-10 lg:flex-row lg:items-center lg:gap-12">
            <div>
                {{-- Signature element: the shape of the thing itself. A four-seat
                     bracket walking to the trophy, drawn in CSS rather than shipped
                     as an image. The seats are an illustration, not data — the real
                     brackets are further down the page.

                     The geometry is justify-around doing the arithmetic: in a
                     column of fixed height, four seats sit at 12.5/37.5/62.5/87.5%
                     and two seats at 25/75%, so every round already lands on the
                     midpoint of its pair. That is why the joiners are plain
                     fixed-height lines.

                     Three fixed columns are wider than a small phone, so the strip
                     scrolls in its own box rather than pushing the page sideways. --}}
                <div class="-mx-6 overflow-x-auto px-6 pb-2 lg:mx-0 lg:px-0">
                <div class="flex h-56 w-max gap-6 text-sm select-none" aria-hidden="true">
                    {{-- Round of four --}}
                    <div class="flex w-28 sm:w-36 flex-col justify-around">
                        @foreach (['Nova', 'Krieg', 'Sable', 'Riot'] as $seat => $name)
                            <div style="animation-delay: {{ $seat * 100 }}ms"
                                 class="animate-bracket-in relative flex h-9 items-center rounded-sm border border-steel bg-carbon px-3 text-mist
                                        after:absolute after:left-full after:top-1/2 after:w-3 after:border-t after:border-steel">
                                {{ $name }}
                            </div>
                        @endforeach
                    </div>

                    {{-- Semi-finals --}}
                    <div class="flex w-28 sm:w-36 flex-col justify-around">
                        @foreach (['Nova', 'Riot'] as $seat => $name)
                            <div style="animation-delay: {{ 400 + $seat * 100 }}ms"
                                 class="animate-bracket-in relative flex h-9 items-center rounded-sm border border-steel bg-carbon px-3 text-frost
                                        before:absolute before:right-full before:top-1/2 before:w-3 before:border-t before:border-steel">
                                {{-- Joins the two seats of the pair: 56px is the
                                     distance between their centres. --}}
                                <span class="absolute right-full top-1/2 mr-3 h-14 -translate-y-1/2 border-l border-steel"></span>
                                {{ $name }}
                            </div>
                        @endforeach
                    </div>

                    {{-- The final --}}
                    <div class="flex w-32 sm:w-40 flex-col justify-around">
                        <div style="animation-delay: 650ms"
                             class="animate-bracket-in relative flex h-9 items-center gap-2 rounded-sm border border-plasma/50 bg-plasma/10 px-3 font-display text-plasma
                                    before:absolute before:right-full before:top-1/2 before:w-3 before:border-t before:border-steel">
                            <span class="absolute right-full top-1/2 mr-3 h-28 -translate-y-1/2 border-l border-steel"></span>
                            {{-- The glow is its own layer: the seat is already
                                 spending its one animation slot arriving. --}}
                            <span class="animate-crown pointer-events-none absolute inset-0 rounded-sm"></span>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                                 stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 shrink-0">
                                <path d="M7 4h10v4.5a5 5 0 0 1-10 0V4Z"/>
                                <path d="M7 6H5.2a2.2 2.2 0 0 0 0 4.4H7M17 6h1.8a2.2 2.2 0 0 1 0 4.4H17"/>
                                <path d="M12 13.5V17M9 20h6"/>
                            </svg>
                            Champion
                        </div>
                    </div>
                </div>
                </div>
            </div>

            <div class="flex shrink-0 items-center gap-4">
                @auth
                    {{-- Already in: an account CTA is noise, so point at the
                         thing they came for. --}}
                    <a href="{{ route('tournaments') }}" wire:navigate
                       class="bg-neon text-void px-6 py-3 rounded-sm hover:bg-neon/90 transition-colors">
                        View tournaments
                    </a>
                @else
                    <a href="{{ route('register') }}" wire:navigate
                       class="bg-neon text-void px-6 py-3 rounded-sm hover:bg-neon/90 transition-colors">
                        Create your account
                    </a>
                    {{-- Signing up before seeing what is on is a leap of faith,
                         so the second link shows the draws. Log in still sits
                         in the header for anybody coming back. --}}
                    <a href="{{ route('tournaments') }}" wire:navigate
                       class="text-mist hover:text-frost transition-colors text-sm">
                        View tournaments &rarr;
                    </a>
                @endauth
            </div>
        </div>
    </section>

    {{-- Games. Two states in one shelf: what is on now, and what the poll is
         asking for. Nothing links out yet — no tournament points at a game
         record. --}}
    @if ($games->isNotEmpty())
        <section class="border-t border-steel">
            <div class="mx-auto max-w-6xl px-6 py-20">
                <div class="mb-10">
                    <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Games</p>
                    <h2 class="font-display text-3xl text-frost">What we run.</h2>
                    @php($live = $games->where('is_active', true))
                    <p class="mt-3 max-w-xl text-sm leading-6 text-mist">
                        {{-- A badge over the whole shelf used to say coming
                             soon, which is no longer true of all of it. Read
                             off the records so flipping a game live in the
                             panel cannot leave this sentence lying. --}}
                        @if ($live->isNotEmpty())
                            {{ $live->pluck('title')->join(', ', ' and ') }}
                            {{ $live->count() === 1 ? 'is' : 'are' }} on now &mdash; say which one you play.
                            The rest open for votes as we put them on.
                        @else
                            Nothing is on yet. Every title below opens for votes the day it lands.
                        @endif
                    </p>
                </div>

                {{-- Most wanted. It used to sit in the hero, where it read as
                     a claim about the site rather than as the poll running
                     right here. Above the shelf rather than under it: it is the
                     scoreboard for the cards below, and nobody scrolls past
                     eight covers to find out there was one. --}}
                @if ($mostWanted->isNotEmpty())
                    <div class="mb-12 border border-steel bg-carbon rounded-sm p-6 sm:p-8">
                        <div class="flex flex-wrap items-baseline justify-between gap-3 mb-6">
                            <p class="font-mono text-xs tracking-widest uppercase text-plasma">Most wanted</p>
                            <p class="font-mono text-xs uppercase tracking-widest text-mist">What players are asking for</p>
                        </div>

                        <div class="space-y-3">
                            @foreach ($mostWanted as $rank => $game)
                                <div class="flex items-center gap-4" wire:key="most-wanted-{{ $game->id }}">
                                    <span class="w-6 shrink-0 font-mono text-xs text-mist">{{ str_pad($rank + 1, 2, '0', STR_PAD_LEFT) }}</span>
                                    <span class="w-40 shrink-0 truncate text-sm text-mist">{{ $game->title }}</span>
                                    <div class="h-2 flex-1 overflow-hidden rounded-full bg-steel">
                                        <div class="h-full rounded-full bg-plasma transition-[width] duration-500"
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
                    </div>
                @endif

                <div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($games as $game)
                        <article class="bg-carbon border border-steel rounded-sm overflow-hidden"
                                 wire:key="landing-game-{{ $game->id }}">
                            <div class="relative aspect-[3/4] bg-void">
                                {{-- Coming soon wears the champion seat's own
                                     colours, glow included, so it carries the
                                     card instead of sitting quietly in a
                                     corner. What tells the two apart is the
                                     glow and the held-back cover under it. --}}
                                <span class="absolute top-2 left-2 z-10 font-mono text-[10px] uppercase tracking-widest px-2 py-1 rounded-sm border backdrop-blur-sm
                                    {{ $game->is_active
                                        ? 'text-plasma border-plasma/40 bg-void/70'
                                        : 'animate-glow text-plasma border-plasma/50 bg-plasma/10' }}">
                                    {{ $game->is_active ? 'Live' : 'Coming soon' }}
                                </span>
                                @if ($game->imageUrl())
                                    {{-- A cover for something nobody can play
                                         yet is held back over the void
                                         background, so the badge on top of it
                                         is the first thing read. --}}
                                    <img src="{{ $game->imageUrl() }}"
                                         alt="{{ $game->title }}"
                                         loading="lazy"
                                         class="h-full w-full object-cover {{ $game->is_active ? '' : 'opacity-40' }}">
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
                                        @elseif ($game->acceptsVotes())
                                            Nobody has asked yet
                                        @else
                                            Not open for votes yet
                                        @endif
                                    </p>
                                </div>

                                @php($voted = (bool) ($game->voted_by_viewer ?? false))

                                @if ($game->acceptsVotes())
                                    <button
                                        type="button"
                                        wire:click="toggleVote({{ $game->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleVote({{ $game->id }})"
                                        aria-pressed="{{ $voted ? 'true' : 'false' }}"
                                        title="{{ $voted ? 'Press again to take it back' : 'Tells us which games to put on more of' }}"
                                        class="w-full font-mono text-[10px] uppercase tracking-widest px-3 py-2 border rounded-sm transition-colors
                                            {{ $voted
                                                ? 'text-plasma border-plasma/40 bg-plasma/10 hover:bg-plasma/20'
                                                : 'text-mist border-steel hover:text-frost hover:border-neon/60' }}">
                                        {{-- "You're in" read as a seat in a
                                             tournament. A like enters nothing:
                                             it tells the people running the site
                                             what to put on. --}}
                                        {{ $voted ? "\u{2713} Liked" : 'Like to play' }}
                                    </button>
                                @else
                                    {{-- Held out rather than left off: a card
                                         with nothing where its neighbours have a
                                         button reads as broken, and the greyed
                                         one says the vote opens later. --}}
                                    <button type="button" disabled aria-disabled="true"
                                            title="Voting opens when this game goes live"
                                            class="w-full cursor-not-allowed font-mono text-[10px] uppercase tracking-widest px-3 py-2 border border-steel/60 text-mist/50 rounded-sm">
                                        Votes open at launch
                                    </button>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>

            </div>
        </section>
    @endif

    <section id="tournaments" class="border-t border-steel scroll-mt-8">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <div class="mb-10 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Tournaments</p>
                    {{-- A taste, not the catalogue: the full list has its own
                         page, with the filters that make a long list usable. --}}
                    <h2 class="font-display text-3xl text-frost">Latest tournaments.</h2>
                </div>
                <a href="{{ route('tournaments') }}" wire:navigate
                   class="text-sm text-mist hover:text-frost transition-colors">
                    View all tournaments &rarr;
                </a>
            </div>

            @if ($tournaments->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($tournaments as $tournament)
                        <x-tournament-card :tournament="$tournament" wire:key="landing-tournament-{{ $tournament->id }}" />
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

            @php($active = $this->activeSubscription())

            @if ($plans->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        @php($isCurrent = $active && $active->id === $plan->id)
                        {{-- Only the button is a link. The card itself just
                             lifts on hover; making the whole thing clickable
                             put a second destination on a card that already
                             had one. --}}
                        <article class="flex min-h-64 flex-col rounded-sm border p-8 transition-colors
                                {{ $isCurrent ? 'bg-carbon border-plasma/50' : 'bg-carbon border-steel hover:border-neon/60' }}"
                                 wire:key="landing-plan-{{ $plan->id }}">
                            <div class="flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <h3 class="font-display text-2xl text-frost">{{ $plan->title }}</h3>
                                    @if ($isCurrent)
                                        <span class="shrink-0 font-mono text-[10px] uppercase tracking-widest text-plasma border border-plasma/40 bg-plasma/10 px-2 py-1">
                                            Active
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-4 text-sm leading-6 text-mist">
                                    {{ Str::limit($plan->description, 130) }}
                                </p>

                                <ul class="mt-5 space-y-2 text-sm text-mist">
                                    <li class="flex items-baseline gap-2">
                                        <span class="text-plasma">&#9656;</span>
                                        <span><span class="text-frost">{{ $plan->tournament_entries }}</span>
                                            tournament {{ \Illuminate\Support\Str::plural('entry', $plan->tournament_entries) }}</span>
                                    </li>
                                    <li class="flex items-baseline gap-2">
                                        <span class="text-plasma">&#9656;</span>
                                        <span><span class="text-frost">{{ $plan->vs_games }}</span>
                                            VS {{ \Illuminate\Support\Str::plural('game', $plan->vs_games) }}</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="mt-8 border-t border-steel pt-6">
                                <p class="font-mono text-xs uppercase tracking-widest text-mist">Starting at</p>
                                <p class="mt-2 font-display text-3xl text-frost">
                                    {{ number_format($plan->price) }}<span class="ml-2 text-base text-mist">Toman</span>
                                </p>

                                @if ($isCurrent)
                                    <a href="{{ route('profile') }}" wire:navigate
                                       class="mt-6 block w-full text-center font-mono text-xs uppercase tracking-widest text-plasma border border-plasma/40 px-6 py-3 rounded-sm hover:bg-plasma/10 transition-colors">
                                        Your current pass
                                    </a>
                                @elseif ($active)
                                    {{-- Holding another pass: only one runs at a time, so say it here. --}}
                                    <p class="mt-6 text-center font-mono text-[10px] uppercase tracking-widest text-mist">
                                        {{ $active->title }} is active
                                    </p>
                                @else
                                    <a href="{{ route('checkout', $plan) }}" wire:navigate
                                       class="mt-6 block w-full text-center bg-neon text-void px-6 py-3 rounded-sm font-mono text-xs uppercase tracking-widest transition-colors hover:bg-neon/90">
                                        Get {{ $plan->title }}
                                    </a>
                                @endif
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
