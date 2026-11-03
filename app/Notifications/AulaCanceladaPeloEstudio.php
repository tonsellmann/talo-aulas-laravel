<?php

namespace App\Notifications;

use App\Models\Aula;
use App\Models\User;

class AulaCanceladaPeloEstudio extends AvisoWhatsApp
{
    public function __construct(public readonly Aula $aula, public readonly string $motivo, public readonly bool $tinhaReserva) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $texto = "A aula de {$this->aula->descricao()} foi cancelada: {$this->motivo}.";
        $texto .= $this->tinhaReserva ? ' A aula voltou para o seu saldo.' : ' Você saiu da fila de espera dela.';

        return new MensagemWhatsApp('aula_cancelada', $texto, route('aluna.agenda'), 'Escolher outro horário');
    }
}
