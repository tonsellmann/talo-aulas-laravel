<div class="mx-auto grid max-w-6xl gap-5">
    <header class="flex flex-wrap items-end gap-3">
        <h1 class="titulo flex-1 text-5xl">Alunas <span class="numero text-3xl text-suave">{{ $this->alunas->count() }}</span></h1>
        <label class="relative w-full max-w-xs">
            <span class="sr-only">Buscar aluna</span>
            <x-icone nome="busca" class="pointer-events-none absolute left-3.5 top-3 text-suave" :tamanho="18" />
            <input type="search" wire:model.live.debounce.250ms="busca" class="campo pl-10" placeholder="Buscar pelo nome">
        </label>
    </header>

    <div class="cartao overflow-x-auto">
        <table class="w-full min-w-[720px] text-left text-sm">
            <thead class="border-b border-linha text-xs text-suave">
                <tr><th class="px-4 py-3 font-semibold">Aluna</th><th class="px-4 py-3 font-semibold">Plano</th><th class="px-4 py-3 font-semibold">Aulas no saldo</th><th class="px-4 py-3 font-semibold">Próxima aula</th><th class="px-4 py-3 font-semibold">Situação</th></tr>
            </thead>
            <tbody class="divide-y divide-linha">
                @forelse ($this->alunas as $a)
                    @php
                        $m = $a->matriculaAtual;
                        $saldo = $a->lotes->sum('saldo');
                        $proxima = $a->proxima_aula ? \Carbon\CarbonImmutable::parse($a->proxima_aula) : null;
                        $divergente = $a->cobrancas->firstWhere('status', \App\Enums\StatusCobranca::Divergente);
                        $pendente = $a->cobrancas->firstWhere('status', \App\Enums\StatusCobranca::Pendente);
                    @endphp
                    <tr class="hover:bg-lilas/60" wire:key="al-{{ $a->id }}">
                        <td class="px-4 py-3"><a href="{{ route('recepcao.aluna', $a) }}" wire:navigate class="flex items-center gap-3 font-semibold hover:underline"><span class="flex size-8 items-center justify-center rounded-full bg-lilas-2 text-xs font-bold">{{ $a->iniciais() }}</span>{{ $a->name }}</a></td>
                        <td class="px-4 py-3">{{ $m?->plano->nome ?? '–' }}@if ($m?->modalidade) <span class="text-suave">· {{ $m->modalidade->nome }}</span>@endif</td>
                        <td class="px-4 py-3"><span class="numero text-xl">{{ $saldo }}</span>@if ($v = $a->lotes->sortBy('valido_ate')->first()) <span class="text-xs text-suave">vence {{ $v->valido_ate->format('d/m') }}</span>@endif</td>
                        <td class="px-4 py-3">{{ $proxima ? ucfirst($proxima->translatedFormat('D d/m, H:i')) : '–' }}</td>
                        <td class="px-4 py-3">
                            @if ($divergente)<span class="selo selo-poucas">Pix com valor diferente</span>
                            @elseif ($m?->status === \App\Enums\StatusMatricula::Pendente)<span class="selo selo-alerta">Plano vencido</span>
                            @elseif ($pendente)<span class="selo selo-lotada">Pix de renovação enviado</span>
                            @else <span class="selo selo-vaga">Em dia</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-suave">Nenhuma aluna com esse nome.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
