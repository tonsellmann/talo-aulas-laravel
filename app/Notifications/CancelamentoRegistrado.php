<?php

namespace App\Notifications;

use App\Models\LoteDeCredito;
use App\Models\Reserva;
use App\Models\User;

class CancelamentoRegistrado extends AvisoWhatsApp
{
    public function __construct(
        public readonly Reserva $reserva,
        public readonly bool $noPrazo,
        public readonly ?LoteDeCredito $reposicao = null,
    ) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $aula = $this->reserva->aula->descricao();

        $texto = match (true) {
            $this->noPrazo => "Aula desmarcada: {$aula}. Ela voltou para o seu saldo.",
            $this->reposicao !== null => "Aula desmarcada depois do prazo: {$aula}. Essa aula não volta, mas usamos uma reposição do seu plano: você ganhou uma aula extra válida até {$this->reposicao->valido_ate->format('d/m')}.",
            default => "Aula desmarcada depois do prazo: {$aula}. Essa aula não volta e as reposições deste ciclo já foram usadas.",
        };

        return new MensagemWhatsApp('cancelamento', $texto, route('aluna.agenda'), 'Remarcar');
    }
}
