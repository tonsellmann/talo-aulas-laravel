<?php

namespace App\Actions;

use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\Reserva;

class RegistrarPresenca
{
    public function __invoke(Reserva $reserva, bool $presente = true): Reserva
    {
        $aula = $reserva->aula;

        if ($aula->fechada_em !== null) {
            throw new RegraDeNegocio('A lista dessa aula já foi fechada.');
        }

        if (now()->lessThan($aula->inicio->subMinutes(30))) {
            throw new RegraDeNegocio('A presença abre meia hora antes da aula.');
        }

        if (! in_array($reserva->status, [StatusReserva::Confirmada, StatusReserva::Presente], true)) {
            throw new RegraDeNegocio('Essa aluna não está na lista da aula.');
        }

        $reserva->update($presente
            ? ['status' => StatusReserva::Presente, 'presenca_em' => now()]
            : ['status' => StatusReserva::Confirmada, 'presenca_em' => null]);

        return $reserva;
    }
}
