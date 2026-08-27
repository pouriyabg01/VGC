<div>
    <p class="font-display text-2xl text-frost">My Matches</p>

    @if ($matches->isEmpty())
        <div class="mt-6 border border-dashed border-steel bg-carbon rounded-sm px-8 py-10">
            <p class="font-mono text-xs uppercase tracking-widest text-mist">No matches yet</p>
            <p class="mt-3 max-w-md text-sm leading-6 text-mist">
                Once a tournament you entered fills up and the bracket is drawn, your matches appear here.
            </p>
        </div>
    @else
        <div class="mt-6 space-y-4">
            @foreach ($matches as $match)
                @php
                    $isPlayer1 = $match->player1_id === $userId;
                    $opponent  = $isPlayer1 ? $match->player2 : $match->player1;
                    $submitted = $match->submissions->firstWhere('user_id', $userId);
                    $canReport = $match->status === $pending && ! $submitted;
                @endphp

                <article wire:key="match-{{ $match->id }}"
                         class="bg-carbon border border-steel rounded-sm p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="font-mono text-[10px] uppercase tracking-widest text-plasma">
                                {{ $match->tournament?->game }} &middot; Round {{ $match->round }}
                            </p>
                            <p class="mt-2 font-display text-xl text-frost">
                                vs {{ $opponent?->name ?? 'TBD' }}
                            </p>
                        </div>

                        <span class="font-mono text-[10px] uppercase tracking-widest px-2 py-1 border
                            {{ $match->status === $pending
                                ? 'text-mist border-steel'
                                : ($match->status === \App\Enums\Tournaments\TournamentMatchEnum::COMPLETED
                                    ? 'text-plasma border-plasma/40 bg-plasma/10'
                                    : 'text-ember border-ember/40 bg-ember/10') }}">
                            {{ $match->status?->value }}
                        </span>
                    </div>

                    {{-- Settled matches show the score instead of a form. --}}
                    @if ($match->status === \App\Enums\Tournaments\TournamentMatchEnum::COMPLETED)
                        <div class="mt-5 border-t border-steel pt-4 flex flex-wrap gap-x-10 gap-y-2 text-sm">
                            <div>
                                <span class="font-mono text-[10px] uppercase tracking-widest text-mist">Score</span>
                                <p class="mt-1 text-frost">
                                    {{ $isPlayer1 ? $match->player1_goal : $match->player2_goal }}
                                    &ndash;
                                    {{ $isPlayer1 ? $match->player2_goal : $match->player1_goal }}
                                </p>
                            </div>
                            <div>
                                <span class="font-mono text-[10px] uppercase tracking-widest text-mist">Result</span>
                                <p class="mt-1 {{ $match->winner_id === $userId ? 'text-plasma' : 'text-mist' }}">
                                    {{ $match->winner_id === $userId ? 'You won' : ($match->winner_id ? 'You lost' : 'Draw') }}
                                </p>
                            </div>
                        </div>
                    @elseif ($canReport)
                        {{-- Open match this player has not reported yet. --}}
                        <form wire:submit.prevent="submit({{ $match->id }})"
                              class="mt-5 border-t border-steel pt-5 space-y-5">
                          <div class="flex flex-wrap items-end gap-4">
                            <div class="w-32">
                                <label for="scored-{{ $match->id }}"
                                       class="font-mono text-[10px] uppercase tracking-widest text-mist mb-2 block">
                                    You scored
                                </label>
                                <input type="number" min="0" id="scored-{{ $match->id }}"
                                       wire:model="goals.{{ $match->id }}.scored"
                                       class="w-full h-11 bg-void border border-steel rounded-sm px-3 text-sm text-frost outline-none transition-colors focus:border-neon focus:ring-2 focus:ring-neon/30">
                            </div>
                            <div class="w-32">
                                <label for="conceded-{{ $match->id }}"
                                       class="font-mono text-[10px] uppercase tracking-widest text-mist mb-2 block">
                                    Conceded
                                </label>
                                <input type="number" min="0" id="conceded-{{ $match->id }}"
                                       wire:model="goals.{{ $match->id }}.conceded"
                                       class="w-full h-11 bg-void border border-steel rounded-sm px-3 text-sm text-frost outline-none transition-colors focus:border-neon focus:ring-2 focus:ring-neon/30">
                            </div>
                          </div>

                            {{-- The picked file is previewed straight from the
                                 browser, so the player can read the scoreline
                                 back before committing to it. --}}
                            <div x-data="{
                                    preview: null,
                                    fileName: null,
                                    show(event) {
                                        const file = event.target.files[0];
                                        if (this.preview) URL.revokeObjectURL(this.preview);
                                        this.preview = file ? URL.createObjectURL(file) : null;
                                        this.fileName = file ? file.name : null;
                                    },
                                 }"
                                 x-on:beforeunload.window="preview && URL.revokeObjectURL(preview)">
                                <label for="screenshot-{{ $match->id }}"
                                       class="font-mono text-[10px] uppercase tracking-widest text-mist block">
                                    Screenshot of the final score
                                </label>
                                <p class="mt-1 text-xs text-mist">{{ $screenshotHint }}</p>

                                <input type="file" id="screenshot-{{ $match->id }}"
                                       accept="{{ $screenshotAccept }}"
                                       wire:model="screenshots.{{ $match->id }}"
                                       x-on:change="show($event)"
                                       class="mt-2 w-full h-12 bg-void border border-steel rounded-sm text-sm text-mist outline-none transition-colors focus:border-neon focus:ring-2 focus:ring-neon/30
                                              file:h-12 file:mr-4 file:border-0 file:bg-steel file:text-frost file:px-5
                                              file:font-mono file:text-[10px] file:uppercase file:tracking-widest file:cursor-pointer hover:file:bg-steel/80">

                                <p class="mt-2 font-mono text-[10px] uppercase tracking-widest text-plasma"
                                   wire:loading wire:target="screenshots.{{ $match->id }}">
                                    Uploading&hellip;
                                </p>

                                <div x-cloak x-show="preview" class="mt-4">
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-mist"
                                       x-text="fileName"></p>
                                    <a :href="preview" target="_blank" class="mt-2 block w-fit">
                                        <img :src="preview" alt="Screenshot preview"
                                             class="max-h-72 w-auto max-w-full rounded-sm border border-steel bg-void object-contain">
                                    </a>
                                    <p class="mt-2 text-xs text-mist">Click the preview to open it full size.</p>
                                </div>
                            </div>

                            <button type="submit"
                                    wire:loading.attr="disabled" wire:target="submit({{ $match->id }}), screenshots.{{ $match->id }}"
                                    class="h-11 bg-neon text-void px-8 rounded-sm font-mono text-xs uppercase tracking-widest transition-colors hover:bg-neon/90 disabled:opacity-60">
                                <span wire:loading.remove wire:target="submit({{ $match->id }})">Submit result</span>
                                <span wire:loading wire:target="submit({{ $match->id }})">Sending&hellip;</span>
                            </button>
                        </form>

                        @error('match.'.$match->id)
                            <p class="mt-3 text-sm text-ember">{{ $message }}</p>
                        @enderror
                    @elseif ($submitted)
                        {{-- Reported; the match settles once the opponent reports too. --}}
                        <div class="mt-5 border-t border-steel pt-4">
                            <p class="font-mono text-[10px] uppercase tracking-widest text-plasma">Result submitted</p>
                            <p class="mt-2 text-sm text-mist">
                                You reported {{ $submitted->scored_goals }}&ndash;{{ $submitted->conceded_goals }}.
                                Waiting for {{ $opponent?->name ?? 'your opponent' }}.
                            </p>
                            @if ($submitted->screenshot)
                                <a href="{{ Storage::disk('public')->url($submitted->screenshot) }}" target="_blank"
                                   class="mt-3 inline-block font-mono text-[10px] uppercase tracking-widest text-plasma hover:underline">
                                    View your screenshot
                                </a>
                            @endif
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    @endif
</div>
