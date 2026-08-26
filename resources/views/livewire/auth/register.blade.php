<div>
    <h1 class="font-display text-2xl text-frost mb-1">Create your account</h1>
    <p class="text-mist text-sm mb-8">Set up your player account.</p>

    <form wire:submit="register" class="space-y-5">
        <div>
            <label for="name" class="block text-sm font-medium text-frost/80 mb-1.5">Name</label>
            <input
                wire:model="name"
                type="text"
                id="name"
                autocomplete="name"
                class="w-full border border-steel rounded-sm px-3 py-2.5 bg-void focus:outline-none focus:ring-2 focus:ring-neon/40 focus:border-neon"
                placeholder="Your name"
            >
            @error('name') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

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
            <label for="password" class="block text-sm font-medium text-frost/80 mb-1.5">Password</label>
            <input
                wire:model="password"
                type="password"
                id="password"
                autocomplete="new-password"
                class="w-full border border-steel rounded-sm px-3 py-2.5 bg-void focus:outline-none focus:ring-2 focus:ring-neon/40 focus:border-neon"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
            @error('password') <p class="text-ember text-xs mt-1.5">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-frost/80 mb-1.5">Confirm password</label>
            <input
                wire:model="password_confirmation"
                type="password"
                id="password_confirmation"
                autocomplete="new-password"
                class="w-full border border-steel rounded-sm px-3 py-2.5 bg-void focus:outline-none focus:ring-2 focus:ring-neon/40 focus:border-neon"
                placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
            >
        </div>

        <button
            type="submit"
            class="w-full bg-neon text-void py-2.5 rounded-sm hover:bg-neon/90 transition-colors"
            wire:loading.attr="disabled"
            wire:target="register"
        >
            <span wire:loading.remove wire:target="register">Create account</span>
            <span wire:loading wire:target="register">Creating&hellip;</span>
        </button>
    </form>

    <p class="text-center text-sm text-mist mt-6">
        Already competing?
        <a href="{{ route('login') }}" wire:navigate class="text-frost underline underline-offset-2">Log in</a>
    </p>
</div>
