{{-- Aviso rápido no canto: sucesso ou regra que impediu a ação. --}}
<div
    x-data="{ itens: [], mostrar(e) { const id = Date.now() + Math.random(); this.itens.push({ id, ...e.detail }); setTimeout(() => this.itens = this.itens.filter(i => i.id !== id), e.detail.tipo === 'erro' ? 7000 : 4500) } }"
    x-on:aviso.window="mostrar($event)"
    class="pointer-events-none fixed inset-x-0 bottom-20 z-[70] flex flex-col items-center gap-2 px-4 md:bottom-6"
    aria-live="polite"
>
    @if (session('aviso'))
        <div x-init="mostrar({ detail: @js(session('aviso')) })"></div>
    @endif
    <template x-for="item in itens" :key="item.id">
        <div class="aviso-toast pointer-events-auto flex max-w-md items-start gap-3 rounded-2xl px-4 py-3 text-[0.95rem] font-semibold shadow-[0_12px_32px_-12px_rgb(42_24_56/.45)]"
             :class="item.tipo === 'erro' ? 'bg-alerta text-white' : 'bg-ameixa text-branco'">
            <span x-text="item.texto"></span>
        </div>
    </template>
</div>
