<div>
    <section class="mx-auto max-w-3xl px-6 pt-16 pb-24">
        <a href="{{ route('home') }}" wire:navigate
           class="font-mono text-xs uppercase tracking-widest text-mist hover:text-frost transition-colors">
            &larr; All plans
        </a>

        <h1 class="mt-8 font-display text-4xl sm:text-5xl text-frost tracking-tight">Checkout</h1>
        <p class="mt-3 text-sm text-mist">Review your pass before confirming.</p>

        {{-- Order summary --}}
        <div class="mt-10 bg-carbon border border-steel rounded-sm">
            <div class="flex items-start justify-between gap-6 p-8">
                <div>
                    <p class="font-mono text-xs uppercase tracking-widest text-plasma">Pass</p>
                    <h2 class="mt-2 font-display text-2xl text-frost">{{ $plan->title }}</h2>
                    @if ($plan->description)
                        <p class="mt-3 max-w-md text-sm leading-6 text-mist">{{ $plan->description }}</p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="font-mono text-xs uppercase tracking-widest text-mist">Price</p>
                    <p class="mt-2 font-display text-3xl text-frost">
                        {{ number_format($plan->price) }}<span class="ml-2 text-base text-mist">Toman</span>
                    </p>
                    {{-- Say what is being bought on the page where the money is
                         committed, not only on the one before it. --}}
                    <p class="mt-3 font-mono text-[10px] uppercase tracking-widest text-mist">
                        {{ $plan->tournament_entries }} {{ \Illuminate\Support\Str::plural('tournament', $plan->tournament_entries) }}
                        &middot;
                        {{ $plan->vs_games }} VS {{ \Illuminate\Support\Str::plural('game', $plan->vs_games) }}
                    </p>
                </div>
            </div>

            <div class="border-t border-steel px-8 py-5 flex items-center justify-between">
                <span class="font-mono text-xs uppercase tracking-widest text-mist">Total due</span>
                <span class="font-display text-xl text-frost">{{ number_format($plan->price) }} Toman</span>
            </div>
        </div>

        {{-- Action --}}
        <div class="mt-8">
            @php $active = $this->activeSubscription(); @endphp

            @if ($active)
                {{-- Say so before the click, rather than refusing after it. --}}
                <div class="bg-carbon border border-steel rounded-sm p-6">
                    <p class="font-mono text-xs uppercase tracking-widest text-plasma">Already subscribed</p>
                    <p class="mt-2 text-sm text-mist">
                        Your <span class="text-frost">{{ $active->title }}</span> pass is active. Only one
                        subscription can run at a time.
                    </p>
                    <a href="{{ route('profile') }}" wire:navigate
                       class="mt-4 inline-block font-mono text-xs uppercase tracking-widest text-plasma border border-plasma/40 px-4 py-2.5 hover:bg-plasma/10 transition-colors">
                        Go to your profile
                    </a>
                </div>
            @else
                <button type="button" wire:click="confirm"
                        wire:loading.attr="disabled" wire:target="confirm"
                        class="w-full sm:w-auto bg-neon text-void px-8 py-3 rounded-sm font-mono text-xs uppercase tracking-widest transition-colors hover:bg-neon/90 disabled:opacity-60">
                    <span wire:loading.remove wire:target="confirm">
                        @auth Confirm and activate @else Log in to confirm @endauth
                    </span>
                    <span wire:loading wire:target="confirm">Working&hellip;</span>
                </button>

                @error('confirm')
                    <p class="mt-3 text-sm text-ember">{{ $message }}</p>
                @enderror

                <p class="mt-4 font-mono text-[10px] uppercase tracking-widest text-mist">
                    {{-- TODO payment: this line goes once a gateway runs before activation. --}}
                    No payment is taken yet &mdash; the pass activates immediately.
                </p>
            @endif
        </div>
    </section>
</div>
