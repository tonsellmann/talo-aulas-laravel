@php
    $abas = [
        ['aluna.inicio', 'Início', 'inicio'],
        ['aluna.agenda', 'Agenda', 'agenda'],
        ['aluna.extrato', 'Extrato', 'extrato'],
        ['aluna.plano', 'Plano', 'plano'],
    ];
@endphp
<x-base :titulo="$title ?? null" class="pb-24 md:pb-12">
    <header class="mx-auto flex w-full max-w-3xl items-center gap-4 px-4 pt-4 md:pt-6">
        <a href="{{ route('aluna.inicio') }}" wire:navigate class="text-2xl"><x-marca /></a>
        <nav class="ml-auto hidden items-center gap-1 md:flex" aria-label="Seções">
            @foreach ($abas as [$rota, $rotulo, $icone])
                <a href="{{ route($rota) }}" wire:navigate @class(['rounded-full px-4 py-2 font-semibold', 'bg-ameixa text-branco' => request()->routeIs($rota), 'hover:bg-lilas-2' => ! request()->routeIs($rota)]) @if (request()->routeIs($rota)) aria-current="page" @endif>{{ $rotulo }}</a>
            @endforeach
        </nav>
        <form method="post" action="{{ route('sair') }}" class="ml-auto md:ml-2">
            @csrf
            <button class="flex size-10 items-center justify-center rounded-full bg-lilas-2 text-sm font-bold" title="Sair ({{ auth()->user()->name }})">
                {{ auth()->user()->iniciais() }}
            </button>
        </form>
    </header>

    <main class="mx-auto w-full max-w-3xl px-4 pt-5">
        {{ $slot }}
    </main>

    <nav class="fixed inset-x-0 bottom-0 z-40 border-t border-linha bg-branco/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden" aria-label="Seções">
        <div class="mx-auto grid max-w-md grid-cols-4">
            @foreach ($abas as [$rota, $rotulo, $icone])
                <a href="{{ route($rota) }}" wire:navigate @class(['flex flex-col items-center gap-1 py-2.5 text-xs font-semibold', 'text-ameixa' => request()->routeIs($rota), 'text-suave' => ! request()->routeIs($rota)]) @if (request()->routeIs($rota)) aria-current="page" @endif>
                    <span @class(['flex h-8 w-14 items-center justify-center rounded-full', 'bg-lima' => request()->routeIs($rota)])><x-icone :nome="$icone" /></span>
                    {{ $rotulo }}
                </a>
            @endforeach
        </div>
    </nav>
</x-base>
