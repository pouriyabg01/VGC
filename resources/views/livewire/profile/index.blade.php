<div>
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        {{-- PLATFORMS SECTION --}}
        <div class="mb-20">
                <div class="flex items-center justify-between">
                    <p class="font-display text-2xl text-frost">My Platforms</p>
                    @if (session()->has('message'))
                        <span class="font-mono text-xs text-plasma">{{ session('message') }}</span>
                    @endif
                </div>

                {{-- List Platforms --}}
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($user->platforms as $p)
                        <div class="flex items-center justify-between bg-carbon/60 border border-steel p-4" wire:key="plat-{{ $p->id }}">
                            <div>
                                <span class="font-display text-lg text-frost block">{{ $p->nickname }}</span>
                                <span class="font-mono text-[10px] uppercase text-mist tracking-widest">{{ $p->platform->label() }}</span>
                            </div>
                            <button wire:click="removePlatform({{ $p->id }})" class="text-xs uppercase text-ember">Remove</button>
                        </div>
                    @endforeach
                </div>

                {{-- Add Platform Form --}}
                <div class="mt-16 space-y-3">
                    <form wire:submit.prevent="addPlatform" class="flex flex-row flex-wrap items-start gap-4">
                        <div class="flex-1 min-w-[160px]">
                            <label for="nickname" class="font-mono text-[10px] uppercase tracking-widest text-mist mb-2 block">Nickname</label>
                            <input type="text" id="nickname" wire:model="form.nickname" placeholder="your gamertag"
                                   class="w-full h-11 bg-void border border-steel rounded-sm px-3 text-sm text-frost placeholder:text-mist/50 outline-none transition-colors focus:border-neon focus:ring-2 focus:ring-neon/30 @error('form.nickname') border-ember @enderror">
                            @error('form.nickname') <span class="mt-1.5 block text-ember text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex-1 min-w-[160px]">
                            <label for="platform" class="font-mono text-[10px] uppercase tracking-widest text-mist mb-2 block">Platform</label>
                            {{-- appearance-none: a bare select keeps the OS widget, which renders
                                 light-on-light against this theme. The chevron is drawn back in. --}}
                            <div class="relative">
                                <select id="platform" wire:model="form.platform"
                                        class="w-full h-11 appearance-none bg-void border border-steel rounded-sm pl-3 pr-9 text-sm text-frost outline-none transition-colors focus:border-neon focus:ring-2 focus:ring-neon/30 @error('form.platform') border-ember @enderror">
                                    <option value="" class="bg-void text-mist">Select&hellip;</option>
                                    @foreach($platformOptions as $value => $label)
                                        <option value="{{ $value }}" class="bg-void text-frost">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-mist"
                                     viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path d="M6 8l4 4 4-4" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            @error('form.platform') <span class="mt-1.5 block text-ember text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="min-w-[120px]">
                            <span class="font-mono text-[10px] uppercase tracking-widest text-transparent mb-2 block" aria-hidden="true">Add</span>
                            <button type="submit"
                                    class="h-11 w-full bg-neon text-void px-8 rounded-sm font-mono text-xs uppercase tracking-widest transition-colors hover:bg-neon/90">
                                Add
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </section>
        {{-- TOURNAMENTS SECTION --}}
    <section class="border-t border-steel">
        <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
            <div class="mt-16 space-y-3">
                <p class="font-display text-2xl text-frost">My Tournaments</p>
                @foreach ($user->tournaments as $tournament)
                    <a href="{{ route('tournament', $tournament) }}" wire:navigate class="block" wire:key="tour-{{ $tournament->id }}">
                        <article class="flex min-h-64 flex-col bg-carbon p-8 hover:bg-neon/5 transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-mono text-xs text-plasma">#{{ str_pad($tournament->id, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="font-mono text-xs uppercase text-mist">{{ $tournament->status->value }}</span>
                            </div>
                            <div class="mt-5 flex-1">
                                <h3 class="font-display text-2xl text-frost">{{ $tournament->game }}</h3>
                                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-mist">Players</dt>
                                        <dd class="mt-1 text-frost">{{ count($tournament->players) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-mist">Matches</dt>
                                        <dd class="mt-1 text-frost">{{ count($tournament->matches()->latestRound()) === '1' ?: 'DONE!'}}</dd>
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
    </section>
        </section>
</div>
