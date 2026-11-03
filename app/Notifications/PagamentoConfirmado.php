<?php

namespace App\Notifications;

use App\Models\Cobranca;
use App\Models\LoteDeCredito;
use App\Models\User;

class PagamentoConfirmado extends AvisoWhatsApp
{
    public function __construct(public readonly Cobranca $cobranca, public readonly LoteDeCredito $lote) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        return new MensagemWhatsApp(
            'pagamento_confirmado',
            "Pix recebido, {$aluna->primeiroNome()}! Suas {$this->lote->quantidade} aulas de {$this->lote->valido_de->format('d/m')} a {$this->lote->valido_ate->format('d/m')} já estão no saldo.",
            route('aluna.agenda'),
            'Marcar aulas',
        );
    }
}
