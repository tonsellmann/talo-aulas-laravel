<?php

namespace App\Notifications;

use App\Models\Aula;
use App\Models\User;
use Carbon\CarbonImmutable;

class AulasVencendo extends AvisoWhatsApp
{
    public function __construct(
        public readonly int $quantidade,
        public readonly CarbonImmutable $vencimento,
        public readonly ?Aula $sugestao = null,
    ) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $aulas = $this->quantidade === 1 ? '1 aula do seu saldo vence' : "{$this->quantidade} aulas do seu saldo vencem";
        $texto = "{$aluna->primeiroNome()}, {$aulas} ".$this->vencimento->translatedFormat('l, d/m').'.';

        if ($this->sugestao) {
            $texto .= ' Ainda tem vaga em '.$this->sugestao->modalidade->nome.', '.$this->sugestao->quando().'.';
        }

        return new MensagemWhatsApp('aulas_vencendo', $texto, route('aluna.agenda'), 'Marcar aula');
    }
}
