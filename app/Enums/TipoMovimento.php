<?php

namespace App\Enums;

enum TipoMovimento: string
{
    case Credito = 'credito';
    case Reserva = 'reserva';
    case Estorno = 'estorno';
    case Expiracao = 'expiracao';
    case Ajuste = 'ajuste';
    /** Registro sem efeito no saldo, para o extrato contar a história inteira. */
    case Registro = 'registro';
}
