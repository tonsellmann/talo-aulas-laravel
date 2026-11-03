@php
    $matricula = $this->matricula;
    $ciclo = $this->ciclo;
    $pix = $this->pix;
@endphp
<div class="grid gap-6 pb-6">
    <h1 class="titulo text-[2.6rem] md:text-5xl">Plano</h1>

    @if ($matricula)
        <section class="cartao grid gap-4 p-5 md:grid-cols-[1fr_auto] md:items-end">
            <div>
                <p class="titulo text-3xl">{{ $matricula->plano->nome }}@if ($matricula->modalidade) <span class="text-ameixa-2">em {{ $matricula->modalidade->nome }}</span>@endif</p>
                <p class="mt-1 text-suave">{{ $matricula->plano->aulas_por_ciclo }} aulas a cada {{ $matricula->plano->ciclo_dias }} dias · {{ \App\Support\Dinheiro::formatar($matricula->plano->preco_centavos) }}</p>
            </div>
            @if ($ciclo)
                <dl class="grid grid-cols-2 gap-x-6 gap-y-1 text-sm">
                    <dt class="text-suave">Ciclo atual</dt><dd class="font-semibold">{{ $ciclo->periodo() }}</dd>
                    <dt class="text-suave">Reposições</dt><dd class="font-semibold">{{ $ciclo->reposicoes_disponiveis }} de {{ $matricula->plano->reposicoes_por_ciclo }}</dd>
                </dl>
            @endif
        </section>
    @endif

    @if ($pix)
        <section class="grid gap-5 rounded-[22px] bg-ameixa p-5 text-branco md:grid-cols-[auto_1fr] md:p-7" aria-labelledby="titulo-pix" x-data="{ copiado: false }">
            <div class="mx-auto w-52 rounded-2xl bg-branco p-2 md:mx-0" x-html="window.qrDoPix(@js($pix->pix_copia_e_cola))" role="img" aria-label="QR code do Pix"></div>
            <div class="flex min-w-0 flex-col gap-3">
                <h2 id="titulo-pix" class="titulo text-3xl">Pix do próximo ciclo</h2>
                <p class="text-lilas-2">{{ $pix->descricao }}. <span class="numero text-2xl text-lima">{{ \App\Support\Dinheiro::formatar($pix->valor_centavos) }}</span></p>
                <p class="text-sm text-lilas-3">Vale até {{ $pix->vence_em->translatedFormat('l, d/m \à\s H:i') }}. As aulas entram no saldo assim que o banco confirmar, sem precisar mandar comprovante.</p>
                <div class="flex min-w-0 items-center gap-2 rounded-xl bg-ameixa-2 p-2 pl-3">
                    <code class="min-w-0 flex-1 truncate text-xs text-lilas-2">{{ $pix->pix_copia_e_cola }}</code>
                    <button type="button" class="btn btn-lima btn-pequeno" x-on:click="navigator.clipboard?.writeText(@js($pix->pix_copia_e_cola)); copiado = true; setTimeout(() => copiado = false, 2500)">
                        <x-icone nome="copiar" :tamanho="16" /><span x-text="copiado ? 'Copiado' : 'Copiar'">Copiar</span>
                    </button>
                </div>
                @if (config('talo.demonstracao'))
                    <div class="mt-1 rounded-2xl border border-dashed border-lilas-3/60 p-3 text-sm text-lilas-2">
                        <p>Na demonstração não existe banco. Este botão manda o aviso de pagamento assinado, igual ao que o gateway enviaria, para o mesmo código do webhook.</p>
                        <button type="button" class="btn btn-lima btn-pequeno mt-2" wire:click="simularPagamento" wire:loading.attr="disabled">Simular o Pix pago</button>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section aria-labelledby="titulo-historico">
        <h2 id="titulo-historico" class="mb-3 text-lg font-bold">Pagamentos</h2>
        <ul class="cartao divide-y divide-linha px-4">
            @forelse ($this->historico as $c)
                <li class="flex flex-wrap items-center gap-3 py-3" wire:key="c-{{ $c->id }}">
                    <span class="min-w-0 flex-1"><span class="block font-semibold">{{ $c->descricao }}</span><span class="text-sm text-suave">{{ $c->paga_em ? 'Pago em '.$c->paga_em->format('d/m \à\s H:i') : 'Venceu em '.$c->vence_em->format('d/m') }}</span></span>
                    <span class="numero text-xl">{{ \App\Support\Dinheiro::formatar($c->valor_pago_centavos ?? $c->valor_centavos) }}</span>
                    <span @class(['selo', 'selo-vaga' => $c->status === \App\Enums\StatusCobranca::Paga, 'selo-poucas' => $c->status === \App\Enums\StatusCobranca::Divergente, 'selo-lotada' => $c->status === \App\Enums\StatusCobranca::Expirada])>{{ $c->status->rotulo() }}</span>
                </li>
            @empty
                <li class="py-6 text-center text-suave">Nenhum pagamento ainda.</li>
            @endforelse
        </ul>
    </section>
</div>
