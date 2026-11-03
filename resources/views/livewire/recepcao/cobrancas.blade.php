@php
    $abas = ['pendente' => 'Aguardando Pix', 'divergente' => 'Valor diferente', 'paga' => 'Pagas', 'expirada' => 'Vencidas'];
    $dinheiro = fn ($c) => \App\Support\Dinheiro::formatar($c);
@endphp
<div class="mx-auto grid max-w-6xl gap-5">
    <header>
        <h1 class="titulo text-5xl">Cobranças</h1>
        <p class="mt-1 max-w-[62ch] text-suave">O Pix de renovação sai sozinho cinco dias antes do fim do ciclo. Quando o banco confirma, as aulas entram no saldo da aluna sem ninguém conferir comprovante.</p>
    </header>

    <div class="flex flex-wrap gap-1.5" role="tablist">
        @foreach ($abas as $chave => $rotulo)
            <button type="button" role="tab" class="chip" aria-pressed="{{ $situacao === $chave ? 'true' : 'false' }}" aria-selected="{{ $situacao === $chave ? 'true' : 'false' }}" wire:click="$set('situacao', '{{ $chave }}')">
                {{ $rotulo }} <span class="numero text-base">{{ $this->contagens[$chave]['n'] ?? 0 }}</span>
            </button>
        @endforeach
    </div>

    @if (($this->contagens[$situacao]['total'] ?? 0) > 0)
        <p class="text-sm text-suave">Total: <span class="numero text-2xl text-ameixa">{{ $dinheiro($this->contagens[$situacao]['total']) }}</span></p>
    @endif

    <ul class="grid gap-2">
        @forelse ($this->cobrancas as $c)
            <li class="cartao flex flex-wrap items-center gap-x-4 gap-y-2 px-4 py-3" wire:key="c-{{ $c->id }}">
                <span class="min-w-0 flex-1">
                    <a href="{{ route('recepcao.aluna', $c->aluna) }}" wire:navigate class="block font-semibold hover:underline">{{ $c->aluna->name }}</a>
                    <span class="text-sm text-suave">{{ $c->descricao }}</span>
                </span>
                <span class="text-sm text-suave">
                    @switch($c->status->value)
                        @case('paga') Pago {{ $c->paga_em?->translatedFormat('d/m \à\s H:i') }} @break
                        @case('pendente') Vale até {{ $c->vence_em->translatedFormat('d/m, H:i') }} @break
                        @case('expirada') Venceu {{ $c->vence_em->format('d/m') }} @break
                        @case('divergente') Pagou {{ $dinheiro($c->valor_pago_centavos) }} @break
                    @endswitch
                </span>
                <span class="numero w-28 text-right text-xl">{{ $dinheiro($c->valor_centavos) }}</span>
                @if ($c->status->value === 'divergente')
                    <div class="flex w-full flex-wrap items-center gap-2 rounded-xl bg-pessego px-3 py-2 text-sm text-pessego-tinta">
                        <span class="flex-1">Faltaram {{ $dinheiro($c->valor_centavos - $c->valor_pago_centavos) }}. O ciclo fica segurado até alguém conferir.</span>
                        <button class="btn btn-primario btn-pequeno" wire:click="aceitarDiferenca({{ $c->id }})" wire:confirm="Liberar o ciclo de {{ $c->aluna->primeiroNome() }} com o valor pago?">Aceitar e liberar o ciclo</button>
                    </div>
                @endif
            </li>
        @empty
            <li class="rounded-[18px] border-2 border-dashed border-lilas-3 p-6 text-center text-suave">Nada aqui.</li>
        @endforelse
    </ul>
</div>
