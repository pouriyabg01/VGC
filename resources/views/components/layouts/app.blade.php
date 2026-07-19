<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Focuslane' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Inter:wght@400;500;600&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-cream text-ink font-sans antialiased">

    <header class="border-b border-ink/10">
        <div class="mx-auto max-w-6xl px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 font-display text-lg tracking-tight">
                <span class="inline-block w-4 h-[3px] bg-brass"></span>
                Focuslane
            </a>

            <nav class="flex items-center gap-6 text-sm">
                @auth
                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 text-ink/70 hover:text-ink transition-colors">
                        @if (auth()->user()->latest_active_sub)
                            <img src="{{ asset('storage/images/subscription-icon.png') }}" alt="icon" class="w-5 h-5">
                        @endif
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-ink/70 hover:text-ink transition-colors">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-ink/70 hover:text-ink transition-colors">Log in</a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="bg-ink text-cream px-4 py-2 rounded-sm hover:bg-ink/90 transition-colors">
                        Get started
                    </a>
                @endauth
            </nav>

        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-ink/10 mt-24">
        <div class="mx-auto max-w-6xl px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-slate">
            <div class="flex items-center gap-2 font-display text-ink">
                <span class="inline-block w-3 h-[2px] bg-brass"></span>
                Focuslane
            </div>
            <p>&copy; {{ date('Y') }} Focuslane. Built for focused work.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
