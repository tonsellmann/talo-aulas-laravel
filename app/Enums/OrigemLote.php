<?php

namespace App\Enums;

enum OrigemLote: string
{
    case Ciclo = 'ciclo';
    case Avulsa = 'avulsa';
    case Reposicao = 'reposicao';
    case Cortesia = 'cortesia';

    public function rotulo(): string
    {
        return match ($this) {
            self::Ciclo => 'Plano',
            self::Avulsa => 'Aula avulsa',
            self::Reposicao => 'Reposição',
            self::Cortesia => 'Cortesia',
        };
    }
}
