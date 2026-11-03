<div>
@php $dia = fn ($data) => $data->isToday() ? 'hoje' : ($data->isTomorrow() ? 'amanhã' : $data->translatedFormat('l, d/m')); @endphp
@if ($c = $this->consequencia)
    <div class="fixed inset-0 z-[60] flex items-end justify-center bg-ameixa/45 md:items-center" x-on:keydown.escape.window="$wire.set('reservaId', null)">
        <div class="absolute inset-0" wire:click="$set('reservaId', null)"></div>
        <div class="relative w-full max-w-md rounded-t-[26px] bg-branco p-6 md:rounded-[26px]" role="dialog" aria-modal="true" aria-labelledby="titulo-desmarcar">
            <h2 id="titulo-desmarcar" class="titulo text-3xl">Desmarcar {{ $c['reserva']->aula->modalidade->nome }}, {{ $dia($c['reserva']->aula->inicio) }} às {{ $c['reserva']->aula->inicio->format('H:i') }}?</h2>
            @if ($c['no_prazo'])
                <p class="mt-3 flex gap-2 rounded-2xl bg-lima-2 p-4"><x-icone nome="check" class="mt-0.5" />Ainda está no prazo: a aula volta para o seu saldo e a vaga vai para a fila de espera.</p>
            @elseif ($c['reposicoes'] > 0)
                <p class="mt-3 flex gap-2 rounded-2xl bg-pessego p-4 text-pessego-tinta"><x-icone nome="alerta" class="mt-0.5" />O prazo terminou às {{ $c['prazo']->format('H:i') }}, então esta aula não volta. Mas você ainda tem {{ $c['reposicoes'] }} {{ $c['reposicoes'] === 1 ? 'reposição' : 'reposições' }} neste ciclo: ganha uma aula extra para usar em {{ config('talo.reposicao.validade_dias') }} dias.</p>
            @else
                <p class="mt-3 flex gap-2 rounded-2xl bg-alerta-2 p-4 text-alerta"><x-icone nome="alerta" class="mt-0.5" />O prazo terminou às {{ $c['prazo']->format('H:i') }} e as reposições deste ciclo já foram usadas. Desmarcando agora, você perde esta aula.</p>
            @endif
            <div class="mt-5 grid grid-cols-2 gap-2">
                <button class="btn btn-contorno" wire:click="$set('reservaId', null)">Manter</button>
                <button @class(['btn', 'btn-primario' => $c['no_prazo'], 'btn-perigo' => ! $c['no_prazo']]) wire:click="confirmar" wire:loading.attr="disabled">Desmarcar</button>
            </div>
        </div>
    </div>
@endif
</div>
