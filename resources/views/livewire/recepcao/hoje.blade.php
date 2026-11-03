@php
    $aulas = $this->aulas->reject->cancelada();
    $restantes = $aulas->filter(fn ($a) => now()->lessThan($a->fim));
    $capacidade = $aulas->sum('capacidade');
    $p = $this->pendencias;
@endphp
<div class="mx-auto grid max-w-6xl gap-6" wire:poll.30s="atualizar">
    <header class="flex flex-wrap items-end gap-4">
        <div class="min-w-0 flex-1">
            <p class="font-semibold text-suave">{{ ucfirst(now()->translatedFormat('l')) }}</p>
            <h1 class="titulo text-5xl">{{ now()->translatedFormat('j \d\e F') }}</h1>
        </div>
        <dl class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            @foreach ([
                ['Aulas hoje', $aulas->count()],
                ['Alunas ainda hoje', $restantes->sum('ocupadas')],
                ['Ocupação do dia', $capacidade ? round($aulas->sum('ocupadas') / $capacidade * 100).'%' : '–'],
                ['Na fila', $aulas->sum('fila_count')],
            ] as [$rotulo, $valor])
                <div class="cartao px-4 py-3"><dt class="text-xs font-semibold text-suave">{{ $rotulo }}</dt><dd class="numero mt-1 text-3xl">{{ $valor }}</dd></div>
            @endforeach
        </dl>
    </header>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <section aria-labelledby="titulo-aulas">
            <h2 id="titulo-aulas" class="mb-3 text-lg font-bold">Aulas do dia</h2>
            <ol class="grid gap-2">
                @forelse ($this->aulas as $aula)
                    @php
                        $estado = $aula->cancelada() ? 'cancelada' : (now()->greaterThan($aula->fim) ? 'passou' : ($aula->comecou() ? 'agora' : 'depois'));
                    @endphp
                    <li wire:key="a-{{ $aula->id }}">
                        <button type="button" wire:click="$dispatch('abrir-aula', { aula: {{ $aula->id }} })"
                                @class(['cartao borda-modalidade grid w-full grid-cols-[auto_1fr] items-center gap-x-4 gap-y-2 px-4 py-3 text-left transition hover:shadow-[0_10px_28px_-18px_rgb(42_24_56/.6)] sm:grid-cols-[auto_1fr_auto]', 'opacity-55' => in_array($estado, ['passou', 'cancelada'])])
                                style="--cor-modalidade: {{ $aula->modalidade->cor }}">
                            <span class="numero w-[4.5rem] text-[2rem]">{{ $aula->inicio->format('H:i') }}</span>
                            <span class="min-w-0">
                                <span class="flex flex-wrap items-center gap-2 font-bold">{{ $aula->modalidade->nome }}
                                    @if ($estado === 'agora')<span class="selo selo-ameixa">Acontecendo</span>@endif
                                    @if ($estado === 'cancelada')<span class="selo selo-alerta">Cancelada</span>@endif
                                </span>
                                <span class="text-sm text-suave">{{ $aula->nivel }} · {{ $aula->instrutora->primeiroNome() }}</span>
                            </span>
                            <span class="col-start-2 flex items-center gap-3 sm:col-start-3">
                                <span class="flex w-28 gap-1" aria-label="{{ $aula->ocupadas }} de {{ $aula->capacidade }}">@for ($i = 0; $i < $aula->capacidade; $i++)<span @class(['vaga-bloco', 'vaga-bloco-ocupada' => $i < $aula->ocupadas])></span>@endfor</span>
                                <span class="numero w-10 text-right text-xl">{{ $aula->ocupadas }}/{{ $aula->capacidade }}</span>
                                @if ($aula->fila_count)<span class="selo selo-ameixa" title="Na fila de espera">+{{ $aula->fila_count }}</span>@endif
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="rounded-[18px] border-2 border-dashed border-lilas-3 p-6 text-center text-suave">Sem aulas hoje.</li>
                @endforelse
            </ol>
        </section>

        <aside class="grid content-start gap-4" aria-label="Pendências">
            <h2 class="text-lg font-bold">Para resolver</h2>

            @foreach ($p['divergentes'] as $c)
                <a href="{{ route('recepcao.cobrancas', ['situacao' => 'divergente']) }}" wire:navigate class="flex gap-3 rounded-2xl bg-pessego p-4 text-pessego-tinta">
                    <x-icone nome="alerta" class="mt-0.5" />
                    <span class="text-sm"><strong>{{ $c->aluna->name }}</strong> pagou {{ \App\Support\Dinheiro::formatar($c->valor_pago_centavos) }} num Pix de {{ \App\Support\Dinheiro::formatar($c->valor_centavos) }}. O ciclo está segurado até você conferir.</span>
                </a>
            @endforeach

            @if ($p['ofertas']->isNotEmpty())
                <div class="cartao p-4">
                    <p class="mb-2 text-sm font-bold">Vagas oferecidas, esperando resposta</p>
                    <ul class="grid gap-1.5 text-sm">
                        @foreach ($p['ofertas'] as $o)
                            <li><strong>{{ $o->aluna->primeiroNome() }}</strong> · {{ $o->aula->modalidade->nome }} {{ $o->aula->inicio->format('H:i') }} · até {{ $o->oferta_expira_em->format('H:i') }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($p['vencendo']->isNotEmpty())
                <div class="cartao p-4">
                    <p class="mb-2 text-sm font-bold">Aulas vencendo em até 3 dias</p>
                    <ul class="grid gap-1.5 text-sm">
                        @foreach ($p['vencendo']->take(6) as $l)
                            <li class="flex gap-2"><a href="{{ route('recepcao.aluna', $l->aluna) }}" wire:navigate class="flex-1 font-semibold hover:underline">{{ $l->aluna->name }}</a><span class="text-suave">{{ $l->saldo }} até {{ $l->valido_ate->format('d/m') }}</span></li>
                        @endforeach
                    </ul>
                    <p class="mt-2 text-xs text-suave">Elas já receberam o aviso com um horário livre sugerido.</p>
                </div>
            @endif

            <a href="{{ route('recepcao.cobrancas') }}" wire:navigate class="cartao flex items-center gap-3 p-4 hover:bg-lilas-2">
                <x-icone nome="pix" />
                <span class="flex-1 text-sm"><strong>{{ $p['pix_pendentes'] }}</strong> Pix de renovação aguardando pagamento</span>
                <x-icone nome="seta" :tamanho="18" />
            </a>
        </aside>
    </div>

    <livewire:recepcao.painel-da-aula />
</div>
