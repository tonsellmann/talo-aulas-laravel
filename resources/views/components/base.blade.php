@props(['titulo' => null])
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2a1838">
    <meta name="robots" content="noindex">
    <title>{{ $titulo ? $titulo.' · ' : '' }}Talo Pilates</title>
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body {{ $attributes->merge(['class' => 'font-sans']) }}>
    @if (config('talo.demonstracao'))
        <livewire:demonstracao.barra />
    @endif

    {{ $slot }}

    <x-avisos />
</body>
</html>
