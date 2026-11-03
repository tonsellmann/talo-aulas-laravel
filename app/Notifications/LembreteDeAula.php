<?php

namespace App\Notifications;

use App\Models\Reserva;
use App\Models\User;

class LembreteDeAula extends AvisoWhatsApp
{
    public function __construct(public readonly Reserva $reserva) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $aula = $this->reserva->aula;
        $saudacao = $aula->inicio->hour < 12 ? 'Bom dia' : ($aula->inicio->hour < 18 ? 'Boa tarde' : 'Boa noite');

        return new MensagemWhatsApp(
            'lembrete_aula',
            "{$saudacao}, {$aluna->primeiroNome()}! Hoje tem {$aula->modalidade->nome} às {$aula->inicio->format('H:i')} com {$aula->instrutora->primeiroNome()}. Traga a meia antiderrapante.",
            route('aluna.inicio'),
            'Ver aula',
        );
    }
}
