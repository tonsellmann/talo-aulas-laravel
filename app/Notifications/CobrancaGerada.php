<?php

namespace App\Notifications;

use App\Models\Cobranca;
use App\Models\User;
use App\Support\Dinheiro;

class CobrancaGerada extends AvisoWhatsApp
{
    public function __construct(public readonly Cobranca $cobranca) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $c = $this->cobranca;

        return new MensagemWhatsApp(
            'cobranca_gerada',
            "{$aluna->primeiroNome()}, seu plano renova em {$c->ciclo_inicio?->format('d/m')}. O Pix de ".Dinheiro::formatar($c->valor_centavos).' já está disponível e vale até '.$c->vence_em->format('d/m').'.',
            route('aluna.plano'),
            'Pagar com Pix',
        );
    }
}
