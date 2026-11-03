<div>
    @if ($aula = $this->aula)
        @php
            $ativas = $this->lista->filter(fn ($r) => $r->status->ocupaVaga());
            $canceladas = $this->lista->reject(fn ($r) => $r->status->ocupaVaga());
            $podePresenca = ! $aula->cancelada() && $aula->fechada_em === null && now()->greaterThanOrEqualTo($aula->inicio->subMinutes(30));
        @endphp
        <div class="fixed inset-0 z-[60] flex justify-end bg-ameixa/40" x-on:keydown.escape.window="$wire.fechar()">
            <div class="absolute inset-0" wire:click="fechar"></div>
            <section class="relative flex h-full w-full max-w-xl flex-col overflow-y-auto bg-lilas shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="titulo-painel">
                <header class="sticky top-0 z-10 border-b-[6px] bg-branco px-5 pb-4 pt-5" style="border-color: {{ $aula->modalidade->cor }}">
                    <div class="flex items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-suave">{{ ucfirst($aula->inicio->translatedFormat('l, d/m')) }} · com {{ $aula->instrutora->name }}</p>
                            <h2 id="titulo-painel" class="titulo mt-1 text-4xl"><span class="numero">{{ $aula->inicio->format('H:i') }}</span> {{ $aula->modalidade->nome }}</h2>
                            <p class="mt-1 text-sm text-suave">{{ $aula->nivel }} · {{ $aula->ocupadas }} de {{ $aula->capacidade }} vagas ocupadas{{ $this->fila->count() ? ' · '.$this->fila->count().' na fila' : '' }}</p>
                        </div>
                        <button class="rounded-lg p-2 hover:bg-lilas-2" wire:click="fechar" aria-label="Fechar"><x-icone nome="x" /></button>
                    </div>
                    @if ($aula->cancelada())
                        <p class="mt-3 rounded-xl bg-alerta-2 px-3 py-2 text-sm font-semibold text-alerta">Cancelada: {{ $aula->motivo_cancelamento }}</p>
                    @endif
                </header>

                <div class="grid gap-6 p-5">
                    <section aria-labelledby="titulo-lista">
                        <div class="mb-2 flex items-baseline justify-between">
                            <h3 id="titulo-lista" class="font-bold">Lista da aula</h3>
                            @if ($podePresenca)<span class="text-xs text-suave">Toque no nome para marcar presença</span>@endif
                        </div>
                        <ul class="cartao divide-y divide-linha">
                            @forelse ($ativas as $r)
                                <li class="flex items-center gap-3 px-4 py-3" wire:key="r-{{ $r->id }}">
                                    @if ($podePresenca && in_array($r->status->value, ['confirmada', 'presente']))
                                        <button type="button" wire:click="presenca({{ $r->id }}, {{ $r->status->value === 'presente' ? 'false' : 'true' }})"
                                                @class(['flex size-9 shrink-0 items-center justify-center rounded-full border-2', 'border-ameixa bg-lima' => $r->status->value === 'presente', 'border-linha' => $r->status->value !== 'presente'])
                                                aria-pressed="{{ $r->status->value === 'presente' ? 'true' : 'false' }}" aria-label="Presença de {{ $r->aluna->name }}">
                                            @if ($r->status->value === 'presente')<x-icone nome="check" :tamanho="18" />@endif
                                        </button>
                                    @else
                                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-lilas-2 text-xs font-bold">{{ $r->aluna->iniciais() }}</span>
                                    @endif
                                    <span class="min-w-0 flex-1">
                                        <a href="{{ route('recepcao.aluna', $r->aluna) }}" wire:navigate class="block truncate font-semibold hover:underline">{{ $r->aluna->name }}</a>
                                        <span class="text-xs text-suave">
                                            {{ $r->origem->value === 'fila' ? 'Veio da fila' : ($r->origem->value === 'recepcao' ? 'Encaixada pela recepção' : 'Reservou pelo app') }}@if ($r->lote && $r->lote->origem->value !== 'ciclo') · aula de {{ mb_strtolower($r->lote->origem->rotulo()) }}@endif
                                        </span>
                                    </span>
                                    @if ($r->status->value === 'falta')
                                        <span class="selo selo-alerta">Falta</span>
                                    @elseif ($r->status->value === 'presente')
                                        <span class="selo selo-vaga">Presente</span>
                                    @elseif (! $aula->comecou())
                                        <button class="text-sm font-semibold text-ameixa-2 underline decoration-lilas-3 underline-offset-4" wire:click="$set('desmarcando', {{ $r->id }})">Desmarcar</button>
                                    @endif
                                </li>
                                @if ($desmarcando === $r->id)
                                    <li class="grid gap-3 bg-lilas-2/50 px-4 py-3" wire:key="d-{{ $r->id }}">
                                        <p class="text-sm">{{ $r->dentroDoPrazo() ? 'Está no prazo: a aula volta para '.$r->aluna->primeiroNome().'.' : 'Fora do prazo: a aula não volta, a não ser que você abone (atestado, imprevisto do estúdio).' }}</p>
                                        @unless ($r->dentroDoPrazo())
                                            <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" wire:model="abonar" class="size-4 accent-[#2a1838]"> Abonar e devolver a aula</label>
                                        @endunless
                                        <div class="flex gap-2">
                                            <button class="btn btn-primario btn-pequeno" wire:click="desmarcar">Desmarcar</button>
                                            <button class="btn btn-contorno btn-pequeno" wire:click="$set('desmarcando', null)">Voltar</button>
                                        </div>
                                    </li>
                                @endif
                            @empty
                                <li class="px-4 py-5 text-center text-sm text-suave">Ninguém reservou ainda.</li>
                            @endforelse
                        </ul>
                    </section>

                    @if (! $aula->cancelada() && ! $aula->comecou())
                        <section aria-labelledby="titulo-encaixar">
                            <h3 id="titulo-encaixar" class="mb-2 font-bold">Encaixar uma aluna</h3>
                            @if ($aula->lotada())
                                <p class="cartao px-4 py-3 text-sm text-suave">A aula está cheia: {{ $aula->capacidade }} {{ $aula->modalidade->slug === 'aparelhos' ? 'aparelhos' : 'vagas' }}, {{ $aula->capacidade }} alunas. Para entrar alguém, outra precisa desmarcar.</p>
                            @else
                                <input type="search" wire:model.live.debounce.250ms="busca" class="campo" placeholder="Digite o nome da aluna" aria-label="Buscar aluna">
                                @if ($this->sugestoes->isNotEmpty())
                                    <ul class="cartao mt-2 divide-y divide-linha">
                                        @foreach ($this->sugestoes as $s)
                                            <li class="flex items-center gap-3 px-4 py-2.5" wire:key="s-{{ $s->id }}">
                                                <span class="flex-1 font-semibold">{{ $s->name }}</span>
                                                <button class="btn btn-lima btn-pequeno" wire:click="encaixar({{ $s->id }})">Encaixar</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            @endif
                        </section>
                    @endif

                    @if ($this->fila->isNotEmpty())
                        <section aria-labelledby="titulo-fila-painel">
                            <h3 id="titulo-fila-painel" class="mb-2 font-bold">Fila de espera</h3>
                            <ol class="cartao divide-y divide-linha">
                                @foreach ($this->fila as $i => $e)
                                    <li class="flex items-center gap-3 px-4 py-3" wire:key="f-{{ $e->id }}">
                                        <span class="numero w-7 text-2xl">{{ $i + 1 }}ª</span>
                                        <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $e->aluna->name }}</span><span class="text-xs text-suave">{{ $e->modo->rotulo() }}</span></span>
                                        @if ($e->status->value === 'oferta')
                                            <span class="selo selo-poucas">Oferta até {{ $e->oferta_expira_em->format('H:i') }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    @if ($canceladas->isNotEmpty())
                        <section aria-labelledby="titulo-canceladas">
                            <h3 id="titulo-canceladas" class="mb-2 font-bold">Desmarcaram</h3>
                            <ul class="grid gap-1 text-sm text-suave">
                                @foreach ($canceladas as $r)
                                    <li>{{ $r->aluna->name }}: {{ mb_strtolower($r->status->rotulo()) }}{{ $r->cancelada_em ? ', '.$r->cancelada_em->translatedFormat('D H:i') : '' }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if (! $aula->cancelada() && ! $aula->comecou())
                        <section class="border-t border-linha pt-5">
                            @if ($cancelandoAula)
                                <form wire:submit="cancelarAula" class="grid gap-3">
                                    <label class="grid gap-1.5 font-semibold">Motivo do cancelamento
                                        <input wire:model="motivo" class="campo font-normal" placeholder="Ex.: falta de energia no prédio" maxlength="120">
                                        @error('motivo')<span class="text-sm font-normal text-alerta">{{ $message }}</span>@enderror
                                    </label>
                                    <p class="text-sm text-suave">As {{ $ativas->count() }} alunas recebem a aula de volta e uma mensagem com o motivo. A fila é encerrada.</p>
                                    <div class="flex gap-2"><button class="btn btn-perigo btn-pequeno">Cancelar a aula</button><button type="button" class="btn btn-contorno btn-pequeno" wire:click="$set('cancelandoAula', false)">Voltar</button></div>
                                </form>
                            @else
                                <button class="text-sm font-semibold text-alerta underline underline-offset-4" wire:click="$set('cancelandoAula', true)">Cancelar esta aula</button>
                            @endif
                        </section>
                    @endif
                </div>
            </section>
        </div>
    @endif
</div>
