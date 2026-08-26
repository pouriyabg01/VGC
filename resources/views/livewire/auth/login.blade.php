<div>
    <h1 class="font-display text-2xl text-frost mb-1">Welcome back</h1>
    <p class="text-mist text-sm mb-8">Back to the bracket.</p>

    <form wire:submit="authenticate" class="space-y-5">
        <div>
            <label for="email" class="block text-sm font-medium text-frost/80 mb-1.5">Email</label>
            <input
                wire:model="email"
                type="email"
                id="email"
                autocomplete="email"
                class="w-full border border-steel rounded-sm px-3 py-2.5 bg-void focus:outline-none focus:ring-2 focus:ring-neon/40 focus:border-neon"
                placeholder="you@example.com"
            >
            @error('email') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium text-frost/80">Password</label>
            </div>
            <input
                wire:model="password"
                type="password"
                id="password"
                autocomplete="current-password"
                class="w-full border border-steel rounded-sm px-3 py-2.5 bg-void focus:outline-none focus:ring-2 focus:ring-neon/40 focus:border-neon"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
            @error('password') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-mist">
            <input wire:model="remember" type="checkbox" class="rounded-sm border-steel text-plasma focus:ring-neon/40">
            Remember me
        </label>

        <button
            type="submit"
            class="w-full bg-neon text-void py-2.5 rounded-sm hover:bg-neon/90 transition-colors"
            wire:loading.attr="disabled"
            wire:target="authenticate"
        >
            <span wire:loading.remove wire:target="authenticate">Log in</span>
            <span wire:loading wire:target="authenticate">Logging in&hellip;</span>
        </button>
    </form>

    <p class="text-center text-sm text-mist mt-6">
        New to {{ config('app.name') }}?
        <a href="{{ route('register') }}" wire:navigate class="text-frost underline underline-offset-2">Create an account</a>
    </p>
</div>
