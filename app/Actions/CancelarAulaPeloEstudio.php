<?php

namespace App\Actions;

use App\Enums\StatusFila;
use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\AulaCanceladaPeloEstudio;
use App\Support\Carteira;
use Illuminate\Support\Facades\DB;

class CancelarAulaPeloEstudio
{
    public function __invoke(Aula $aula, string $motivo, User $por): void
    {
        [$reservas, $fila] = DB::transaction(function () use ($aula, $motivo, $por) {
            $aula = Aula::query()->with('modalidade')->whereKey($aula->id)->lockForUpdate()->firstOrFail();

            if ($aula->cancelada()) {
                throw RegraDeNegocio::aulaCancelada();
            }

            if ($aula->comecou()) {
                throw RegraDeNegocio::aulaJaComecou();
            }

            $reservas = $aula->reservas()->where('status', StatusReserva::Confirmada)->with('aluna', 'lote')->get();

            foreach ($reservas as $reserva) {
                /** @var Reserva $reserva */
                $reserva->update(['status' => StatusReserva::CanceladaPeloEstudio, 'cancelada_em' => now()]);
                $reserva->setRelation('aula', $aula);
                Carteira::de($reserva->aluna)->estornar($reserva, 'Aula cancelada pelo estúdio: '.$aula->descricao(), $por);
            }

            $fila = EntradaNaFila::query()->where('aula_id', $aula->id)
                ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta])
                ->with('aluna')->get();
            EntradaNaFila::query()->whereIn('id', $fila->pluck('id'))
                ->update(['status' => StatusFila::Encerrada, 'resolvida_em' => now()]);

            $aula->update(['cancelada_em' => now(), 'motivo_cancelamento' => $motivo, 'ocupadas' => 0]);

            return [$reservas, $fila];
        });

        foreach ($reservas as $reserva) {
            $reserva->aluna->notify(new AulaCanceladaPeloEstudio($aula, $motivo, true));
        }

        foreach ($fila as $entrada) {
            $entrada->aluna->notify(new AulaCanceladaPeloEstudio($aula, $motivo, false));
        }
    }
}
