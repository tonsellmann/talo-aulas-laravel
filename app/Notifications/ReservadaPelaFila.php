<?php

namespace App\Notifications;

use App\Models\Reserva;
use App\Models\User;

class ReservadaPelaFila extends AvisoWhatsApp
{
    public function __construct(public readonly Reserva $reserva) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $aula = $this->reserva->aula;

        return new MensagemWhatsApp(
            'reservada_pela_fila',
            "Vagou um lugar em {$aula->modalidade->nome}, {$aula->quando()}, e já reservamos para você, como você pediu na fila. Uma aula saiu do seu saldo.",
            route('aluna.inicio'),
            'Ver minhas aulas',
        );
    }
}
