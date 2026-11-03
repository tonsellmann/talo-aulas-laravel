<?php

use App\Actions\CancelarReserva;
use App\Actions\ReservarAula;
use App\Enums\OrigemLote;
use App\Enums\OrigemReserva;
use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\Ciclo;
use App\Models\LoteDeCredito;
use App\Models\Matricula;
use App\Models\MovimentoDeCredito;
use App\Models\Plano;
use App\Models\User;
use App\Support\Carteira;

function reservar(User $aluna, $aula, OrigemReserva $origem = OrigemReserva::Aluna)
{
    return app(ReservarAula::class)($aluna, $aula, $origem);
}

function codigoDoErro(callable $acao): ?string
{
    try {
        $acao();
    } catch (RegraDeNegocio $erro) {
        return $erro->codigo;
    }

    return null;
}

describe('reservar', function () {
    it('desconta uma aula da carteira, ocupa a vaga e deixa a linha no extrato', function () {
        $aluna = alunaCom(4);
        $aula = aulaEm(24);

        $reserva = reservar($aluna, $aula);

        expect($reserva->status)->toBe(StatusReserva::Confirmada)
            ->and(Carteira::de($aluna)->saldo())->toBe(3)
            ->and($aula->fresh()->ocupadas)->toBe(1)
            ->and(MovimentoDeCredito::query()->where('aluna_id', $aluna->id)->where('quantidade', -1)->count())->toBe(1);
    });

    it('recusa aula lotada, aula repetida e aluna sem aula na modalidade', function () {
        $aula = aulaEm(24, capacidade: 1);
        $primeira = alunaCom();
        reservar($primeira, $aula);

        expect(codigoDoErro(fn () => reservar(alunaCom(), $aula)))->toBe('lotada')
            ->and(codigoDoErro(fn () => reservar($primeira, $aula)))->toBe('ja_reservada');

        $soDeSolo = alunaCom(4, modalidades: ['solo']);
        expect(codigoDoErro(fn () => reservar($soDeSolo, aulaEm(26))))->toBe('sem_credito')
            ->and(Carteira::de($soDeSolo)->saldo())->toBe(4);
    });

    it('usa primeiro as aulas que vencem antes', function () {
        $aluna = alunaCom(2, validoAte: now()->addDays(20)->endOfDay());
        $venceAntes = Carteira::de($aluna)->creditar(1, OrigemLote::Cortesia, now()->startOfDay(), now()->addDays(5)->endOfDay(), null, 'Cortesia');

        $reserva = reservar($aluna, aulaEm(24));

        expect($reserva->lote_id)->toBe($venceAntes->id)
            ->and($venceAntes->fresh()->saldo)->toBe(0);
    });

    it('só abre a agenda uma semana antes e fecha meia hora antes para a aluna, mas não para a recepção', function () {
        $aluna = alunaCom();
        expect(codigoDoErro(fn () => reservar($aluna, aulaEm(24 * 8))))->toBe('agenda_fechada')
            ->and(codigoDoErro(fn () => reservar($aluna, aulaEm(0.25))))->toBe('em_cima_da_hora');

        $encaixe = reservar($aluna, aulaEm(0.25), OrigemReserva::Recepcao);
        expect($encaixe->status)->toBe(StatusReserva::Confirmada);
    });

    it('não vende a última aula do lote duas vezes', function () {
        $aluna = alunaCom(1);
        $lote = LoteDeCredito::query()->where('aluna_id', $aluna->id)->sole();
        $reserva = reservar($aluna, aulaEm(24));

        // Outra reserva que leu o mesmo lote antes do desconto não consegue descontar de novo.
        expect(fn () => Carteira::de($aluna)->debitar($lote, $reserva, 'segunda tentativa'))->toThrow(RegraDeNegocio::class)
            ->and($lote->fresh()->saldo)->toBe(0);
    });
});

describe('cancelar', function () {
    it('no prazo, a aula volta para a carteira e a vaga abre', function () {
        $aluna = alunaCom(4);
        $aula = aulaEm(24);
        $reserva = reservar($aluna, $aula);

        app(CancelarReserva::class)($reserva);

        expect($reserva->fresh()->status)->toBe(StatusReserva::Cancelada)
            ->and(Carteira::de($aluna)->saldo())->toBe(4)
            ->and($aula->fresh()->ocupadas)->toBe(0);
    });

    it('depois do prazo, a aula não volta, mas o plano com reposição dá uma aula extra', function () {
        $aluna = alunaCom(4);
        $plano = Plano::query()->create(['slug' => 'duas-por-semana', 'nome' => '2x por semana', 'aulas_por_ciclo' => 8, 'reposicoes_por_ciclo' => 1, 'preco_centavos' => 38000, 'ciclo_dias' => 30]);
        $matricula = Matricula::query()->create(['aluna_id' => $aluna->id, 'plano_id' => $plano->id, 'status' => 'ativa', 'inicio' => now()->toDateString()]);
        Ciclo::query()->create(['matricula_id' => $matricula->id, 'inicio' => now()->toDateString(), 'fim' => now()->addDays(29)->toDateString(), 'reposicoes_disponiveis' => 1]);
        $reserva = reservar($aluna, aulaEm(3));

        app(CancelarReserva::class)($reserva);

        $reposicao = LoteDeCredito::query()->where('aluna_id', $aluna->id)->where('origem', OrigemLote::Reposicao)->sole();
        expect($reserva->fresh()->status)->toBe(StatusReserva::CanceladaTarde)
            ->and($reposicao->saldo)->toBe(1)
            ->and($reposicao->valido_ate->toDateString())->toBe(now()->addDays(30)->toDateString())
            ->and(Carteira::de($aluna)->saldo())->toBe(4);
    });

    it('a recepção pode abonar o cancelamento fora do prazo', function () {
        $aluna = alunaCom(4);
        $reserva = reservar($aluna, aulaEm(3));

        app(CancelarReserva::class)($reserva, User::factory()->recepcao()->create(), abonar: true);

        expect($reserva->fresh()->status)->toBe(StatusReserva::Cancelada)
            ->and(Carteira::de($aluna)->saldo())->toBe(4);
    });
});
