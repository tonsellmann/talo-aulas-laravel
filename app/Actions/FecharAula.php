<?php

namespace App\Actions;

use App\Enums\StatusFila;
use App\Enums\StatusReserva;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use App\Notifications\FaltaRegistrada;
use App\Support\Carteira;
use Illuminate\Support\Facades\DB;

/** Depois da aula: quem não teve presença marcada vira falta. */
class FecharAula
{
    public function __construct(private readonly UsarReposicao $usarReposicao) {}

    public function __invoke(Aula $aula): void
    {
        $faltas = DB::transaction(function () use ($aula) {
            $aula = Aula::query()->with('modalidade')->whereKey($aula->id)->lockForUpdate()->firstOrFail();

            if ($aula->fechada_em !== null) {
                return [];
            }

            $faltas = [];

            $aula->reservas()->where('status', StatusReserva::Confirmada)->with('aluna')->get()
                ->each(function (Reserva $reserva) use ($aula, &$faltas) {
                    $reserva->setRelation('aula', $aula);
                    $reserva->update(['status' => StatusReserva::Falta]);
                    Carteira::de($reserva->aluna)->registrar('Falta em '.$aula->descricao().' ('.$aula->inicio->format('d/m').')', $reserva);
                    $faltas[] = [$reserva, ($this->usarReposicao)($reserva)];
                });

            EntradaNaFila::query()->where('aula_id', $aula->id)
                ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta])
                ->update(['status' => StatusFila::Encerrada, 'resolvida_em' => now()]);

            $aula->update(['fechada_em' => now()]);

            return $faltas;
        });

        foreach ($faltas as [$reserva, $reposicao]) {
            $reserva->aluna->notify(new FaltaRegistrada($reserva, $reposicao));
        }
    }

    /** Varredura: fecha as aulas que já terminaram. */
    public static function pendentes(): int
    {
        $tolerancia = config('talo.aulas.tolerancia_fechamento_minutos');
        $aulas = Aula::query()
            ->whereNull('fechada_em')
            ->whereNull('cancelada_em')
            ->where('fim', '<=', now()->subMinutes($tolerancia))
            ->get();

        $fechar = app(self::class);
        $aulas->each(fn (Aula $aula) => $fechar($aula));

        return $aulas->count();
    }
}
