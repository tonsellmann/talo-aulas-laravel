<?php

namespace App\Notifications;

use App\Models\EntradaNaFila;
use App\Models\User;

class VagaSemCredito extends AvisoWhatsApp
{
    public function __construct(public readonly EntradaNaFila $entrada) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        return new MensagemWhatsApp(
            'vaga_sem_credito',
            'Vagou um lugar em '.$this->entrada->aula->descricao().', mas você está sem aulas que valham nessa data, então a vaga foi para a próxima da fila.',
            route('aluna.plano'),
            'Ver meu plano',
        );
    }
}
