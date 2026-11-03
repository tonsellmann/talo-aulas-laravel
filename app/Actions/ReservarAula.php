<?php

namespace App\Actions;

use App\Enums\OrigemReserva;
use App\Enums\StatusFila;
use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\ReservaConfirmada;
use App\Support\Carteira;
use Illuminate\Support\Facades\DB;

class ReservarAula
{
    public function __invoke(
        User $aluna,
        Aula $aula,
        OrigemReserva $origem = OrigemReserva::Aluna,
        ?User $por = null,
        bool $avisar = true,
    ): Reserva {
        $this->conferirJanela($aula, $origem);

        $reserva = DB::transaction(function () use ($aluna, $aula, $origem, $por) {
            // Trava a linha da aula: quem chegar ao mesmo tempo espera esta transação
            // terminar e então enxerga o contador já atualizado.
            $aula = Aula::query()->with('modalidade')->whereKey($aula->id)->lockForUpdate()->firstOrFail();

            if ($aula->cancelada()) {
                throw RegraDeNegocio::aulaCancelada();
            }

            $reserva = Reserva::query()
                ->where('aula_id', $aula->id)
                ->where('aluna_id', $aluna->id)
                ->lockForUpdate()
                ->first();

            if ($reserva?->status->ocupaVaga()) {
                throw RegraDeNegocio::jaReservada();
            }

            if ($aula->lotada()) {
                throw RegraDeNegocio::aulaLotada();
            }

            $carteira = Carteira::de($aluna);
            $lote = $carteira->loteParaAula($aula, travar: true) ?? throw RegraDeNegocio::semCredito($aula);

            $reserva ??= new Reserva(['aula_id' => $aula->id, 'aluna_id' => $aluna->id]);
            $reserva->fill([
                'status' => StatusReserva::Confirmada,
                'origem' => $origem,
                'lote_id' => $lote->id,
                'cancelada_em' => null,
                'presenca_em' => null,
                'lembrete_enviado_em' => null,
            ])->save();
            $reserva->setRelation('aula', $aula);

            $carteira->debitar($lote, $reserva, 'Reserva: '.$aula->descricao().' ('.$aula->inicio->format('d/m').')', $por);
            $aula->increment('ocupadas');

            EntradaNaFila::query()
                ->where('aula_id', $aula->id)
                ->where('aluna_id', $aluna->id)
                ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta])
                ->update(['status' => StatusFila::Confirmada, 'resolvida_em' => now()]);

            return $reserva;
        });

        if ($avisar) {
            $aluna->notify(new ReservaConfirmada($reserva->load('aula.modalidade', 'aula.instrutora')));
        }

        return $reserva;
    }

    private function conferirJanela(Aula $aula, OrigemReserva $origem): void
    {
        if ($aula->comecou()) {
            throw RegraDeNegocio::aulaJaComecou();
        }

        if (now()->lessThan($aula->inicio->subDays(config('talo.reserva.abre_dias_antes')))) {
            throw RegraDeNegocio::agendaFechada($aula);
        }

        // A recepção pode encaixar alguém em cima da hora; a aluna sozinha, não.
        if ($origem === OrigemReserva::Aluna && now()->greaterThan($aula->inicio->subMinutes(config('talo.reserva.fecha_minutos_antes')))) {
            throw RegraDeNegocio::emCimaDaHora();
        }
    }
}
