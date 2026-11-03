<?php

namespace App\Enums;

enum StatusReserva: string
{
    case Confirmada = 'confirmada';
    case Presente = 'presente';
    case Falta = 'falta';
    case Cancelada = 'cancelada';
    case CanceladaTarde = 'cancelada_tarde';
    case CanceladaPeloEstudio = 'cancelada_pelo_estudio';

    /** Ocupa vaga na aula. */
    public function ocupaVaga(): bool
    {
        return in_array($this, [self::Confirmada, self::Presente, self::Falta], true);
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Confirmada => 'Confirmada',
            self::Presente => 'Presente',
            self::Falta => 'Falta',
            self::Cancelada => 'Cancelada no prazo',
            self::CanceladaTarde => 'Cancelada depois do prazo',
            self::CanceladaPeloEstudio => 'Aula cancelada pelo estúdio',
        };
    }
}
