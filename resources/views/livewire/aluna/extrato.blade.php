<div class="grid gap-6 pb-6">
    <div>
        <h1 class="titulo text-[2.6rem] md:text-5xl">Extrato</h1>
        <p class="mt-1 max-w-[52ch] text-suave">Cada aula que entra e sai do seu saldo, com o motivo. As que vencem primeiro são usadas primeiro.</p>
    </div>

    @if ($this->lotes->isNotEmpty())
        <section class="grid gap-2 sm:grid-cols-2" aria-label="Aulas disponíveis">
            @foreach ($this->lotes as $lote)
                <div class="cartao flex items-center gap-4 p-4">
                    <span class="numero text-5xl">{{ $lote->saldo }}</span>
                    <span class="text-sm"><span class="block font-bold">{{ $lote->origem->rotulo() }}</span><span class="text-suave">{{ $lote->saldo === 1 ? 'vale' : 'valem' }} até {{ $lote->valido_ate->translatedFormat('d/m') }}@if ($lote->modalidades), só {{ collect($lote->modalidades)->map(fn ($m) => ['solo' => 'Solo', 'aparelhos' => 'Aparelhos', 'gestantes' => 'Gestantes', 'sessenta' => '60+'][$m] ?? $m)->join(' e ') }}@endif</span></span>
                </div>
            @endforeach
        </section>
    @endif

    <ol class="cartao divide-y divide-linha px-4">
        @php $ultimoDia = null; @endphp
        @forelse ($this->movimentos as $m)
            @php $diaDoMovimento = $m->created_at->toDateString(); @endphp
            @if ($diaDoMovimento !== $ultimoDia)
                <li class="pb-1 pt-4 text-sm font-bold text-suave first:pt-3">{{ ucfirst($m->created_at->isToday() ? 'hoje' : $m->created_at->translatedFormat('l, d/m')) }}</li>
                @php $ultimoDia = $diaDoMovimento; @endphp
            @endif
            <li class="flex items-center gap-3 py-3" wire:key="m-{{ $m->id }}">
                <span @class([
                    'numero flex size-10 shrink-0 items-center justify-center rounded-full text-lg',
                    'bg-lima' => $m->quantidade > 0,
                    'bg-lilas-2' => $m->quantidade < 0,
                    'border border-dashed border-lilas-3 text-suave' => $m->quantidade === 0,
                ])>{{ $m->quantidade > 0 ? '+'.$m->quantidade : ($m->quantidade === 0 ? '·' : $m->quantidade) }}</span>
                <span class="min-w-0 flex-1">
                    <span class="block text-[0.95rem]">{{ $m->descricao }}</span>
                    <span class="text-xs text-suave">{{ $m->created_at->format('H:i') }}@if ($m->lote) · {{ $m->lote->origem->rotulo() }}@endif</span>
                </span>
            </li>
        @empty
            <li class="py-6 text-center text-suave">Nenhum movimento ainda.</li>
        @endforelse
    </ol>

    @if ($this->movimentos->count() >= $limite)
        <button class="btn btn-contorno justify-self-center" wire:click="mais">Ver mais antigos</button>
    @endif
</div>
