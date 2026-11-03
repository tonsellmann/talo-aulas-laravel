<x-base :titulo="$title ?? null">
    <main class="mx-auto flex min-h-[calc(100dvh-56px)] w-full max-w-5xl flex-col px-4 py-8 md:py-14">
        {{ $slot }}
    </main>
</x-base>
