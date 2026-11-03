<?php

namespace App\Enums;

enum ModoFila: string
{
    /** Vagou com folga, reserva sozinha e avisa. */
    case Automatico = 'automatico';
    /** Vagou, manda a oferta e espera a aluna confirmar. */
    case Avisar = 'avisar';

    public function rotulo(): string
    {
        return match ($this) {
            self::Automatico => 'Reservar sozinha se vagar',
            self::Avisar => 'Me avisar para eu confirmar',
        };
    }
}
