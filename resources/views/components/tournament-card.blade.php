@props(['tournament'])

{{-- One tournament in a grid. Shared by the landing page and the tournaments
     page so the two lists cannot drift into saying different things about the
     same tournament. --}}
<a href="{{ route('tournament', $tournament) }}" wire:navigate
   {{ $attributes->merge(['class' => 'block group']) }}>
    <article class="flex min-h-64 flex-col bg-carbon border border-steel rounded-sm p-8 transition-colors group-hover:border-neon/60">
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
