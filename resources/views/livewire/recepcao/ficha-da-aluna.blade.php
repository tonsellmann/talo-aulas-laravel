@php
    $m = $aluna->matriculaAtual;
    $saldo = $this->lotes->sum('saldo');
@endphp
<div class="mx-auto grid max-w-6xl gap-6">
    <header class="flex flex-wrap items-center gap-4">
        <a href="{{ route('recepcao.alunas') }}" wire:navigate class="rounded-full p-2 hover:bg-lilas-2" aria-label="Voltar para a lista"><x-icone nome="voltar" /></a>
        <span class="flex size-14 items-center justify-center rounded-full bg-lima text-lg font-extrabold">{{ $aluna->iniciais() }}</span>
        <div class="min-w-0 flex-1">
            <h1 class="titulo text-4xl">{{ $aluna->name }}</h1>
            <p class="text-sm text-suave">{{ $m?->plano->nome ?? 'Sem plano' }}@if ($m?->modalidade) em {{ $m->modalidade->nome }}@endif · WhatsApp {{ preg_replace('/^55(\d{2})(\d{5})(\d{4})$/', '($1) $2-$3', $aluna->telefone ?? '') }}</p>
        </div>
        <p class="text-right"><span class="numero text-5xl">{{ $saldo }}</span><span class="block text-sm text-suave">{{ $saldo === 1 ? 'aula no saldo' : 'aulas no saldo' }}</span></p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="grid content-start gap-6">
            <section aria-labelledby="t-proximas">
                <h2 id="t-proximas" class="mb-2 font-bold">Próximas aulas</h2>
                <ul class="cartao divide-y divide-linha">
                    @forelse ($this->proximas as $r)
                        <li class="flex items-center gap-4 px-4 py-3">
                            <span class="numero w-16 text-2xl">{{ $r->aula->inicio->format('H:i') }}</span>
                            <span class="flex-1"><span class="font-semibold">{{ $r->aula->modalidade->nome }}</span> <span class="text-sm text-suave">· {{ ucfirst($r->aula->inicio->translatedFormat('l, d/m')) }}</span></span>
                            <button class="text-sm font-semibold underline decoration-lilas-3 underline-offset-4" wire:click="$dispatch('abrir-aula', { aula: {{ $r->aula_id }} })">Abrir aula</button>
                        </li>
                    @empty
                        <li class="px-4 py-5 text-center text-sm text-suave">Nenhuma aula marcada.</li>
                    @endforelse
                </ul>
            </section>

            <section aria-labelledby="t-extrato">
                <h2 id="t-extrato" class="mb-2 font-bold">Extrato recente</h2>
                <ol class="cartao divide-y divide-linha">
                    @foreach ($this->movimentos as $mv)
                        <li class="flex items-center gap-3 px-4 py-2.5 text-sm">
                            <span @class(['numero w-8 text-center text-lg', 'text-suave' => $mv->quantidade === 0])>{{ $mv->quantidade > 0 ? '+'.$mv->quantidade : ($mv->quantidade ?: '·') }}</span>
                            <span class="flex-1">{{ $mv->descricao }}</span>
                            <span class="text-xs text-suave">{{ $mv->created_at->format('d/m H:i') }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="grid content-start gap-4">
            <section class="cartao p-4" aria-labelledby="t-lotes">
                <h2 id="t-lotes" class="mb-2 font-bold">Aulas por validade</h2>
                <ul class="grid gap-1.5 text-sm">
                    @forelse ($this->lotes as $l)
                        <li class="flex gap-2"><span class="numero w-6 text-lg">{{ $l->saldo }}</span><span class="flex-1">{{ $l->origem->rotulo() }}</span><span class="text-suave">até {{ $l->valido_ate->format('d/m') }}</span></li>
                    @empty
                        <li class="text-suave">Sem aulas no saldo.</li>
                    @endforelse
                </ul>
            </section>

            <section class="rounded-[18px] border-2 border-dashed border-lilas-3 p-4" aria-labelledby="t-notas">
                <div class="flex items-center gap-2">
                    <x-icone nome="cadeado" :tamanho="18" />
                    <h2 id="t-notas" class="flex-1 font-bold">Observações de saúde</h2>
                </div>
                @if ($verNotas)
                    <ul class="mt-3 grid gap-2">
                        @forelse ($this->notas as $n)
                            <li class="rounded-xl bg-branco p-3 text-sm">{{ $n->texto }}<span class="mt-1 block text-xs text-suave">{{ $n->autor->primeiroNome() }}, {{ $n->created_at->format('d/m/Y') }}</span></li>
                        @empty
                            <li class="text-sm text-suave">Nenhuma observação.</li>
                        @endforelse
                    </ul>
                    <form wire:submit="salvarNota" class="mt-3 grid gap-2">
                        <textarea wire:model="novaNota" class="campo text-sm" placeholder="Nova observação (lesão, liberação médica, gestação...)" maxlength="500"></textarea>
                        @error('novaNota')<span class="text-sm text-alerta">{{ $message }}</span>@enderror
                        <button class="btn btn-contorno btn-pequeno justify-self-start">Salvar observação</button>
                    </form>
                @else
                    <p class="mt-2 text-sm text-suave">Dado sensível pela LGPD: fica cifrado e cada leitura é registrada.</p>
                    <button class="btn btn-contorno btn-pequeno mt-3" wire:click="abrirNotas">Ler observações</button>
                @endif
                @if ($this->acessos->isNotEmpty())
                    <p class="mt-3 text-xs text-suave">Últimas leituras: {{ $this->acessos->map(fn ($a) => $a->usuario->primeiroNome().' '.$a->created_at->format('d/m H:i'))->join(', ') }}</p>
                @endif
            </section>

            <section class="cartao p-4" aria-labelledby="t-cortesia">
                <h2 id="t-cortesia" class="font-bold">Dar aula de cortesia</h2>
                <form wire:submit="darCortesia" class="mt-3 grid gap-2">
                    <div class="flex gap-2">
                        <select wire:model="cortesias" class="campo w-24" aria-label="Quantidade">@foreach ([1, 2, 3, 4] as $q)<option value="{{ $q }}">{{ $q }}</option>@endforeach</select>
                        <input wire:model="motivoCortesia" class="campo" placeholder="Motivo (fica no extrato)" maxlength="120">
                    </div>
                    @error('motivoCortesia')<span class="text-sm text-alerta">{{ $message }}</span>@enderror
                    <button class="btn btn-lima btn-pequeno justify-self-start">Lançar cortesia</button>
                </form>
            </section>
        </aside>
    </div>

    <livewire:recepcao.painel-da-aula />
</div>
