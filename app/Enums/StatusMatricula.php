<?php

namespace App\Enums;

enum StatusMatricula: string
{
    case Ativa = 'ativa';
    /** Ciclo terminou sem o Pix do próximo. */
    case Pendente = 'pendente';
    case Encerrada = 'encerrada';
}
