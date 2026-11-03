<?php

namespace App\Actions;

use App\Enums\OrigemLote;
use App\Models\Ciclo;
use App\Models\LoteDeCredito;
use App\Models\Reserva;
use App\Support\Carteira;

/**
 * Plano com reposição: a primeira falta (ou cancelamento fora do prazo) do
 * ciclo vira uma aula extra com validade de 30 dias, sem a aluna pedir.
 */
class UsarReposicao
{
    public function __invoke(Reserva $reserva): ?LoteDeCredito
    {
        $aula = $reserva->aula;
        $dia = $aula->inicio->toDateString();

        $ciclo = Ciclo::query()
            ->whereHas('matricula', fn ($q) => $q->where('aluna_id', $reserva->aluna_id))
            ->where('inicio', '<=', $dia)
            ->where('fim', '>=', $dia)
            ->lockForUpdate()
            ->first();

        if ($ciclo === null || $ciclo->reposicoes_disponiveis < 1) {
            return null;
        }

        $ciclo->decrement('reposicoes_disponiveis');

        return Carteira::de($reserva->aluna)->creditar(
            quantidade: 1,
            origem: OrigemLote::Reposicao,
            validoDe: now(),
            validoAte: now()->addDays(config('talo.reposicao.validade_dias'))->endOfDay(),
            modalidades: $ciclo->matricula->modalidadesPermitidas(),
            descricao: 'Reposição de '.$aula->descricao().' ('.$aula->inicio->format('d/m').')',
            ciclo: $ciclo,
        );
    }
}
