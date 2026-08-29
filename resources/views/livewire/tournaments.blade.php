<div>
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        <p class="font-mono text-xs tracking-widest uppercase text-plasma mb-3">Tournaments</p>
        <h1 class="font-display text-4xl sm:text-5xl text-frost tracking-tight">
            Every bracket on the board.
        </h1>
        <p class="mt-4 max-w-xl text-sm leading-6 text-mist">
            Pick one on your platform, take a seat, and play your way to the final.
        </p>

        {{-- Filters --}}
        <div class="mt-10 flex flex-wrap items-end gap-4 border-b border-steel pb-6">
            <div>
                <label for="filter-status" class="block font-mono text-xs uppercase tracking-widest text-mist mb-2">
                    Status
                </label>
                <select id="filter-status" wire:model.live="status"
                        class="bg-carbon border border-steel rounded-sm px-3 py-2 text-sm text-frost focus:border-neon focus:outline-none">
                    <option value="">Any status</option>
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}">{{ $case->value }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="filter-platform" class="block font-mono text-xs uppercase tracking-widest text-mist mb-2">
                    Platform
                </label>
                <select id="filter-platform" wire:model.live="platform"
                        class="bg-carbon border border-steel rounded-sm px-3 py-2 text-sm text-frost focus:border-neon focus:outline-none">
                    <option value="">Any platform</option>
                    @foreach ($platforms as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>

            @if ($status || $platform)
                <button type="button" wire:click="clearFilters"
                        class="text-sm text-mist hover:text-frost transition-colors pb-2">
                    Clear filters
                </button>
            @endif

            <span class="ml-auto pb-2 font-mono text-xs uppercase tracking-widest text-mist">
                {{ $tournaments->total() }} {{ Str::plural('tournament', $tournaments->total()) }}
            </span>
        </div>

        @if ($tournaments->isNotEmpty())
            <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($tournaments as $tournament)
                    <x-tournament-card :tournament="$tournament" wire:key="tournament-{{ $tournament->id }}" />
                @endforeach
            </div>

            {{-- Prev/next rather than the packaged paginator: that one ships
                 light-theme markup, which reads as broken on this page. --}}
            @if ($tournaments->hasPages())
                <nav class="mt-12 flex items-center justify-between border-t border-steel pt-6">
                    <button type="button" wire:click="previousPage" wire:loading.attr="disabled"
                            @disabled($tournaments->onFirstPage())
                            class="text-sm text-mist hover:text-frost transition-colors disabled:opacity-30 disabled:hover:text-mist">
                        &larr; Previous
                    </button>
                    <span class="font-mono text-xs uppercase tracking-widest text-mist">
                        Page {{ $tournaments->currentPage() }} of {{ $tournaments->lastPage() }}
                    </span>
                    <button type="button" wire:click="nextPage" wire:loading.attr="disabled"
                            @disabled(! $tournaments->hasMorePages())
                            class="text-sm text-mist hover:text-frost transition-colors disabled:opacity-30 disabled:hover:text-mist">
                        Next &rarr;
                    </button>
                </nav>
            @endif
        @else
            <div class="mt-10 border border-dashed border-steel bg-carbon px-6 py-12 text-center">
                <h2 class="font-display text-2xl text-frost">
                    {{ $status || $platform ? 'Nothing matches those filters.' : 'No tournaments yet.' }}
                </h2>
                <p class="mx-auto mt-3 max-w-md text-sm leading-6 text-mist">
                    {{ $status || $platform
                        ? 'Widen the search — there may be brackets on another platform.'
                        : 'New brackets open here as soon as they are put on.' }}
                </p>
            </div>
        @endif
    </section>
</div>
