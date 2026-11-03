<div class="sticky top-0 z-50 bg-[#1d1027] text-branco" x-data="{ relogio: false }">
    <div class="mx-auto flex max-w-6xl flex-wrap items-center gap-x-3 gap-y-2 px-3 py-2 text-sm">
        <span class="selo bg-lima text-ameixa" title="Dados inventados; nada sai do seu navegador"><span class="hidden sm:inline">Demonstração</span><span class="sm:hidden">Demo</span></span>

        @auth
            <label class="flex min-w-0 items-center gap-2">
                <span class="hidden text-lilas-3 sm:inline">Você está como</span>
                <select class="h-8 min-w-0 max-w-[8.5rem] rounded-full border-0 bg-ameixa-2 pl-3 pr-8 text-sm font-semibold text-branco sm:max-w-none"
                        x-on:change="$wire.entrarComo($event.target.value)" aria-label="Trocar de pessoa">
                    <optgroup label="Equipe">
                        @foreach ($this->pessoas->where('papel', '!=', \App\Enums\Papel::Aluna) as $p)
                            <option value="{{ $p->id }}" @selected($p->id === auth()->id())>{{ $p->name }} ({{ $p->papel->rotulo() }})</option>
                        @endforeach
                    </optgroup>
                    <optgroup label="Alunas">
                        @foreach ($this->pessoas->where('papel', \App\Enums\Papel::Aluna) as $p)
                            <option value="{{ $p->id }}" @selected($p->id === auth()->id())>{{ $p->name }}</option>
                        @endforeach
                    </optgroup>
                </select>
            </label>
        @endauth

        <div class="relative ml-auto flex items-center gap-2">
            <button type="button" class="flex h-8 items-center gap-2 rounded-full bg-ameixa-2 px-3 font-semibold hover:bg-ameixa" x-on:click="relogio = ! relogio" :aria-expanded="relogio" title="Avançar o relógio da demonstração">
                <x-icone nome="relogio" :tamanho="16" />
                <span class="numero text-[1.05rem] font-bold"><span class="hidden sm:inline">{{ now()->translatedFormat('D d/m') }} · </span><span class="sm:hidden">{{ now()->translatedFormat('D') }} </span>{{ now()->format('H:i') }}</span>
            </button>
            <div x-show="relogio" x-cloak x-transition.origin.top.right x-on:click.outside="relogio = false"
                 class="absolute right-0 top-10 w-72 rounded-2xl bg-branco p-3 text-ameixa shadow-[0_18px_40px_-14px_rgb(0_0_0/.5)]">
                <p class="px-1 pb-2 text-[0.82rem] text-suave">Avance o tempo e as rotinas do servidor rodam: lembretes, fila de espera, faltas, aulas vencendo e Pix de renovação.</p>
                <div class="grid grid-cols-3 gap-1.5">
                    <button type="button" class="btn btn-contorno btn-pequeno" wire:click="avancar(30)" wire:loading.attr="disabled">+30 min</button>
                    <button type="button" class="btn btn-contorno btn-pequeno" wire:click="avancar(180)" wire:loading.attr="disabled">+3 h</button>
                    <button type="button" class="btn btn-primario btn-pequeno" wire:click="avancar(1440)" wire:loading.attr="disabled">+1 dia</button>
                </div>
                <button type="button" class="mt-3 flex w-full items-center justify-center gap-2 rounded-xl py-2 text-sm font-semibold text-suave hover:bg-lilas" wire:click="recomecar" wire:confirm="Apagar tudo o que você fez e montar o estúdio de novo?">
                    <x-icone nome="recomecar" :tamanho="16" /> Recomeçar a demonstração
                </button>
            </div>

            @auth
                @unless (auth()->user()->eh(\App\Enums\Papel::Instrutora))
                    <button type="button" class="relative flex h-8 items-center gap-1.5 rounded-full bg-ameixa-2 px-3 font-semibold hover:bg-ameixa" wire:click="abrirMensagens" title="O que chegou no WhatsApp">
                        <x-icone nome="mensagem" :tamanho="16" />
                        <span class="hidden sm:inline">WhatsApp</span>
                        @if ($this->naoLidas)
                            <span class="absolute -right-1.5 -top-1.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-lima px-1 text-[0.7rem] font-extrabold text-ameixa">{{ $this->naoLidas }}</span>
                        @endif
                    </button>
                @endunless
            @endauth
        </div>
    </div>

    @if ($mensagensAbertas)
        <div class="fixed inset-0 z-50 flex justify-end bg-ameixa/40" x-on:keydown.escape.window="$wire.set('mensagensAbertas', false)">
            <div class="absolute inset-0" wire:click="$set('mensagensAbertas', false)"></div>
            <section class="relative flex h-full w-full max-w-md flex-col bg-[#efe8f7] text-ameixa shadow-2xl" aria-label="Mensagens do WhatsApp">
                <header class="flex items-center gap-3 bg-ameixa px-4 py-3 text-branco">
                    <x-marca :tamanho="34">
                        <span class="flex flex-col leading-tight"><span class="font-sans text-base font-bold">Talo Pilates</span><span class="font-sans text-xs font-normal text-lilas-3">{{ auth()->user()?->eh(\App\Enums\Papel::Aluna) ? 'conversa com '.auth()->user()->primeiroNome() : 'todas as mensagens enviadas' }}</span></span>
                    </x-marca>
                    <button type="button" class="ml-auto rounded-lg p-1.5 hover:bg-ameixa-2" wire:click="$set('mensagensAbertas', false)" aria-label="Fechar"><x-icone nome="x" /></button>
                </header>
                <div class="flex flex-1 flex-col-reverse gap-2 overflow-y-auto px-3 py-4">
                    @forelse ($this->mensagens as $m)
                        <article class="max-w-[88%] self-start rounded-2xl rounded-tl-md bg-branco px-3.5 py-2.5 shadow-sm">
                            @unless (auth()->user()->eh(\App\Enums\Papel::Aluna))
                                <p class="mb-1 text-xs font-bold text-ameixa-2">Para {{ $m->aluna?->name }}</p>
                            @endunless
                            <p class="text-[0.95rem] leading-snug">{{ $m->texto }}</p>
                            @if ($m->link)
                                <a href="{{ $m->link }}" wire:navigate class="mt-2 flex items-center justify-center gap-1.5 rounded-xl border-t border-linha pt-2 text-sm font-bold text-ameixa-2 hover:text-ameixa">{{ $m->rotulo_link ?? 'Abrir' }}<x-icone nome="seta" :tamanho="16" /></a>
                            @endif
                            <p class="mt-1 text-right text-[0.7rem] text-suave">{{ $m->created_at->translatedFormat('D d/m, H:i') }}</p>
                        </article>
                    @empty
                        <p class="m-auto max-w-[24ch] text-center text-sm text-suave">Nenhuma mensagem ainda. Reserve, desmarque ou avance o relógio para ver os avisos chegando.</p>
                    @endforelse
                </div>
            </section>
        </div>
    @endif
</div>
