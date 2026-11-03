<?php

namespace App\Actions;

use App\Enums\ModoFila;
use App\Enums\OrigemReserva;
use App\Enums\StatusFila;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Notifications\ReservadaPelaFila;
use App\Notifications\VagaOferecida;
use App\Notifications\VagaSemCredito;

/**
 * Entrega as vagas livres de uma aula para a fila de espera, na ordem de chegada.
 *
 * - Com folga até a aula, quem pediu "reservar sozinha" ganha a vaga na hora.
 * - Quem pediu aviso recebe uma oferta com prazo; a vaga fica guardada até lá.
 * - Perto da aula não há tempo para uma oferta de cada vez: a vaga vai para
 *   todas da fila ao mesmo tempo e fica com quem confirmar primeiro.
 *
 * Pode ser chamada quantas vezes for preciso: só age sobre vaga que ainda não
 * tem oferta aberta.
 */
class PromoverFila
{
    public function __construct(private readonly ReservarAula $reservar) {}

    public function __invoke(Aula $aula): void
    {
        // Limite de voltas só por segurança: cada volta resolve uma pessoa da fila.
        for ($volta = 0; $volta < 100; $volta++) {
            $aula->refresh();

            if ($aula->cancelada() || $aula->comecou()) {
                $this->encerrar($aula);

                return;
            }

            $ofertasAbertas = $aula->fila()->where('status', StatusFila::Oferta)->count();

            if ($aula->vagas() - $ofertasAbertas <= 0) {
                return;
            }

            /** @var EntradaNaFila|null $proxima */
            $proxima = $aula->fila()->where('status', StatusFila::Aguardando)->with('aluna')->first();

            if ($proxima === null) {
                return;
            }

            $minutos = (int) now()->diffInMinutes($aula->inicio, false);

            if ($minutos <= config('talo.fila.corrida_final_minutos')) {
                $this->corridaFinal($aula);

                return;
            }

            if ($proxima->modo === ModoFila::Automatico && $minutos >= config('talo.fila.automatico_ate_minutos_antes')) {
                $this->reservarDireto($proxima, $aula);

                continue;
            }

            $this->ofertar($proxima, $aula, corridaFinal: false);
        }
    }

    private function reservarDireto(EntradaNaFila $entrada, Aula $aula): void
    {
        try {
            $reserva = ($this->reservar)($entrada->aluna, $aula, OrigemReserva::Fila, avisar: false);
            $entrada->aluna->notify(new ReservadaPelaFila($reserva->load('aula.modalidade')));
        } catch (RegraDeNegocio $e) {
            if ($e->codigo !== 'sem_credito') {
                throw $e;
            }

            $entrada->update(['status' => StatusFila::SemCredito, 'resolvida_em' => now()]);
            $entrada->aluna->notify(new VagaSemCredito($entrada));
        }
    }

    private function ofertar(EntradaNaFila $entrada, Aula $aula, bool $corridaFinal): void
    {
        $limiteDaAula = $aula->inicio->subMinutes(config('talo.fila.oferta_ate_minutos_antes'));
        $prazo = $corridaFinal ? $limiteDaAula : now()->addMinutes(config('talo.fila.oferta_minutos'))->min($limiteDaAula);

        $entrada->update([
            'status' => StatusFila::Oferta,
            'oferta_enviada_em' => now(),
            'oferta_expira_em' => $prazo,
        ]);

        $entrada->aluna->notify(new VagaOferecida($entrada->setRelation('aula', $aula), $corridaFinal));
    }

    private function corridaFinal(Aula $aula): void
    {
        $aula->fila()->where('status', StatusFila::Aguardando)->with('aluna')->get()
            ->each(fn (EntradaNaFila $entrada) => $this->ofertar($entrada, $aula, corridaFinal: true));
    }

    private function encerrar(Aula $aula): void
    {
        EntradaNaFila::query()
            ->where('aula_id', $aula->id)
            ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta])
            ->update(['status' => StatusFila::Encerrada, 'resolvida_em' => now()]);
    }
}
