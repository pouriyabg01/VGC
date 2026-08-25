<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Focuslane' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@400;500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-void text-frost font-sans antialiased">

    <header class="border-b border-steel bg-carbon/40">
        <div class="mx-auto max-w-6xl px-6 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" wire:navigate class="flex items-center gap-2 font-display text-lg tracking-tight">
                <span class="inline-block w-4 h-[3px] bg-plasma"></span>
                Focuslane
            </a>

            <nav class="flex items-center gap-6 text-sm">
                @auth
                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 text-mist hover:text-frost transition-colors">
                        @if (auth()->user()->latest_active_sub)
                            <img src="{{ asset('storage/images/subscription-icon.png') }}" alt="icon" class="w-5 h-5">
                        @endif
                        <span>{{ auth()->user()->name }}</span>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-mist hover:text-frost transition-colors">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" wire:navigate class="text-mist hover:text-frost transition-colors">Log in</a>
                    <a href="{{ route('register') }}" wire:navigate
                       class="bg-neon text-void px-4 py-2 rounded-sm hover:bg-neon/90 transition-colors">
                        Get started
                    </a>
                @endauth
            </nav>

        </div>
    </header>

    <main>
        {{ $slot }}
    </main>

    <footer class="border-t border-steel mt-24">
        <div class="mx-auto max-w-6xl px-6 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm text-mist">
            <div class="flex items-center gap-2 font-display text-frost">
                <span class="inline-block w-3 h-[2px] bg-plasma"></span>
                Focuslane
            </div>
            <p>&copy; {{ date('Y') }} Focuslane. Built for focused work.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
