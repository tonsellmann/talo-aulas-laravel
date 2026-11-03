@props(['nome', 'tamanho' => 20])
@php
    // Ícones desenhados para o sistema, em traço de 2px.
    $caminhos = [
        'inicio' => '<path d="M4 11l8-7 8 7"/><path d="M6 10v10h12V10"/><path d="M10 20v-5h4v5"/>',
        'agenda' => '<rect x="3.5" y="5" width="17" height="15.5" rx="3"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
        'extrato' => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6M9 16h3"/>',
        'plano' => '<rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18M7 15h4"/>',
        'relogio' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        'pessoa' => '<circle cx="12" cy="8" r="3.5"/><path d="M5 20c1-4 4-6 7-6s6 2 7 6"/>',
        'pessoas' => '<circle cx="9" cy="8.5" r="3"/><path d="M3.5 19c.8-3.4 3-5 5.5-5s4.7 1.6 5.5 5"/><circle cx="16.5" cy="7.5" r="2.5"/><path d="M15.5 13.2c2.3-.3 4.3 1.2 5 4.8"/>',
        'fila' => '<path d="M4 6h10M4 12h10M4 18h6"/><path d="M17 15l3 3-3 3"/><path d="M20 18h-6"/>',
        'check' => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'x' => '<path d="M6 6l12 12M18 6L6 18"/>',
        'mais' => '<path d="M12 5v14M5 12h14"/>',
        'seta' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'voltar' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'mensagem' => '<path d="M4 5h16v11H9l-5 4z"/>',
        'pix' => '<path d="M12 3.2l8.8 8.8-8.8 8.8L3.2 12z"/><path d="M8.6 12h6.8"/>',
        'grafico' => '<path d="M4 20V10M10 20V4M16 20v-7M21 20H3"/>',
        'sair' => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 16l-4-4 4-4M6 12h10"/>',
        'avancar' => '<path d="M5 6l7 6-7 6zM13 6l7 6-7 6z"/>',
        'recomecar' => '<path d="M4 4v6h6"/><path d="M5.5 15a7 7 0 1 0 1.2-7.4L4 10"/>',
        'alerta' => '<path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/>',
        'cadeado' => '<rect x="5" y="10.5" width="14" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>',
        'semana' => '<rect x="3" y="4" width="18" height="16" rx="3"/><path d="M8 4v16M13 4v16M3 10h18"/>',
        'cancelar' => '<circle cx="12" cy="12" r="8.5"/><path d="M8.5 8.5l7 7"/>',
        'copiar' => '<rect x="8" y="8" width="12" height="12" rx="2.5"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
        'busca' => '<circle cx="11" cy="11" r="6.5"/><path d="M16 16l4 4"/>',
        'nota' => '<path d="M5 4h10l4 4v12H5z"/><path d="M15 4v4h4M8 12h8M8 16h5"/>',
        'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'shrink-0']) }} width="{{ $tamanho }}" height="{{ $tamanho }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $caminhos[$nome] ?? '' !!}</svg>
