<?php

namespace App\Notifications;

use App\Models\EntradaNaFila;
use App\Models\User;

class VagaOferecida extends AvisoWhatsApp
{
    public function __construct(public readonly EntradaNaFila $entrada, public readonly bool $corridaFinal) {}

    public function toWhatsApp(User $aluna): MensagemWhatsApp
    {
        $aula = $this->entrada->aula;
        $prazo = $this->entrada->oferta_expira_em?->format('H:i');

        $texto = $this->corridaFinal
            ? "Vagou um lugar em {$aula->modalidade->nome} hoje às {$aula->inicio->format('H:i')}. Como a aula está perto, avisamos toda a fila: fica com a vaga quem confirmar primeiro (até {$prazo})."
            : "Vagou um lugar em {$aula->modalidade->nome}, {$aula->quando()}. Você é a próxima da fila: confirme até {$prazo} para ficar com a vaga.";

        return new MensagemWhatsApp('vaga_oferecida', $texto, route('aluna.oferta', $this->entrada), 'Quero a vaga');
    }
}
