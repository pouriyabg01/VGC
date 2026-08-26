<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
<body class="bg-void text-frost font-sans antialiased min-h-screen flex items-center justify-center px-6">

    <div class="w-full max-w-md">
        <a href="{{ route('home') }}" wire:navigate class="flex items-center justify-center gap-2 font-display text-xl mb-8 text-frost">
            <span class="inline-block w-5 h-[3px] bg-plasma"></span>
            {{ config('app.name') }}
        </a>

        <div class="bg-carbon text-frost border border-steel rounded-sm p-8 shadow-2xl shadow-black/40">
            {{ $slot }}
        </div>

        <p class="text-center text-mist/70 text-xs mt-6 font-mono">play. compete. win.</p>
    </div>

    @livewireScripts
</body>
</html>
