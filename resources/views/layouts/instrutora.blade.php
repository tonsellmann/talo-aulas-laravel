<x-base :titulo="$title ?? null">
    <header class="mx-auto flex w-full max-w-4xl items-center gap-3 px-4 pt-5">
        <a href="{{ route('instrutora.aulas') }}" wire:navigate class="text-2xl"><x-marca /></a>
        <span class="text-sm font-semibold text-suave">instrutora</span>
        <form method="post" action="{{ route('sair') }}" class="ml-auto flex items-center gap-2">
            @csrf
            <span class="hidden text-sm font-semibold sm:inline">{{ auth()->user()->name }}</span>
            <button class="flex size-10 items-center justify-center rounded-full bg-lilas-2 text-sm font-bold" title="Sair">{{ auth()->user()->iniciais() }}</button>
        </form>
    </header>
    <main class="mx-auto w-full max-w-4xl px-4 py-5">
        {{ $slot }}
    </main>
</x-base>
