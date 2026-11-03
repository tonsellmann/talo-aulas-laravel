@php
    $aula = $this->aula;
    $podePresenca = $aula && $aula->fechada_em === null && now()->greaterThanOrEqualTo($aula->inicio->subMinutes(30));
@endphp
<div class="grid gap-6">
    <header>
        <h1 class="titulo text-5xl">Bom trabalho, {{ auth()->user()->primeiroNome() }}</h1>
        <p class="mt-1 text-suave">{{ ucfirst(now()->translatedFormat('l, j \d\e F')) }} · {{ $this->aulas->count() }} {{ $this->aulas->count() === 1 ? 'aula' : 'aulas' }} hoje</p>
    </header>

    @if ($this->aulas->isEmpty())
        <p class="cartao p-6 text-center text-suave">Você não tem aulas hoje.</p>
    @else
        <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-1" aria-label="Aulas de hoje">
            @foreach ($this->aulas as $a)
                <button type="button" wire:click="escolher({{ $a->id }})" aria-pressed="{{ $a->id === $aulaId ? 'true' : 'false' }}"
                        @class(['flex min-w-[7.5rem] flex-col items-start rounded-2xl px-4 py-3 text-left', 'bg-ameixa text-branco' => $a->id === $aulaId, 'bg-branco hover:bg-lilas-2' => $a->id !== $aulaId, 'opacity-60' => now()->greaterThan($a->fim) && $a->id !== $aulaId])>
                    <span class="numero text-3xl">{{ $a->inicio->format('H:i') }}</span>
                    <span class="text-sm font-semibold">{{ $a->modalidade->nome }}</span>
                    <span @class(['text-xs', 'text-lilas-3' => $a->id === $aulaId, 'text-suave' => $a->id !== $aulaId])>{{ $a->ocupadas }}/{{ $a->capacidade }} · {{ now()->greaterThan($a->fim) ? 'aconteceu' : ($a->comecou() ? 'agora' : 'depois') }}</span>
                </button>
            @endforeach
        </nav>
    @endif

    @if ($aula)
        <section aria-labelledby="t-lista">
            <div class="mb-3 flex flex-wrap items-baseline gap-3">
                <h2 id="t-lista" class="titulo flex-1 text-3xl">{{ $aula->modalidade->nome }} das {{ $aula->inicio->format('H:i') }}</h2>
                <p class="text-sm text-suave">
                    @if ($aula->fechada_em) Lista fechada: quem não teve presença ficou com falta.
                    @elseif ($podePresenca) Toque no círculo para marcar presença.
                    @else A presença abre às {{ $aula->inicio->subMinutes(30)->format('H:i') }}.
                    @endif
                </p>
            </div>
            <ul class="grid gap-2">
                @forelse ($this->lista as $r)
                    @php $temNota = in_array($r->aluna_id, $this->comNotas, true); $aberta = in_array($r->aluna_id, $notasAbertas, true); @endphp
                    <li class="cartao p-4" wire:key="r-{{ $r->id }}">
                        <div class="flex items-center gap-4">
                            <button type="button" @disabled(! $podePresenca || $r->status->value === 'falta') wire:click="presenca({{ $r->id }}, {{ $r->status->value === 'presente' ? 'false' : 'true' }})"
                                    @class(['flex size-14 shrink-0 items-center justify-center rounded-full border-[2.5px] transition', 'border-ameixa bg-lima' => $r->status->value === 'presente', 'border-linha' => $r->status->value === 'confirmada', 'border-alerta bg-alerta-2 text-alerta' => $r->status->value === 'falta', 'opacity-50' => ! $podePresenca && $r->status->value === 'confirmada'])
                                    aria-pressed="{{ $r->status->value === 'presente' ? 'true' : 'false' }}" aria-label="Presença de {{ $r->aluna->name }}">
                                @if ($r->status->value === 'presente')<x-icone nome="check" :tamanho="26" />@elseif ($r->status->value === 'falta')<x-icone nome="x" :tamanho="22" />@endif
                            </button>
                            <span class="min-w-0 flex-1">
                                <span class="block text-lg font-bold">{{ $r->aluna->name }}</span>
                                <span class="text-sm text-suave">{{ ['presente' => 'Presente', 'falta' => 'Falta', 'confirmada' => 'Reservou'][$r->status->value] ?? '' }}{{ $r->origem->value === 'fila' ? ' · veio da fila' : '' }}</span>
                            </span>
                            @if ($temNota && ! $aberta)
                                <button type="button" class="btn btn-contorno btn-pequeno" wire:click="abrirNota({{ $r->aluna_id }})"><x-icone nome="cadeado" :tamanho="16" />Saúde</button>
                            @endif
                        </div>
                        @if ($aberta)
                            <ul class="mt-3 grid gap-2 border-t border-linha pt-3">
                                @foreach ($this->notasDe($r->aluna_id) as $n)
                                    <li class="flex gap-2 rounded-xl bg-pessego/70 p-3 text-[0.95rem] text-pessego-tinta"><x-icone nome="alerta" class="mt-0.5" :tamanho="18" />{{ $n->texto }}</li>
                                @endforeach
                                <li class="text-xs text-suave">Sua leitura foi registrada às {{ now()->format('H:i') }}.</li>
                            </ul>
                        @endif
                    </li>
                @empty
                    <li class="cartao p-6 text-center text-suave">Ninguém reservou esta aula.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($this->amanha->isNotEmpty())
        <section aria-labelledby="t-amanha">
            <h2 id="t-amanha" class="mb-2 font-bold">Amanhã</h2>
            <p class="text-ameixa-2">{{ $this->amanha->map(fn ($a) => $a->inicio->format('H:i').' '.$a->modalidade->nome.' ('.$a->ocupadas.'/'.$a->capacidade.')')->join(' · ') }}</p>
        </section>
    @endif
</div>
