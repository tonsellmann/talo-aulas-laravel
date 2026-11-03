<?php

namespace App\Actions;

use App\Enums\OrigemReserva;
use App\Enums\StatusFila;
use App\Exceptions\RegraDeNegocio;
use App\Models\EntradaNaFila;
use App\Models\Reserva;

class AceitarOferta
{
    public function __construct(private readonly ReservarAula $reservar) {}

    public function __invoke(EntradaNaFila $entrada): Reserva
    {
        if (! $entrada->ofertaValida()) {
            throw new RegraDeNegocio('O prazo dessa oferta acabou e a vaga seguiu para a próxima da fila.', 'oferta_expirada');
        }

        try {
            return ($this->reservar)($entrada->aluna, $entrada->aula, OrigemReserva::Fila);
        } catch (RegraDeNegocio $e) {
            if ($e->codigo === 'lotada') {
                // Na corrida final outra pessoa confirmou antes; ela continua na fila.
                $entrada->update(['status' => StatusFila::Aguardando, 'oferta_enviada_em' => null, 'oferta_expira_em' => null]);

                throw new RegraDeNegocio('Outra pessoa da fila confirmou antes de você. Você continua na fila, caso vague de novo.', 'perdeu_corrida');
            }

            throw $e;
        }
    }
}
