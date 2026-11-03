<?php

namespace App\Notifications;

use App\Models\Reserva;
use App\Models\User;

class ReservaConfirmada extends AvisoWhatsApp
{
    public function __construct(public readonly Reserva $reserva) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $aula = $this->reserva->aula;
        $prazo = $this->reserva->prazoDeCancelamento();

        return new MensagemWhatsApp(
            'reserva_confirmada',
            "{$aluna->primeiroNome()}, sua aula de {$aula->modalidade->nome} está reservada: {$aula->quando()}, com {$aula->instrutora->primeiroNome()}. "
            .'Se precisar desmarcar, faça até '.$prazo->translatedFormat('l').' às '.$prazo->format('H:i').' para a aula voltar para você.',
            route('aluna.inicio'),
            'Ver minhas aulas',
        );
    }
}
