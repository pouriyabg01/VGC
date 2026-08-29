<div>
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Plans</p>
        <h1 class="font-display text-4xl sm:text-5xl text-frost tracking-tight">
            Pick the pass that fits your run.
        </h1>
        <p class="mt-4 max-w-xl text-sm leading-6 text-mist">
            A pass is what lets you enter tournaments. One runs at a time.
        </p>

        @php $active = $this->activeSubscription(); @endphp

        @if ($plans->isNotEmpty())
            <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($plans as $plan)
                    @php $isCurrent = $active && $active->id === $plan->id; @endphp
                    <article wire:key="plan-{{ $plan->id }}"
                             class="flex min-h-72 flex-col rounded-sm border p-8 transition-colors
                                    {{ $isCurrent ? 'bg-carbon border-plasma/50' : 'bg-carbon border-steel hover:border-neon/60' }}">
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h2 class="font-display text-2xl text-frost">{{ $plan->title }}</h2>
                                @if ($isCurrent)
                                    <span class="shrink-0 font-mono text-[10px] uppercase tracking-widest text-plasma border border-plasma/40 bg-plasma/10 px-2 py-1">
                                        Active
                                    </span>
                                @endif
                            </div>
                            @if ($plan->description)
                                <p class="mt-4 text-sm leading-6 text-mist">{{ $plan->description }}</p>
                            @endif
                        </div>

                            {{-- What the pass actually buys. This is the product,
                                 so it sits above the price rather than in the
                                 description where it can be skimmed past. --}}
                            <ul class="mt-6 space-y-2 text-sm text-mist">
                                <li class="flex items-baseline gap-2">
                                    <span class="text-plasma">&#9656;</span>
                                    <span><span class="text-frost">{{ $plan->tournament_entries }}</span>
                                        {{ \Illuminate\Support\Str::plural('tournament', $plan->tournament_entries) }}</span>
                                </li>
                                <li class="flex items-baseline gap-2">
                                    <span class="text-plasma">&#9656;</span>
                                    <span><span class="text-frost">{{ $plan->vs_games }}</span>
                                        VS {{ \Illuminate\Support\Str::plural('game', $plan->vs_games) }}</span>
                                </li>
                            </ul>

                        <div class="mt-8 border-t border-steel pt-6">
                            <p class="font-mono text-xs uppercase tracking-widest text-mist">Price</p>
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
            <div class="mt-12 border border-dashed border-steel bg-carbon rounded-sm px-6 py-12 text-center">
                <h2 class="font-display text-2xl text-frost">No plans published yet.</h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-mist">
                    Plans created in the admin panel will appear here.
                </p>
            </div>
        @endif
    </section>
</div>
