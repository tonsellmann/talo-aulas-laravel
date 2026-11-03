<div class="mx-auto grid max-w-5xl gap-5">
    <header class="flex flex-wrap items-end gap-3">
        <div class="min-w-0 flex-1">
            <h1 class="titulo text-5xl">Mensagens</h1>
            <p class="mt-1 max-w-[60ch] text-suave">Tudo o que o sistema mandou no WhatsApp. Cada mensagem é gravada antes de sair; se a API falhar, ela fica aqui e é reenviada.</p>
        </div>
        <select wire:model.live="modelo" class="campo w-auto" aria-label="Filtrar por tipo">
            <option value="">Todos os tipos</option>
            @foreach (\App\Livewire\Recepcao\Mensagens::MODELOS as $chave => $rotulo)<option value="{{ $chave }}">{{ $rotulo }}</option>@endforeach
        </select>
    </header>

    <ul class="grid gap-2">
        @forelse ($this->mensagens as $m)
            <li class="cartao grid gap-1 px-4 py-3 sm:grid-cols-[180px_1fr]" wire:key="m-{{ $m->id }}">
                <div class="text-sm">
                    <p class="font-semibold">{{ $m->aluna?->name }}</p>
                    <p class="text-xs text-suave">{{ \App\Livewire\Recepcao\Mensagens::MODELOS[$m->modelo] ?? $m->modelo }} · {{ $m->created_at->translatedFormat('D d/m, H:i') }}</p>
                </div>
                <p class="text-[0.95rem]">{{ $m->texto }} <span @class(['selo ml-1 align-middle', 'selo-vaga' => $m->status === 'enviada', 'selo-poucas' => $m->status === 'pendente', 'selo-alerta' => $m->status === 'falhou'])>{{ ['enviada' => 'Enviada', 'pendente' => 'Na fila de envio', 'falhou' => 'Falhou'][$m->status] ?? $m->status }}</span></p>
            </li>
        @empty
            <li class="rounded-[18px] border-2 border-dashed border-lilas-3 p-6 text-center text-suave">Nenhuma mensagem desse tipo.</li>
        @endforelse
    </ul>
</div>
