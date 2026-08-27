<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>

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
                {{ config('app.name') }}
            </a>

            <nav class="flex items-center gap-6 text-sm">
                @auth
                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-2 text-mist hover:text-frost transition-colors">
                        @if (app(\App\Services\SubscriptionService::class)->activeFor(auth()->user()))
                            {{-- Shown only while a pass is active; absent otherwise. --}}
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
                                 stroke-linecap="round" stroke-linejoin="round"
                                 class="h-5 w-5 shrink-0 text-plasma" role="img"
                                 aria-label="Active subscription">
                                <title>Active subscription</title>
                                <path d="M7 8.5h10a4.5 4.5 0 0 1 4.42 3.66l.62 3.3A2.35 2.35 0 0 1 19.73 18c-.79 0-1.52-.4-1.95-1.05L16.6 15.2H7.4l-1.18 1.75A2.35 2.35 0 0 1 4.27 18a2.35 2.35 0 0 1-2.31-2.54l.62-3.3A4.5 4.5 0 0 1 7 8.5Z"/>
                                <path d="M7.6 11.4v2M6.6 12.4h2M15.6 11.7h.01M17.4 13.3h.01"/>
                            </svg>
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
                {{ config('app.name') }}
            </div>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Built for competitive play.</p>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
