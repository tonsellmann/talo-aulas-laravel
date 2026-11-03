<?php

namespace App\Enums;

enum StatusFila: string
{
    case Aguardando = 'aguardando';
    case Oferta = 'oferta';
    case Confirmada = 'confirmada';
    case Expirada = 'expirada';
    case Saiu = 'saiu';
    case SemCredito = 'sem_credito';
    case Encerrada = 'encerrada';

    public function ativa(): bool
    {
        return $this === self::Aguardando || $this === self::Oferta;
    }
}
