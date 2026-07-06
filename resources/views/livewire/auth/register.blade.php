<div>
    <h1 class="font-display text-2xl text-ink mb-1">Create your account</h1>
    <p class="text-ink/60 text-sm mb-8">One lane at a time starts here.</p>

    <form wire:submit="register" class="space-y-5">
        <div>
            <label for="name" class="block text-sm font-medium text-ink/80 mb-1.5">Name</label>
            <input
                wire:model="name"
                type="text"
                id="name"
                autocomplete="name"
                class="w-full border border-ink/15 rounded-sm px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-brass/50 focus:border-brass"
                placeholder="Jamie Rivers"
            >
            @error('name') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

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
            <label for="password" class="block text-sm font-medium text-ink/80 mb-1.5">Password</label>
            <input
                wire:model="password"
                type="password"
                id="password"
                autocomplete="new-password"
                class="w-full border border-ink/15 rounded-sm px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-brass/50 focus:border-brass"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
            @error('password') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-ink/80 mb-1.5">Confirm password</label>
            <input
                wire:model="password_confirmation"
                type="password"
                id="password_confirmation"
                autocomplete="new-password"
                class="w-full border border-ink/15 rounded-sm px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-brass/50 focus:border-brass"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-ink text-cream py-2.5 rounded-sm hover:bg-ink/90 transition-colors"
            wire:loading.attr="disabled"
            wire:target="register"
        >
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register">Creating&hellip;</span>
        </button>
    </form>

    <p class="text-center text-sm text-ink/60 mt-6">
        Already have a lane going?
        <a href="{{ route('login') }}" wire:navigate class="text-ink underline underline-offset-2">Log in</a>
    </p>
</div>
