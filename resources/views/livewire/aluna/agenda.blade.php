@php
    $permitidas = auth()->user()->matriculaAtual?->modalidadesPermitidas();
    $abreEm = fn ($aula) => $aula->inicio->subDays(config('talo.reserva.abre_dias_antes'));
@endphp
<div class="grid gap-5 pb-6">
    <h1 class="titulo text-[2.6rem] md:text-5xl">Agenda</h1>

    <nav class="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-1" aria-label="Dias">
        @foreach ($this->dias as $d)
            <button type="button" wire:click="$set('dia', '{{ $d->toDateString() }}')" aria-pressed="{{ $d->toDateString() === $dia ? 'true' : 'false' }}"
                    @class(['flex min-w-[3.6rem] flex-col items-center rounded-2xl px-3 py-2 font-semibold', 'bg-ameixa text-branco' => $d->toDateString() === $dia, 'bg-branco hover:bg-lilas-2' => $d->toDateString() !== $dia])>
                <span class="text-xs">{{ $d->isToday() ? 'hoje' : $d->translatedFormat('D') }}</span>
                <span class="numero text-2xl">{{ $d->format('d') }}</span>
            </button>
        @endforeach
    </nav>

    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Modalidade">
        <button type="button" class="chip" aria-pressed="{{ $modalidade === '' ? 'true' : 'false' }}" wire:click="$set('modalidade', '')">Todas</button>
        @foreach ($this->modalidades as $m)
            <button type="button" class="chip" aria-pressed="{{ $modalidade === $m->slug ? 'true' : 'false' }}" wire:click="$set('modalidade', '{{ $m->slug }}')">
                <span class="size-2.5 rounded-full" style="background: {{ $m->cor }}"></span>{{ $m->nome }}
            </button>
        @endforeach
    </div>

    <ul class="grid gap-2.5" wire:loading.class="opacity-60" wire:target="dia,modalidade">
        @forelse ($this->aulas as $aula)
            @php
                $reserva = $this->minhas['reservas'][$aula->id] ?? null;
                $fila = $this->minhas['filas'][$aula->id] ?? null;
                $vou = $reserva && $reserva->status->ocupaVaga();
                $valeNoPlano = $permitidas === null || in_array($aula->modalidade->slug, $permitidas, true);
                $passou = $aula->comecou();
            @endphp
            <li class="cartao borda-modalidade grid grid-cols-[auto_1fr] gap-x-4 gap-y-3 p-4 sm:grid-cols-[auto_1fr_auto] sm:items-center" style="--cor-modalidade: {{ $aula->modalidade->cor }}" wire:key="aula-{{ $aula->id }}">
                <span class="numero pt-0.5 text-[2.1rem] sm:text-[2.4rem]">{{ $aula->inicio->format('H:i') }}</span>
                <div class="min-w-0">
                    <p class="font-bold">{{ $aula->modalidade->nome }} <span class="font-normal text-suave">· {{ $aula->nivel }} · com {{ $aula->instrutora->primeiroNome() }}</span></p>
                    <div class="mt-2 flex max-w-[14rem] gap-1" aria-label="{{ $aula->ocupadas }} de {{ $aula->capacidade }} vagas ocupadas">
                        @for ($i = 0; $i < $aula->capacidade; $i++)<span @class(['vaga-bloco', 'vaga-bloco-ocupada' => $i < $aula->ocupadas])></span>@endfor
                    </div>
                    <p class="mt-1.5 text-sm text-suave">
                        @if ($aula->cancelada()) Cancelada pelo estúdio: {{ $aula->motivo_cancelamento }}
                        @elseif ($aula->lotada()) Lotada{{ $aula->fila()->count() ? ', '.$aula->fila()->count().' na fila' : '' }}
                        @else {{ $aula->vagas() }} {{ $aula->vagas() === 1 ? 'vaga' : 'vagas' }}
                        @endif
                    </p>
                </div>
                <div class="col-start-2 flex flex-wrap items-center gap-2 sm:col-start-3 sm:justify-end">
                    @if ($vou)
                        <span class="selo selo-vaga"><x-icone nome="check" :tamanho="14" />Você vai</span>
                        @unless ($passou)
                            <button class="text-sm font-semibold underline decoration-lilas-3 underline-offset-4" wire:click="$dispatch('pedir-desmarcar', { reserva: {{ $reserva->id }} })">Desmarcar</button>
                        @endunless
                    @elseif ($fila)
                        <span class="selo selo-ameixa">{{ $fila->posicao() }}ª na fila</span>
                        <button class="text-sm font-semibold underline decoration-lilas-3 underline-offset-4" wire:click="sairDaFila({{ $fila->id }})">Sair</button>
                    @elseif ($aula->cancelada())
                        <span class="selo selo-alerta">Cancelada</span>
                    @elseif ($passou)
                        <span class="text-sm text-suave">{{ now()->greaterThan($aula->fim) ? 'Aconteceu' : 'Acontecendo agora' }}</span>
                    @elseif (now()->lessThan($abreEm($aula)))
                        <span class="text-sm text-suave">Abre {{ $abreEm($aula)->translatedFormat('D d/m, H:i') }}</span>
                    @elseif (! $valeNoPlano)
                        <span class="text-sm text-suave">Seu plano não inclui {{ $aula->modalidade->nome }}</span>
                    @elseif ($aula->lotada())
                        <button class="btn btn-contorno btn-pequeno" wire:click="escolherFila({{ $aula->id }})"><x-icone nome="fila" :tamanho="16" />Entrar na fila</button>
                    @elseif (! $this->pagaCom($aula))
                        <a href="{{ route('aluna.plano') }}" wire:navigate class="max-w-[16rem] text-right text-sm text-suave">Sem aula no saldo para {{ $aula->inicio->format('d/m') }}. <span class="font-semibold text-ameixa underline">Renovar o plano</span></a>
                    @else
                        <button class="btn btn-primario btn-pequeno" wire:click="reservar({{ $aula->id }})" wire:loading.attr="disabled" wire:target="reservar({{ $aula->id }})">Reservar</button>
                    @endif
                </div>
            </li>
        @empty
            <li class="rounded-[18px] border-2 border-dashed border-lilas-3 p-6 text-center text-suave">Nenhuma aula {{ $modalidade ? 'dessa modalidade ' : '' }}neste dia.</li>
        @endforelse
    </ul>

    @if ($filaPara && ($aulaFila = $this->aulas->firstWhere('id', $filaPara)))
        <div class="fixed inset-0 z-[60] flex items-end justify-center bg-ameixa/45 md:items-center" x-on:keydown.escape.window="$wire.set('filaPara', null)">
            <div class="absolute inset-0" wire:click="$set('filaPara', null)"></div>
            <form wire:submit="entrarNaFila" class="relative w-full max-w-md rounded-t-[26px] bg-branco p-6 md:rounded-[26px]" role="dialog" aria-modal="true" aria-labelledby="titulo-fila">
                <h2 id="titulo-fila" class="titulo text-3xl">Fila de {{ $aulaFila->modalidade->nome }}, {{ $aulaFila->inicio->translatedFormat('l') }} às {{ $aulaFila->inicio->format('H:i') }}</h2>
                <p class="mt-2 text-ameixa-2">{{ $aulaFila->fila()->count() ? 'Há '.$aulaFila->fila()->count().' antes de você.' : 'Você será a primeira da fila.' }} Se alguém desmarcar, como você prefere?</p>
                <div class="mt-4 grid gap-2">
                    <label @class(['flex cursor-pointer gap-3 rounded-2xl border-2 p-4', 'border-ameixa bg-lilas' => $modo === 'avisar', 'border-linha' => $modo !== 'avisar'])>
                        <input type="radio" wire:model.live="modo" value="avisar" class="mt-1 accent-[#2a1838]">
                        <span><span class="block font-bold">Me avisar para eu confirmar</span><span class="text-sm text-suave">A oferta chega no WhatsApp e você tem {{ config('talo.fila.oferta_minutos') }} minutos para confirmar.</span></span>
                    </label>
                    <label @class(['flex cursor-pointer gap-3 rounded-2xl border-2 p-4', 'border-ameixa bg-lilas' => $modo === 'automatico', 'border-linha' => $modo !== 'automatico'])>
                        <input type="radio" wire:model.live="modo" value="automatico" class="mt-1 accent-[#2a1838]">
                        <span><span class="block font-bold">Reservar sozinha se vagar</span><span class="text-sm text-suave">Vagando com mais de {{ config('talo.fila.automatico_ate_minutos_antes') / 60 }} horas de antecedência, a vaga já fica sua e uma aula sai do saldo.</span></span>
                    </label>
                </div>
                <div class="mt-5 grid grid-cols-2 gap-2">
                    <button type="button" class="btn btn-contorno" wire:click="$set('filaPara', null)">Agora não</button>
                    <button class="btn btn-primario" wire:loading.attr="disabled">Entrar na fila</button>
                </div>
            </form>
        </div>
    @endif

    <livewire:aluna.desmarcar />
</div>
