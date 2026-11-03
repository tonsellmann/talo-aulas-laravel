@php
    $aluna = auth()->user();
    $proxima = $this->proximas->first();
    $depois = $this->proximas->slice(1);
    $saldo = $this->lotes->sum('saldo');
    $dia = fn ($data) => $data->isToday() ? 'hoje' : ($data->isTomorrow() ? 'amanhã' : $data->translatedFormat('l, d/m'));
@endphp
<div class="grid gap-8 pb-6">
    <section>
        <h1 class="titulo text-[2.6rem] md:text-5xl">Oi, {{ $aluna->primeiroNome() }}</h1>
        <p class="mt-1 text-suave">{{ ucfirst(now()->translatedFormat('l, j \d\e F')) }}</p>
    </section>

    @foreach ($this->ofertas as $oferta)
        <section class="rounded-[22px] bg-lima p-5" wire:key="oferta-{{ $oferta->id }}"
                 x-data="contagem({{ $oferta->oferta_expira_em->getTimestampMs() }}, {{ now()->getTimestampMs() }})">
            <p class="font-bold">Vagou uma vaga para você</p>
            <p class="titulo mt-2 text-3xl">{{ $oferta->aula->modalidade->nome }}, {{ $dia($oferta->aula->inicio) }} às {{ $oferta->aula->inicio->format('H:i') }}</p>
            <p class="mt-2 text-ameixa-2">Com {{ $oferta->aula->instrutora->primeiroNome() }}. Confirme até {{ $oferta->oferta_expira_em->format('H:i') }}; depois disso, a vaga segue para a próxima da fila.</p>
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button class="btn btn-primario" wire:click="aceitar({{ $oferta->id }})" wire:loading.attr="disabled">Quero a vaga</button>
                <button class="btn btn-contorno" wire:click="sairDaFila({{ $oferta->id }})" wire:confirm="Recusar a vaga e sair da fila desta aula?">Não quero</button>
                <span class="numero ml-auto text-3xl" x-text="texto" aria-label="Tempo para confirmar"></span>
            </div>
        </section>
    @endforeach

    <section aria-labelledby="titulo-proxima">
        <h2 id="titulo-proxima" class="mb-3 text-lg font-bold">Sua próxima aula</h2>
        @if ($proxima)
            @php $prazo = $proxima->prazoDeCancelamento(); @endphp
            <article class="relative overflow-hidden rounded-[22px] bg-ameixa p-5 text-branco md:p-7" wire:key="proxima-{{ $proxima->id }}">
                <span class="absolute inset-y-0 left-0 w-2" style="background: {{ $proxima->aula->modalidade->cor }}"></span>
                <p class="font-semibold text-lilas-3">{{ ucfirst($dia($proxima->aula->inicio)) }}</p>
                <p class="numero mt-1 text-[5.5rem] text-lima md:text-[7rem]">{{ $proxima->aula->inicio->format('H:i') }}</p>
                <p class="mt-2 text-lg font-semibold">{{ $proxima->aula->modalidade->nome }} <span class="text-lilas-3">· com {{ $proxima->aula->instrutora->primeiroNome() }} · {{ $proxima->aula->nivel }}</span></p>
                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-ameixa-2 pt-4">
                    <p class="min-w-0 flex-1 text-[0.95rem] text-lilas-2">
                        @if ($proxima->dentroDoPrazo())
                            Desmarque até <strong class="text-branco">{{ $prazo->isToday() ? $prazo->format('H:i').' de hoje' : $prazo->translatedFormat('l, H:i') }}</strong> e a aula volta para o seu saldo.
                        @else
                            O prazo para desmarcar com devolução acabou às {{ $prazo->format('H:i') }}.
                        @endif
                    </p>
                    <button class="btn btn-pequeno border-lilas-3 text-branco hover:bg-ameixa-2" wire:click="$dispatch('pedir-desmarcar', { reserva: {{ $proxima->id }} })">Desmarcar</button>
                </div>
            </article>
        @else
            <div class="cartao flex flex-wrap items-center gap-4 p-5">
                <p class="flex-1 text-ameixa-2">Nenhuma aula marcada.@if ($saldo) Você tem {{ $saldo }} {{ $saldo === 1 ? 'aula disponível' : 'aulas disponíveis' }}.@endif</p>
                <a href="{{ route('aluna.agenda') }}" wire:navigate class="btn btn-primario">Ver horários</a>
            </div>
        @endif

        @if ($depois->isNotEmpty())
            <ul class="mt-3 grid gap-2">
                @foreach ($depois as $reserva)
                    <li class="cartao borda-modalidade flex items-center gap-4 px-4 py-3" style="--cor-modalidade: {{ $reserva->aula->modalidade->cor }}" wire:key="r-{{ $reserva->id }}">
                        <span class="numero w-16 text-3xl">{{ $reserva->aula->inicio->format('H:i') }}</span>
                        <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $reserva->aula->modalidade->nome }}</span><span class="text-sm text-suave">{{ ucfirst($dia($reserva->aula->inicio)) }} · {{ $reserva->aula->instrutora->primeiroNome() }}</span></span>
                        <button class="text-sm font-semibold text-ameixa-2 underline decoration-lilas-3 underline-offset-4 hover:decoration-ameixa" wire:click="$dispatch('pedir-desmarcar', { reserva: {{ $reserva->id }} })">Desmarcar</button>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section aria-labelledby="titulo-carteira">
        <div class="mb-3 flex items-baseline justify-between gap-3">
            <h2 id="titulo-carteira" class="text-lg font-bold">Suas aulas</h2>
            <a href="{{ route('aluna.extrato') }}" wire:navigate class="text-sm font-semibold text-ameixa-2 underline decoration-lilas-3 underline-offset-4">Ver extrato</a>
        </div>
        <div class="cartao grid gap-5 p-5">
            <p><span class="numero text-5xl">{{ $saldo }}</span> <span class="text-lg font-semibold">{{ $saldo === 1 ? 'aula disponível' : 'aulas disponíveis' }}</span></p>

            @if ($plano = $this->loteDoPlano)
                <div>
                    <div class="flex flex-wrap gap-1.5" aria-hidden="true">
                        @for ($i = 0; $i < $plano->quantidade; $i++)
                            <span @class(['ficha', 'ficha-usada' => $i >= $plano->saldo])></span>
                        @endfor
                    </div>
                    <p class="mt-2 text-sm text-suave">Plano: {{ $plano->saldo }} de {{ $plano->quantidade }} sobrando, valem até {{ $plano->valido_ate->translatedFormat('l, d/m') }}.</p>
                </div>
            @endif

            @foreach ($this->lotes->where('origem', '!=', \App\Enums\OrigemLote::Ciclo) as $lote)
                <div class="flex items-center gap-3" wire:key="lote-{{ $lote->id }}">
                    <div class="flex gap-1.5" aria-hidden="true">@for ($i = 0; $i < $lote->saldo; $i++)<span class="ficha ficha-reposicao"></span>@endfor</div>
                    <p class="text-sm text-suave"><span class="font-semibold text-ameixa">{{ $lote->origem->rotulo() }}</span>: {{ $lote->saldo }} {{ $lote->saldo === 1 ? 'aula' : 'aulas' }}, até {{ $lote->valido_ate->format('d/m') }}.</p>
                </div>
            @endforeach

            @if ($pix = $this->pix)
                <a href="{{ route('aluna.plano') }}" wire:navigate class="flex items-center gap-3 rounded-2xl bg-lima-2 p-4 hover:bg-lima">
                    <x-icone nome="pix" />
                    <span class="flex-1 text-[0.95rem]">Seu plano renova em <strong>{{ $pix->ciclo_inicio?->format('d/m') }}</strong>. O Pix de {{ \App\Support\Dinheiro::formatar($pix->valor_centavos) }} já está disponível.</span>
                    <x-icone nome="seta" />
                </a>
            @endif
        </div>
    </section>

    @if ($this->filas->isNotEmpty())
        <section aria-labelledby="titulo-fila">
            <h2 id="titulo-fila" class="mb-3 text-lg font-bold">Fila de espera</h2>
            <ul class="grid gap-2">
                @foreach ($this->filas as $entrada)
                    <li class="cartao flex flex-wrap items-center gap-3 px-4 py-3" wire:key="fila-{{ $entrada->id }}">
                        <span class="flex size-11 flex-col items-center justify-center rounded-full bg-ameixa text-lima"><span class="numero text-xl">{{ $entrada->posicao() }}ª</span></span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $entrada->aula->modalidade->nome }}, {{ $dia($entrada->aula->inicio) }} às {{ $entrada->aula->inicio->format('H:i') }}</span>
                            <span class="text-sm text-suave">{{ $entrada->modo->rotulo() }}</span>
                        </span>
                        <button class="text-sm font-semibold text-ameixa-2 underline decoration-lilas-3 underline-offset-4" wire:click="sairDaFila({{ $entrada->id }})">Sair da fila</button>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

    <livewire:aluna.desmarcar />
</div>
