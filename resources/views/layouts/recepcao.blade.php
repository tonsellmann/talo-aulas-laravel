@php
    $itens = [
        ['recepcao.hoje', 'Hoje', 'inicio'],
        ['recepcao.semana', 'Semana', 'semana'],
        ['recepcao.alunas', 'Alunas', 'pessoas'],
        ['recepcao.cobrancas', 'Cobranças', 'pix'],
        ['recepcao.mensagens', 'Mensagens', 'mensagem'],
        ['recepcao.relatorios', 'Relatórios', 'grafico'],
    ];
    $ativo = fn ($rota) => request()->routeIs($rota) || ($rota === 'recepcao.alunas' && request()->routeIs('recepcao.aluna'));
@endphp
<x-base :titulo="$title ?? null">
    <div class="min-h-dvh lg:grid lg:grid-cols-[236px_minmax(0,1fr)]" x-data="{ menu: false }">
        <aside class="sticky top-0 hidden h-dvh flex-col gap-8 bg-ameixa px-5 py-6 text-branco lg:flex">
            <a href="{{ route('recepcao.hoje') }}" wire:navigate class="text-2xl"><x-marca>talo <span class="ml-1 font-sans text-sm font-semibold text-lilas-3">recepção</span></x-marca></a>
            <nav class="flex flex-col gap-1" aria-label="Seções">
                @foreach ($itens as [$rota, $rotulo, $icone])
                    <a href="{{ route($rota) }}" wire:navigate @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 font-semibold', 'bg-lima text-ameixa' => $ativo($rota), 'text-lilas-2 hover:bg-ameixa-2' => ! $ativo($rota)]) @if ($ativo($rota)) aria-current="page" @endif>
                        <x-icone :nome="$icone" />{{ $rotulo }}
                    </a>
                @endforeach
            </nav>
            <form method="post" action="{{ route('sair') }}" class="mt-auto flex items-center gap-3 border-t border-ameixa-2 pt-5">
                @csrf
                <span class="flex size-9 items-center justify-center rounded-full bg-ameixa-2 text-sm font-bold">{{ auth()->user()->iniciais() }}</span>
                <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ auth()->user()->name }}</span>
                <button class="rounded-lg p-1.5 text-lilas-3 hover:bg-ameixa-2 hover:text-branco" title="Sair"><x-icone nome="sair" /></button>
            </form>
        </aside>

        <header class="sticky top-0 z-30 flex items-center gap-3 bg-ameixa px-4 py-3 text-branco lg:hidden">
            <a href="{{ route('recepcao.hoje') }}" wire:navigate class="text-xl"><x-marca :tamanho="28" /></a>
            <span class="text-sm font-semibold text-lilas-3">recepção</span>
            <button class="ml-auto rounded-lg p-2 hover:bg-ameixa-2" x-on:click="menu = ! menu" :aria-expanded="menu" aria-label="Abrir menu"><x-icone nome="menu" /></button>
        </header>
        <nav x-show="menu" x-cloak x-transition.opacity class="sticky top-[60px] z-30 grid grid-cols-3 gap-1 bg-ameixa px-3 pb-3 text-branco lg:hidden" aria-label="Seções">
            @foreach ($itens as [$rota, $rotulo, $icone])
                <a href="{{ route($rota) }}" wire:navigate @class(['flex flex-col items-center gap-1 rounded-xl py-2 text-xs font-semibold', 'bg-lima text-ameixa' => $ativo($rota), 'text-lilas-2' => ! $ativo($rota)])><x-icone :nome="$icone" />{{ $rotulo }}</a>
            @endforeach
        </nav>

        <main class="min-w-0 px-4 py-5 md:px-8 md:py-8">
            {{ $slot }}
        </main>
    </div>
</x-base>
