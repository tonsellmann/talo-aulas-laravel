<?php

use App\Actions\ExpirarCreditos;
use App\Actions\FecharAula;
use App\Actions\GerarAulas;
use App\Actions\ReservarAula;
use App\Enums\StatusCobranca;
use App\Enums\StatusReserva;
use App\Models\Aula;
use App\Models\Ciclo;
use App\Models\Cobranca;
use App\Models\Feriado;
use App\Models\Matricula;
use App\Models\Plano;
use App\Models\Turma;
use App\Models\User;
use App\Support\Carteira;
use App\Support\Pix\GatewayDeDemonstracao;

describe('rotinas', function () {
    it('aula que vence sem uso sai da carteira e fica no extrato', function () {
        $aluna = alunaCom(3, validoAte: now()->addDay()->endOfDay());
        $this->travel(3)->days();

        expect(app(ExpirarCreditos::class)())->toBe(1)
            ->and(Carteira::de($aluna)->saldo())->toBe(0)
            ->and($aluna->movimentos()->latest('id')->first()->descricao)->toStartWith('3 aulas venceram sem uso');
    });

    it('fecha a aula marcando falta para quem não veio, uma vez só', function () {
        $aluna = alunaCom();
        $aula = aulaEm(24);
        $reserva = app(ReservarAula::class)($aluna, $aula, avisar: false);
        $this->travel(26)->hours();

        app(FecharAula::class)($aula);
        app(FecharAula::class)($aula->fresh());

        expect($reserva->fresh()->status)->toBe(StatusReserva::Falta)
            ->and($aluna->movimentos()->where('descricao', 'like', 'Falta em%')->count())->toBe(1)
            ->and($aula->fresh()->fechada_em)->not->toBeNull();
    });

    it('gera as aulas das turmas pulando feriado, sem duplicar ao rodar de novo', function () {
        Turma::query()->create(['modalidade_id' => modalidade('aparelhos')->id, 'instrutora_id' => User::factory()->instrutora()->create()->id, 'dia_semana' => 5, 'hora' => '18:00', 'nivel' => 'Iniciante', 'capacidade' => 4]);
        Feriado::query()->create(['data' => '2026-11-20', 'nome' => 'Consciência Negra']);

        $criadas = app(GerarAulas::class)(semanas: 3);

        $datas = Aula::query()->orderBy('inicio')->get()->map(fn (Aula $a) => $a->inicio->format('d/m H:i'))->all();
        expect($criadas)->toBe(2)
            ->and($datas)->toBe(['06/11 18:00', '13/11 18:00'])
            ->and(app(GerarAulas::class)(semanas: 3))->toBe(0);
    });
});

describe('Pix', function () {
    function cobrancaDoPlano(int $valor = 38000): Cobranca
    {
        $aluna = User::factory()->create();
        $plano = Plano::query()->create(['slug' => 'duas-por-semana', 'nome' => '2x por semana', 'aulas_por_ciclo' => 8, 'reposicoes_por_ciclo' => 1, 'preco_centavos' => $valor, 'ciclo_dias' => 30]);
        $matricula = Matricula::query()->create(['aluna_id' => $aluna->id, 'plano_id' => $plano->id, 'status' => 'pendente', 'inicio' => now()->toDateString()]);

        return Cobranca::query()->create([
            'aluna_id' => $aluna->id, 'matricula_id' => $matricula->id, 'ciclo_inicio' => now()->toDateString(), 'descricao' => 'Plano 2x por semana',
            'valor_centavos' => $valor, 'status' => 'pendente', 'gateway' => 'demonstracao', 'gateway_id' => 'demo_teste', 'vence_em' => now()->addDays(3),
        ]);
    }

    function avisar(array $corpo, ?string $assinatura = null)
    {
        $json = json_encode($corpo);

        return test()->call('POST', '/webhooks/pix', [], [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_TALO_ASSINATURA' => $assinatura ?? GatewayDeDemonstracao::assinar($json)], $json);
    }

    it('recusa aviso sem a assinatura certa', function () {
        avisar(GatewayDeDemonstracao::avisoDePagamento(cobrancaDoPlano()), 'assinatura-falsa')->assertStatus(401);
    });

    it('pagamento confirmado abre o ciclo e põe as aulas na carteira; o mesmo aviso de novo não dá aula em dobro', function () {
        $cobranca = cobrancaDoPlano();
        $aviso = GatewayDeDemonstracao::avisoDePagamento($cobranca);

        avisar($aviso)->assertOk()->assertJson(['resultado' => 'ciclo_criado']);
        avisar($aviso)->assertOk()->assertJson(['resultado' => 'duplicado']);

        expect($cobranca->fresh()->status)->toBe(StatusCobranca::Paga)
            ->and(Ciclo::query()->count())->toBe(1)
            ->and(Carteira::de($cobranca->aluna)->saldo())->toBe(8)
            ->and($cobranca->matricula->fresh()->status->value)->toBe('ativa');
    });

    it('Pix com valor diferente fica para a recepção conferir, sem liberar aulas', function () {
        $cobranca = cobrancaDoPlano();

        avisar(GatewayDeDemonstracao::avisoDePagamento($cobranca, 30000))->assertOk();

        expect($cobranca->fresh()->status)->toBe(StatusCobranca::Divergente)
            ->and(Carteira::de($cobranca->aluna)->saldo())->toBe(0);
    });
});
