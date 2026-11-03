<?php

namespace App\Actions;

use App\Enums\ModoFila;
use App\Enums\StatusFila;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use App\Models\User;

class EntrarNaFila
{
    public function __invoke(User $aluna, Aula $aula, ModoFila $modo): EntradaNaFila
    {
        // Lê de novo: quem chamou pode ter uma cópia antiga, de antes da última reserva.
        $aula->refresh();

        if ($aula->cancelada()) {
            throw RegraDeNegocio::aulaCancelada();
        }

        if ($aula->comecou()) {
            throw RegraDeNegocio::aulaJaComecou();
        }

        if (! $aula->lotada()) {
            throw new RegraDeNegocio('Ainda tem vaga nessa aula: reserve direto.', 'tem_vaga');
        }

        $reservada = Reserva::query()->where('aula_id', $aula->id)->where('aluna_id', $aluna->id)
            ->get()->contains(fn (Reserva $r) => $r->status->ocupaVaga());

        if ($reservada) {
            throw RegraDeNegocio::jaReservada();
        }

        $entrada = EntradaNaFila::query()->firstOrNew(['aula_id' => $aula->id, 'aluna_id' => $aluna->id]);

        if ($entrada->exists && $entrada->status->ativa()) {
            $entrada->update(['modo' => $modo]);

            return $entrada;
        }

        // Quem volta para a fila entra no fim dela.
        $entrada->fill([
            'modo' => $modo,
            'status' => StatusFila::Aguardando,
            'oferta_enviada_em' => null,
            'oferta_expira_em' => null,
            'resolvida_em' => null,
        ]);
        $entrada->created_at = now();
        $entrada->save();

        return $entrada;
    }
}
