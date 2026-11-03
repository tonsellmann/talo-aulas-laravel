<div class="flex flex-1 flex-col">
    @if (config('talo.demonstracao'))
        @php
            $personas = [
                ['aluna@talo.test', 'Marina Couto', 'Aluna, 2x por semana em Aparelhos', 'Reserve e desmarque aulas, entre na fila de espera e pague o Pix do próximo mês. Ela está com uma aula sobrando e o plano vencendo.', 'bg-lima'],
                ['recepcao@talo.test', 'Camila Rocha', 'Recepção', 'Acompanhe o dia do estúdio, encaixe alunas, cancele uma aula, resolva um Pix pago com valor errado e veja as mensagens que saíram.', 'bg-lilas-3'],
                ['bianca@talo.test', 'Bianca Moura', 'Instrutora de Aparelhos', 'Marque presença e veja as observações de saúde das alunas. Cada leitura fica registrada, como pede a LGPD.', 'bg-rosa'],
            ];
        @endphp
        <div class="flex items-center gap-3 text-3xl"><x-marca /></div>
        <h1 class="titulo mt-8 max-w-[16ch] text-[clamp(2.6rem,7vw,4.6rem)]">O estúdio inteiro, de três lados.</h1>
        <p class="mt-4 max-w-[58ch] text-lg text-ameixa-2">Agenda de aulas com créditos que vencem, fila de espera que chama a próxima aluna sozinha e cobrança por Pix. Escolha quem você quer ser; dá para trocar a qualquer momento na barra de cima.</p>

        <div class="mt-8 grid gap-3 md:grid-cols-3">
            @foreach ($personas as [$email, $nome, $papel, $texto, $cor])
                <button type="button" wire:click="entrarComo('{{ $email }}')" wire:loading.attr="disabled"
                        class="cartao group flex flex-col items-start gap-3 p-5 text-left transition hover:-translate-y-0.5 hover:shadow-[0_18px_36px_-20px_rgb(42_24_56/.55)] focus-visible:outline-offset-4">
                    <span class="flex w-full items-center gap-3">
                        <span class="{{ $cor }} flex size-12 items-center justify-center rounded-full text-lg font-extrabold">{{ collect(explode(' ', $nome))->map(fn ($p) => mb_substr($p, 0, 1))->join('') }}</span>
                        <span class="flex flex-col"><span class="text-lg font-bold">{{ $nome }}</span><span class="text-sm text-suave">{{ $papel }}</span></span>
                    </span>
                    <span class="text-[0.95rem] text-ameixa-2">{{ $texto }}</span>
                    <span class="mt-auto flex items-center gap-1.5 pt-1 font-bold">Entrar como {{ explode(' ', $nome)[0] }} <x-icone nome="seta" :tamanho="18" class="transition group-hover:translate-x-1" /></span>
                </button>
            @endforeach
        </div>
        <p class="mt-6 max-w-[64ch] text-sm text-suave">Alunas, horários e valores são inventados. As mensagens de WhatsApp não saem daqui: aparecem no botão WhatsApp da barra.</p>
    @else
        <div class="m-auto w-full max-w-sm">
            <div class="text-3xl"><x-marca /></div>
            <h1 class="titulo mt-6 text-4xl">Entrar</h1>
            <form wire:submit="entrar" class="mt-6 grid gap-4">
                <label class="grid gap-1.5 font-semibold">E-mail
                    <input type="email" wire:model="email" class="campo font-normal" autocomplete="username" required>
                    @error('email') <span class="text-sm font-normal text-alerta">{{ $message }}</span> @enderror
                </label>
                <label class="grid gap-1.5 font-semibold">Senha
                    <input type="password" wire:model="senha" class="campo font-normal" autocomplete="current-password" required>
                </label>
                <button class="btn btn-primario mt-2">Entrar</button>
            </form>
        </div>
    @endif
</div>
