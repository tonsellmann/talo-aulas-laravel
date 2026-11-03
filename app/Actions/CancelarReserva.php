<?php

namespace App\Actions;

use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\Ciclo;
use App\Models\LoteDeCredito;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\CancelamentoRegistrado;
use App\Support\Carteira;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CancelarReserva
{
    public function __construct(private readonly UsarReposicao $usarReposicao, private readonly PromoverFila $promoverFila) {}

    /**
     * @param  bool  $abonar  a recepção pode cancelar fora do prazo devolvendo a aula (atestado, por exemplo)
     */
    public function __invoke(Reserva $reserva, ?User $por = null, bool $abonar = false): Reserva
    {
        /** @var array{0: Reserva, 1: bool, 2: LoteDeCredito|null} $resultado */
        $resultado = DB::transaction(function () use ($reserva, $por, $abonar) {
            $aula = Aula::query()->with('modalidade')->whereKey($reserva->aula_id)->lockForUpdate()->firstOrFail();
            $reserva = Reserva::query()->whereKey($reserva->id)->lockForUpdate()->firstOrFail();
            $reserva->setRelation('aula', $aula);

            if ($reserva->status !== StatusReserva::Confirmada) {
                throw new RegraDeNegocio('Essa reserva não está mais ativa.');
            }

            if ($aula->comecou()) {
                throw RegraDeNegocio::aulaJaComecou();
            }

            $noPrazo = $abonar || $reserva->dentroDoPrazo();
            $carteira = Carteira::de($reserva->aluna);
            $reposicao = null;

            if ($noPrazo) {
                $reserva->update(['status' => StatusReserva::Cancelada, 'cancelada_em' => now()]);
                $carteira->estornar($reserva, ($abonar && ! $reserva->dentroDoPrazo() ? 'Cancelamento abonado pela recepção: ' : 'Cancelamento no prazo: ').$aula->descricao(), $por);
            } else {
                $reserva->update(['status' => StatusReserva::CanceladaTarde, 'cancelada_em' => now()]);
                $carteira->registrar('Cancelamento depois do prazo: '.$aula->descricao().' (a aula não volta)', $reserva, $por);
                $reposicao = ($this->usarReposicao)($reserva);
            }

            $aula->decrement('ocupadas');

            return [$reserva, $noPrazo, $reposicao];
        });

        [$reserva, $noPrazo, $reposicao] = $resultado;

        $reserva->aluna->notify(new CancelamentoRegistrado($reserva, $noPrazo, $reposicao));

        // A vaga que abriu vai para a fila.
        ($this->promoverFila)($reserva->aula);

        return $reserva;
    }

    /**
     * O que vai acontecer se a aluna cancelar agora. A tela mostra isso antes
     * de ela confirmar, para ninguém perder aula sem saber.
     *
     * @return array{no_prazo: bool, prazo: CarbonImmutable, reposicoes: int}
     */
    public static function consequencia(Reserva $reserva): array
    {
        $dia = $reserva->aula->inicio->toDateString();
        $reposicoes = (int) Ciclo::query()
            ->whereHas('matricula', fn ($q) => $q->where('aluna_id', $reserva->aluna_id))
            ->where('inicio', '<=', $dia)
            ->where('fim', '>=', $dia)
            ->value('reposicoes_disponiveis');

        return [
            'no_prazo' => $reserva->dentroDoPrazo(),
            'prazo' => $reserva->prazoDeCancelamento(),
            'reposicoes' => $reposicoes,
        ];
    }
}
