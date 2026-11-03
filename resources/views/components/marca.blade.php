@props(['tamanho' => 32])
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 font-display font-extrabold tracking-tight']) }}>
    <svg width="{{ $tamanho }}" height="{{ $tamanho }}" viewBox="0 0 32 32" aria-hidden="true"><rect width="32" height="32" rx="16" fill="#2a1838"/><path d="M16 27V9" stroke="#d4f25a" stroke-width="3" stroke-linecap="round"/><path d="M16 14c-4-1-6-4-6-7 4 0 6 3 6 7zM16 18c4-1 6-4 6-7-4 0-6 3-6 7z" fill="#d4f25a"/></svg>
    {{ $slot->isEmpty() ? 'talo' : $slot }}
</span>
