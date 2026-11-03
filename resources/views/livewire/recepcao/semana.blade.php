@php
    $dias = collect(range(0, 5))->map(fn ($i) => $this->segunda->addDays($i));
    $porCelula = $this->aulas->groupBy(fn ($a) => $a->inicio->toDateString().' '.$a->inicio->format('H:i'));
    $ocupadas = $this->aulas->reject->cancelada()->sum('ocupadas');
    $capacidade = $this->aulas->reject->cancelada()->sum('capacidade');
@endphp
<div class="mx-auto grid max-w-7xl gap-5">
    <header class="flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-suave">Semana de {{ $this->segunda->format('d/m') }} a {{ $this->segunda->addDays(5)->format('d/m') }}</p>
            <h1 class="titulo text-5xl">Grade da semana</h1>
        </div>
        <p class="text-sm text-suave"><span class="numero text-2xl text-ameixa">{{ $capacidade ? round($ocupadas / $capacidade * 100) : 0 }}%</span> de ocupação</p>
        <div class="flex gap-1.5">
            <button class="btn btn-contorno btn-pequeno" wire:click="mudarSemana(-1)" aria-label="Semana anterior"><x-icone nome="voltar" :tamanho="16" /></button>
            <button class="btn btn-contorno btn-pequeno" wire:click="estaSemana">Esta semana</button>
            <button class="btn btn-contorno btn-pequeno" wire:click="mudarSemana(1)" aria-label="Próxima semana"><x-icone nome="seta" :tamanho="16" /></button>
        </div>
    </header>

    <div class="flex flex-wrap gap-4 text-sm text-suave" aria-hidden="true">
        <span class="flex items-center gap-1.5"><span class="vaga-bloco vaga-bloco-ocupada w-4 flex-none"></span>vaga ocupada</span>
        <span class="flex items-center gap-1.5"><span class="vaga-bloco w-4 flex-none"></span>vaga livre</span>
        <span class="flex items-center gap-1.5"><span class="selo selo-ameixa">+2</span>na fila de espera</span>
    </div>

    <div class="-mx-4 overflow-x-auto px-4 pb-2 md:mx-0 md:px-0">
        <table class="w-full min-w-[860px] border-separate border-spacing-1.5 text-left">
            <thead>
                <tr>
                    <th class="w-16"><span class="sr-only">Horário</span></th>
                    @foreach ($dias as $d)
                        <th scope="col" @class(['rounded-xl px-2 py-2 text-sm font-bold', 'bg-ameixa text-branco' => $d->isToday(), 'text-suave' => ! $d->isToday()])>{{ ucfirst($d->translatedFormat('D')) }} <span class="numero ml-1 text-lg">{{ $d->format('d') }}</span></th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($this->horarios as $hora)
                    <tr>
                        <th scope="row" class="numero align-top pt-2 text-xl">{{ $hora }}</th>
                        @foreach ($dias as $d)
                            <td class="align-top">
                                @foreach ($porCelula[$d->toDateString().' '.$hora] ?? [] as $aula)
                                    <button type="button" wire:key="a-{{ $aula->id }}" wire:click="$dispatch('abrir-aula', { aula: {{ $aula->id }} })"
                                            @class(['cartao borda-modalidade flex w-full flex-col gap-1.5 px-3 py-2 text-left transition hover:shadow-[0_10px_24px_-16px_rgb(42_24_56/.6)]', 'opacity-50' => $aula->cancelada() || now()->greaterThan($aula->fim)])
                                            style="--cor-modalidade: {{ $aula->modalidade->cor }}">
                                        <span class="flex items-center gap-1 text-sm font-bold">{{ $aula->modalidade->nome }}
                                            @if ($aula->cancelada())<span class="selo selo-alerta ml-auto">Cancelada</span>@elseif ($aula->fila_count)<span class="selo selo-ameixa ml-auto">+{{ $aula->fila_count }}</span>@endif
                                        </span>
                                        <span class="flex gap-0.5">@for ($i = 0; $i < $aula->capacidade; $i++)<span @class(['vaga-bloco h-1.5', 'vaga-bloco-ocupada' => $i < $aula->ocupadas])></span>@endfor</span>
                                        <span class="text-xs text-suave">{{ $aula->instrutora->primeiroNome() }} · {{ $aula->ocupadas }}/{{ $aula->capacidade }}</span>
                                    </button>
                                @endforeach
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <livewire:recepcao.painel-da-aula />
</div>
