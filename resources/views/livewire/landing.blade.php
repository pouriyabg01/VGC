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

    {{-- Features, laid out as lanes --}}
    <section class="border-t border-ink/10">
        <div class="mx-auto max-w-6xl px-6 py-20">
            <h2 class="font-display text-3xl text-ink mb-12">Three lanes. That's the whole system.</h2>

            <div class="grid sm:grid-cols-3 gap-px bg-ink/10 rounded-sm overflow-hidden">
                @foreach ([
                    [
                        'title' => 'Start a lane',
                        'body' => 'Open a lane for whatever you\'re about to focus on. No project setup, no fields to fill in first.',
                    ],
                    [
                        'title' => 'Work the lane',
                        'body' => 'One active lane at a time. Switching lanes is deliberate, so your time log tells the truth.',
                    ],
                    [
                        'title' => 'Close the lane',
                        'body' => 'End of day, every lane rolls into a client-ready summary. Export it or send it straight over.',
                    ],
                ] as $i => $feature)
                    <div class="bg-cream p-8">
                        <span class="font-mono text-xs text-brass">{{ sprintf('%02d', $i + 1) }}</span>
                        <h3 class="font-display text-xl mt-3 mb-2 text-ink">{{ $feature['title'] }}</h3>
                        <p class="text-ink/70 text-sm leading-relaxed">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
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
