<div>
    <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
        {{-- PLATFORMS SECTION --}}
        <div class="mb-20">
                <div class="flex items-center justify-between">
                    <p class="font-display text-2xl text-ink">My Platforms</p>
                    @if (session()->has('message'))
                        <span class="font-mono text-xs text-green-600">{{ session('message') }}</span>
                    @endif
                </div>

                {{-- List Platforms --}}
                <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach ($user->platforms as $p)
                        <div class="flex items-center justify-between bg-cream/50 border border-ink/5 p-4" wire:key="plat-{{ $p->id }}">
                            <div>
                                <span class="font-display text-lg text-ink block">{{ $p->nickname }}</span>
                                <span class="font-mono text-[10px] uppercase text-slate tracking-widest">{{ $p->platform }}</span>
                            </div>
                            <button wire:click="removePlatform({{ $p->id }})" class="text-xs uppercase text-red-400">Remove</button>
                        </div>
                    @endforeach
                </div>

                {{-- Add Platform Form --}}
                <div class="mt-16 space-y-3">
                    <form wire:submit.prevent="addPlatform" class="flex flex-row flex-wrap items-end gap-4">
                        <div class="flex-1 min-w-[160px]">
                            <label class="font-mono text-[10px] uppercase text-slate mb-2 block">Nickname</label>
                            <input type="text" wire:model="form.nickname" class="w-full bg-transparent border-b border-ink/20 outline-none text-sm @error('form.nickname') border-red-500 @enderror">
                            @error('form.nickname') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex-1 min-w-[160px]">
                            <label class="font-mono text-[10px] uppercase text-slate mb-2 block">Platform</label>
                            <select wire:model="form.platform" class="w-full bg-transparent border-b border-ink/20 outline-none text-sm @error('form.platform') border-red-500 @enderror">
                                <option value="">Select...</option>
                                @foreach($platformOptions as $option)
                                    <option value="{{ $option }}">{{ strtoupper($option) }}</option>
                                @endforeach
                            </select>
                            @error('form.platform') <span class="text-red-500 text-[10px]">{{ $message }}</span> @enderror
                        </div>
                        <div class="flex-1 min-w-[160px]">
                            <button type="submit" class="bg-ink text-cream px-8 py-2 text-xs uppercase border border-cream">
                                Add
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </section>
        {{-- TOURNAMENTS SECTION --}}
    <section class="border-t border-ink/10">
        <section class="mx-auto max-w-6xl px-6 pt-20 pb-24">
            <div class="mt-16 space-y-3">
                <p class="font-display text-2xl text-inkk">My Tournaments</p>
                @foreach ($user->tournaments as $tournament)
                    <a href="{{ route('tournament', $tournament) }}" wire:navigate class="block" wire:key="tour-{{ $tournament->id }}">
                        <article class="flex min-h-64 flex-col bg-cream p-8 hover:bg-ink/[0.02] transition-colors">
                            <div class="flex items-start justify-between gap-4">
                                <span class="font-mono text-xs text-brass">#{{ str_pad($tournament->id, 2, '0', STR_PAD_LEFT) }}</span>
                                <span class="font-mono text-xs uppercase text-slate">{{ $tournament->status->value }}</span>
                            </div>
                            <div class="mt-5 flex-1">
                                <h3 class="font-display text-2xl text-ink">{{ $tournament->game }}</h3>
                                <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-slate">Players</dt>
                                        <dd class="mt-1 text-ink">{{ count($tournament->players) }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-mono text-xs uppercase tracking-widest text-slate">Matches</dt>
                                        <dd class="mt-1 text-ink">{{ count($tournament->matches()->latestRound()) === '1' ?: 'DONE!'}}</dd>
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
    </section>
        </section>
</div>
