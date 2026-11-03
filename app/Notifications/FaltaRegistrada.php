<?php

namespace App\Notifications;

use App\Models\LoteDeCredito;
use App\Models\Reserva;
use App\Models\User;

class FaltaRegistrada extends AvisoWhatsApp
{
    public function __construct(public readonly Reserva $reserva, public readonly ?LoteDeCredito $reposicao) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $texto = "Sentimos sua falta em {$this->reserva->aula->descricao()}.";
        $texto .= $this->reposicao
            ? ' Usamos uma reposição do seu plano: você tem uma aula extra até '.$this->reposicao->valido_ate->format('d/m').'.'
            : ' As reposições deste ciclo já foram usadas, então essa aula não volta.';

        return new MensagemWhatsApp('falta_registrada', $texto, route('aluna.agenda'), 'Remarcar');
    }
}
