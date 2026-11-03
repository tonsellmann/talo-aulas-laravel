<?php

namespace App\Actions;

use App\Models\Aula;
use App\Models\Feriado;
use App\Models\Turma;
use Carbon\CarbonImmutable;

/** Transforma as turmas fixas em aulas com data, algumas semanas à frente. */
class GerarAulas
{
    public function __invoke(?int $semanas = null, ?CarbonImmutable $desde = null): int
    {
        $semanas ??= config('talo.aulas.semanas_geradas');
        $hoje = ($desde ?? now())->startOfDay();
        $ate = now()->startOfDay()->addWeeks($semanas);
        $feriados = Feriado::query()->whereBetween('data', [$hoje->toDateString(), $ate->toDateString()])->pluck('data')
            ->map(fn ($d) => $d->toDateString())->all();

        $criadas = 0;

        foreach (Turma::query()->where('ativa', true)->get() as $turma) {
            for ($dia = $hoje; $dia->lessThanOrEqualTo($ate); $dia = $dia->addDay()) {
                if ($dia->dayOfWeekIso !== $turma->dia_semana || in_array($dia->toDateString(), $feriados, true)) {
                    continue;
                }

                [$h, $m] = array_map('intval', explode(':', $turma->hora));
                $inicio = $dia->setTime($h, $m);

                $aula = Aula::query()->firstOrCreate(
                    ['turma_id' => $turma->id, 'inicio' => $inicio],
                    [
                        'modalidade_id' => $turma->modalidade_id,
                        'instrutora_id' => $turma->instrutora_id,
                        'fim' => $inicio->addMinutes($turma->duracao_minutos),
                        'nivel' => $turma->nivel,
                        'capacidade' => $turma->capacidade,
                    ],
                );

                $criadas += $aula->wasRecentlyCreated ? 1 : 0;
            }
        }

        return $criadas;
    }
}
