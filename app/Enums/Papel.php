<?php

namespace App\Enums;

enum Papel: string
{
    case Aluna = 'aluna';
    case Recepcao = 'recepcao';
    case Instrutora = 'instrutora';

    public function rotulo(): string
    {
        return match ($this) {
            self::Aluna => 'Aluna',
            self::Recepcao => 'Recepção',
            self::Instrutora => 'Instrutora',
        };
    }

    public function inicio(): string
    {
        return match ($this) {
            self::Aluna => 'aluna.inicio',
            self::Recepcao => 'recepcao.hoje',
            self::Instrutora => 'instrutora.aulas',
        };
    }
}
