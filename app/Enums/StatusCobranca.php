<?php

namespace App\Enums;

enum StatusCobranca: string
{
    case Pendente = 'pendente';
    case Paga = 'paga';
    case Expirada = 'expirada';
    /** Pago com valor diferente do cobrado: fica para a recepção conferir. */
    case Divergente = 'divergente';

    public function rotulo(): string
    {
        return match ($this) {
            self::Pendente => 'Aguardando Pix',
            self::Paga => 'Paga',
            self::Expirada => 'Vencida',
            self::Divergente => 'Valor diferente',
        };
    }
}
