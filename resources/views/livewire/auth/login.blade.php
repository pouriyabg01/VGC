<div>
    <h1 class="font-display text-2xl text-ink mb-1">Welcome back</h1>
    <p class="text-ink/60 text-sm mb-8">Pick up your lanes where you left them.</p>

    <form wire:submit="authenticate" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-ink/80 mb-1.5">Email</label>
            <input
                wire:model="email"
                type="email"
                id="email"
                autocomplete="email"
                class="w-full border border-ink/15 rounded-sm px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-brass/50 focus:border-brass"
                placeholder="you@studio.com"
            >
            @error('email') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-ink/80">Password</label>
            </div>
            <input
                wire:model="password"
                type="password"
                id="password"
                autocomplete="current-password"
                class="w-full border border-ink/15 rounded-sm px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-brass/50 focus:border-brass"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
            @error('password') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-ink/70">
            <input wire:model="remember" type="checkbox" class="rounded-sm border-ink/30 text-brass focus:ring-brass/50">
            Remember me
        </label>

        <button
            type="submit"
            class="w-full bg-ink text-cream py-2.5 rounded-sm hover:bg-ink/90 transition-colors"
            wire:loading.attr="disabled"
            wire:target="authenticate"
        >
            <span wire:loading.remove wire:target="authenticate">Log in</span>
            <span wire:loading wire:target="authenticate">Logging in&hellip;</span>
        </button>
    </form>

    <p class="text-center text-sm text-ink/60 mt-6">
        New to Focuslane?
        <a href="{{ route('register') }}" wire:navigate class="text-ink underline underline-offset-2">Create an account</a>
    </p>
</div>
