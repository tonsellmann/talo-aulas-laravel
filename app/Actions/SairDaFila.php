<?php

namespace App\Actions;

use App\Enums\StatusFila;
use App\Models\EntradaNaFila;

class SairDaFila
{
    public function __construct(private readonly PromoverFila $promoverFila) {}

    public function __invoke(EntradaNaFila $entrada): void
    {
        $tinhaOferta = $entrada->status === StatusFila::Oferta;
        $entrada->update(['status' => StatusFila::Saiu, 'resolvida_em' => now()]);

        // Se ela segurava uma vaga oferecida, a vaga segue para a próxima.
        if ($tinhaOferta) {
            ($this->promoverFila)($entrada->aula);
        }
    }
}
