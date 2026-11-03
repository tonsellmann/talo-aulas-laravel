<?php

namespace App\Notifications;

use App\Models\EntradaNaFila;
use App\Models\User;

class OfertaExpirou extends AvisoWhatsApp
{
    public function __construct(public readonly EntradaNaFila $entrada) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        return new MensagemWhatsApp(
            'oferta_expirou',
            'O prazo para confirmar a vaga de '.$this->entrada->aula->descricao().' acabou e ela foi para a próxima da fila. Se ainda quiser essa aula, entre na fila de novo.',
            route('aluna.agenda'),
            'Ver agenda',
        );
    }
}
