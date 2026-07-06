<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
<body class="bg-ink text-cream font-sans antialiased min-h-screen flex items-center justify-center px-6">

    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center justify-center gap-2 font-display text-xl mb-8 text-cream">
            <span class="inline-block w-5 h-[3px] bg-brass"></span>
            Focuslane
        </a>

        <div class="bg-cream text-ink rounded-sm p-8 shadow-2xl shadow-black/20">
            {{ $slot }}
        </div>

        <p class="text-center text-cream/40 text-xs mt-6 font-mono">one lane at a time</p>
    </div>

    @livewireScripts
</body>
</html>
