@php
    // Escala sequencial de um só tom (lilás claro → ameixa): mais ocupado, mais escuro.
    $tons = ['#f4effa', '#ddd0f0', '#bda6e2', '#8a6bb8', '#4a3560'];
    $tom = fn (float $v) => $tons[min(4, (int) floor($v * 5))];
    $dias = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb'];
    $o = $this->ocupacao;
    $d = $this->dinheiro;
    $maxFila = max(1, $this->filas->max('total') ?? 1);
@endphp
<div class="mx-auto grid max-w-6xl gap-6">
    <header>
        <h1 class="titulo text-5xl">Relatórios</h1>
        <p class="mt-1 text-suave">Últimos {{ \App\Livewire\Recepcao\Relatorios::DIAS }} dias.</p>
    </header>

    <dl class="grid gap-3 sm:grid-cols-3">
        <div class="cartao p-5"><dt class="text-sm font-semibold text-suave">Recebido nos últimos 30 dias</dt><dd class="numero mt-2 text-4xl">{{ \App\Support\Dinheiro::formatar($d['recebido']) }}</dd></div>
        <div class="cartao p-5"><dt class="text-sm font-semibold text-suave">Pix de renovação em aberto</dt><dd class="numero mt-2 text-4xl">{{ \App\Support\Dinheiro::formatar($d['a_receber']) }}</dd></div>
        <div class="cartao p-5"><dt class="text-sm font-semibold text-suave">Diferenças para conferir</dt><dd class="numero mt-2 text-4xl {{ $d['divergente'] ? 'text-alerta' : '' }}">{{ \App\Support\Dinheiro::formatar($d['divergente']) }}</dd></div>
    </dl>

    <section class="cartao p-5" aria-labelledby="t-ocupacao">
        <div class="flex flex-wrap items-end gap-3">
            <h2 id="t-ocupacao" class="flex-1 text-lg font-bold">Ocupação média por horário</h2>
            <div class="flex items-center gap-1 text-xs text-suave" aria-hidden="true">vazia @foreach ($tons as $t)<span class="h-3 w-6 rounded-sm" style="background: {{ $t }}"></span>@endforeach cheia</div>
        </div>
        <div class="mt-4 overflow-x-auto">
            <table class="w-full min-w-[560px] border-separate border-spacing-1 text-center text-sm">
                <thead><tr><th class="w-16"></th>@foreach ($dias as $nome)<th scope="col" class="pb-1 font-semibold text-suave">{{ $nome }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($o['horarios'] as $h)
                        <tr>
                            <th scope="row" class="numero pr-2 text-left text-lg">{{ $h }}</th>
                            @foreach ($dias as $n => $nome)
                                @php $c = $o['celulas'][$n.' '.$h] ?? null; @endphp
                                @if ($c)
                                    <td class="h-11 rounded-md font-bold" style="background: {{ $tom($c['media']) }}; color: {{ $c['media'] >= 0.6 ? '#fffdfd' : '#2a1838' }}" title="{{ $nome }} {{ $h }}: {{ round($c['media'] * 100) }}% em {{ $c['aulas'] }} aulas">{{ round($c['media'] * 100) }}%</td>
                                @else
                                    <td class="h-11 rounded-md bg-lilas text-xs text-suave">–</td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid gap-6 lg:grid-cols-2">
        <section class="cartao p-5" aria-labelledby="t-fila">
            <h2 id="t-fila" class="text-lg font-bold">Onde a fila se repete</h2>
            <p class="mt-1 text-sm text-suave">Pedidos de vaga em aulas lotadas. Fila toda semana no mesmo horário é sinal de turma nova.</p>
            <ul class="mt-4 grid gap-3">
                @forelse ($this->filas as $f)
                    <li>
                        <div class="flex justify-between gap-3 text-sm"><span class="font-semibold">{{ $f['rotulo'] }}</span><span class="text-suave">{{ $f['total'] }} {{ $f['total'] === 1 ? 'pedido' : 'pedidos' }} em {{ $f['aulas'] }} {{ $f['aulas'] === 1 ? 'aula' : 'aulas' }}</span></div>
                        <div class="mt-1 h-2.5 rounded-full bg-lilas"><div class="h-2.5 rounded-full bg-ameixa-2" style="width: {{ round($f['total'] / $maxFila * 100) }}%"></div></div>
                    </li>
                @empty
                    <li class="text-sm text-suave">Nenhuma fila no período.</li>
                @endforelse
            </ul>
        </section>

        <section class="cartao p-5" aria-labelledby="t-faltas">
            <h2 id="t-faltas" class="text-lg font-bold">Faltas por modalidade</h2>
            <p class="mt-1 text-sm text-suave">Reservas que terminaram em falta, sobre as aulas que aconteceram.</p>
            <ul class="mt-4 grid gap-3">
                @foreach ($this->faltas as $f)
                    <li>
                        <div class="flex justify-between gap-3 text-sm"><span class="flex items-center gap-2 font-semibold"><span class="size-2.5 rounded-full" style="background: {{ $f['cor'] }}"></span>{{ $f['nome'] }}</span><span class="text-suave">{{ round($f['taxa'] * 100) }}% de {{ $f['total'] }}</span></div>
                        <div class="mt-1 h-2.5 rounded-full bg-lilas"><div class="h-2.5 rounded-full bg-ameixa-2" style="width: {{ max(2, round($f['taxa'] * 100 * 4)) }}%" title="escala de 0 a 25%"></div></div>
                    </li>
                @endforeach
            </ul>
            <p class="mt-3 text-xs text-suave">Barras na escala de 0 a 25%.</p>
        </section>
    </div>
</div>
