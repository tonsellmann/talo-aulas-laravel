<?php

namespace App\Actions;

use App\Enums\StatusReserva;
use App\Models\Aula;
use App\Models\LoteDeCredito;
use App\Models\Reserva;
use App\Notifications\AulasVencendo;
use App\Notifications\LembreteDeAula;

class EnviarLembretes
{
    public function __invoke(): int
    {
        return $this->aulasDeHoje() + $this->aulasVencendo();
    }

    /** Lembrete algumas horas antes de cada aula reservada. */
    private function aulasDeHoje(): int
    {
        $reservas = Reserva::query()
            ->where('status', StatusReserva::Confirmada)
            ->whereNull('lembrete_enviado_em')
            ->whereHas('aula', fn ($q) => $q->whereNull('cancelada_em')
                ->where('inicio', '>', now())
                ->where('inicio', '<=', now()->addHours(config('talo.lembretes.aula_horas_antes'))))
            ->with('aula.modalidade', 'aula.instrutora', 'aluna')
            ->get();

        foreach ($reservas as $reserva) {
            $reserva->update(['lembrete_enviado_em' => now()]);
            $reserva->aluna->notify(new LembreteDeAula($reserva));
        }

        return $reservas->count();
    }

    /** Aviso de aulas que vão vencer sem uso, com uma sugestão de horário livre. */
    private function aulasVencendo(): int
    {
        $lotes = LoteDeCredito::query()
            ->where('saldo', '>', 0)
            ->whereNull('expirado_em')
            ->whereNull('aviso_vencimento_em')
            ->where('valido_ate', '>=', now())
            ->where('valido_ate', '<=', now()->addDays(config('talo.lembretes.creditos_dias_antes'))->endOfDay())
            ->with('aluna')
            ->get();

        foreach ($lotes->groupBy('aluna_id') as $lotesDaAluna) {
            /** @var LoteDeCredito $primeiro */
            $primeiro = $lotesDaAluna->sortBy('valido_ate')->first();
            $quantidade = (int) $lotesDaAluna->sum('saldo');

            $sugestao = Aula::query()->futuras()
                ->where('inicio', '>', now()->addHours(2))
                ->where('inicio', '<=', $primeiro->valido_ate)
                ->whereColumn('ocupadas', '<', 'capacidade')
                ->whereDoesntHave('reservas', fn ($q) => $q->where('aluna_id', $primeiro->aluna_id))
                ->with('modalidade')
                ->orderBy('inicio')
                ->get()
                ->first(fn (Aula $aula) => $primeiro->valeParaModalidade($aula->modalidade->slug));

            LoteDeCredito::query()->whereIn('id', $lotesDaAluna->pluck('id'))->update(['aviso_vencimento_em' => now()]);
            $primeiro->aluna->notify(new AulasVencendo($quantidade, $primeiro->valido_ate, $sugestao));
        }

        return $lotes->groupBy('aluna_id')->count();
    }
}
