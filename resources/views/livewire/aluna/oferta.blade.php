@php $aula = $entrada->aula; @endphp
<div class="grid gap-5 pb-6">
    @if ($entrada->ofertaValida())
        <section class="rounded-[26px] bg-lima p-6 md:p-8" x-data="contagem({{ $entrada->oferta_expira_em->getTimestampMs() }}, {{ now()->getTimestampMs() }})">
            <p class="font-bold">Vagou uma vaga para você</p>
            <h1 class="titulo mt-3 text-[2.6rem]">{{ $aula->modalidade->nome }}<br>{{ $aula->quando() }}</h1>
            <p class="mt-3 text-ameixa-2">Com {{ $aula->instrutora->primeiroNome() }} · {{ $aula->nivel }}. Confirmando, uma aula sai do seu saldo.</p>
            <p class="mt-5 flex items-baseline gap-3"><span class="numero text-6xl" x-text="texto"></span><span class="font-semibold">para confirmar</span></p>
            <div class="mt-5 grid gap-2 sm:grid-cols-2">
                <button class="btn btn-primario" wire:click="aceitar" wire:loading.attr="disabled">Quero a vaga</button>
                <button class="btn btn-contorno" wire:click="recusar">Não quero</button>
            </div>
        </section>
    @else
        <section class="cartao p-6">
            <h1 class="titulo text-4xl">Essa oferta não vale mais</h1>
            <p class="mt-3 text-ameixa-2">
                @switch($entrada->status->value)
                    @case('confirmada') Você já confirmou: a aula está nas suas próximas aulas. @break
                    @case('expirada') O prazo para confirmar acabou e a vaga seguiu para a próxima da fila. @break
                    @case('saiu') Você recusou a vaga ou saiu da fila. @break
                    @default A vaga já foi preenchida.
                @endswitch
            </p>
            <a href="{{ route('aluna.agenda') }}" wire:navigate class="btn btn-primario mt-5">Ver outros horários</a>
        </section>
    @endif
</div>
