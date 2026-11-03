<?php

namespace Database\Seeders;

use App\Actions\EntrarNaFila;
use App\Actions\GerarAulas;
use App\Actions\ReservarAula;
use App\Actions\RodarRotinas;
use App\Actions\UsarReposicao;
use App\Enums\ModoFila;
use App\Enums\OrigemLote;
use App\Enums\OrigemReserva;
use App\Enums\Papel;
use App\Enums\StatusCobranca;
use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\Ciclo;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Models\Modalidade;
use App\Models\NotaDeSaude;
use App\Models\Plano;
use App\Models\Reserva;
use App\Models\Turma;
use App\Models\User;
use App\Support\Carteira;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Um estúdio em funcionamento: três semanas de histórico, a semana atual com
 * aulas cheias e fila de espera, e a Marina no meio de tudo, que é por quem a
 * demonstração começa. Tudo é calculado a partir de "agora", então o cenário
 * fica certo em qualquer data.
 */
class DemonstracaoSeeder extends Seeder
{
    /** @var array<string, Modalidade> */
    private array $modalidades = [];

    /** @var array<string, Plano> */
    private array $planos = [];

    private CarbonImmutable $agora;

    public function run(): void
    {
        // Uma transação só: no SQLite isso deixa a carga dezenas de vezes mais rápida.
        DB::transaction(fn () => $this->montar());
    }

    private function montar(): void
    {
        mt_srand(2026);
        $this->agora = now();
        $this->modalidades = Modalidade::all()->keyBy('slug')->all();
        $this->planos = Plano::all()->keyBy('slug')->all();
        $recepcao = User::query()->where('papel', Papel::Recepcao)->firstOrFail();

        app(GerarAulas::class)(desde: $this->agora->subWeeks(3)->startOfWeek(CarbonImmutable::MONDAY));

        $senha = Hash::make('talo2026');
        $marina = $this->aluna('Marina Couto', 'aluna@talo.test', $senha);
        $alunas = collect($this->nomes())->map(fn (string $nome, int $i) => $this->aluna($nome, 'aluna'.($i + 1).'@talo.test', $senha))->values();

        // Marina: 2x por semana em Aparelhos, ciclo terminando em três dias.
        $this->matricular($marina, $this->planos['2x'], $this->modalidades['aparelhos'], $this->agora->subDays(26)->startOfDay());
        // A penúltima aula dela foi falta: virou reposição, que ainda está no saldo.
        $this->historico($marina, ['aparelhos' => ['17:30' => [2, 4]]], faltasContandoDoFim: [2]);

        foreach ($alunas as $i => $aluna) {
            [$plano, $modalidade, $horarios] = $this->perfil($i);
            $this->matricular($aluna, $plano, $modalidade, $this->agora->subDays(mt_rand(1, 27))->startOfDay());
            $this->historico($aluna, $horarios);
        }

        foreach (collect([$marina])->merge($alunas) as $aluna) {
            $this->vencerLotesAntigos($aluna);
        }

        $this->lotarAulasConcorridas($alunas, $marina);
        $this->notasDeSaude($alunas, $recepcao);
        $this->umaDivergencia();

        // As rotinas rodam como rodariam no servidor: geram o Pix da Marina,
        // os avisos de aulas vencendo e os lembretes do dia.
        app(RodarRotinas::class)();
    }

    private function aluna(string $nome, string $email, string $senha): User
    {
        return User::query()->create([
            'name' => $nome,
            'email' => $email,
            'telefone' => '55739'.str_pad((string) mt_rand(81000000, 99999999), 8, '0'),
            'papel' => Papel::Aluna,
            'password' => $senha,
        ]);
    }

    private function matricular(User $aluna, Plano $plano, ?Modalidade $modalidade, CarbonImmutable $inicioDoCiclo): void
    {
        $matricula = Matricula::query()->create([
            'aluna_id' => $aluna->id,
            'plano_id' => $plano->id,
            'modalidade_id' => $plano->escolhe_modalidade ? $modalidade?->id : null,
            'status' => 'ativa',
            'renovacao_automatica' => $plano->slug !== 'avulsa',
            'inicio' => $inicioDoCiclo->subDays($plano->ciclo_dias * mt_rand(1, 8)),
        ]);

        $this->ciclo($matricula, $plano, $inicioDoCiclo);
    }

    private function ciclo(Matricula $matricula, Plano $plano, CarbonImmutable $inicio): Ciclo
    {
        $fim = $inicio->addDays($plano->ciclo_dias - 1);
        $ciclo = Ciclo::query()->create([
            'matricula_id' => $matricula->id,
            'inicio' => $inicio,
            'fim' => $fim,
            'reposicoes_disponiveis' => $plano->reposicoes_por_ciclo,
            'status' => 'pago',
        ]);

        $matricula->load('plano', 'modalidade', 'aluna');
        $carteira = Carteira::de($matricula->aluna);
        $carteira->creditar(
            quantidade: $plano->aulas_por_ciclo,
            origem: OrigemLote::Ciclo,
            validoDe: $inicio->startOfDay(),
            validoAte: $fim->endOfDay(),
            modalidades: $matricula->modalidadesPermitidas(),
            descricao: "{$plano->nome}: {$plano->aulas_por_ciclo} aulas de {$inicio->format('d/m')} a {$fim->format('d/m')}",
            ciclo: $ciclo,
        );
        $this->datarUltimoMovimento($matricula->aluna, $inicio->setTime(8, 2));

        Cobranca::query()->create([
            'aluna_id' => $matricula->aluna_id,
            'matricula_id' => $matricula->id,
            'ciclo_inicio' => $inicio,
            'descricao' => $plano->nome.': '.$inicio->format('d/m').' a '.$fim->format('d/m'),
            'valor_centavos' => $plano->preco_centavos,
            'status' => StatusCobranca::Paga,
            'gateway' => 'demonstracao',
            'gateway_id' => 'demo_hist_'.$matricula->id.'_'.$inicio->format('Ymd'),
            'vence_em' => $inicio->subDays(2),
            'paga_em' => $inicio->subDays(mt_rand(2, 4))->setTime(mt_rand(8, 21), mt_rand(0, 59)),
            'valor_pago_centavos' => $plano->preco_centavos,
        ]);

        return $ciclo;
    }

    /**
     * Reservas nos horários de costume: passadas com presença (ou falta),
     * futuras dentro da janela de uma semana.
     *
     * @param  array<string, array<string, list<int>>>  $horarios  modalidade => hora => dias da semana
     * @param  list<int>  $faltasContandoDoFim  aulas passadas que viram falta (1 = a mais recente)
     */
    private function historico(User $aluna, array $horarios, array $faltasContandoDoFim = []): void
    {
        $aulas = collect();
        foreach ($horarios as $slug => $porHora) {
            foreach ($porHora as $hora => $dias) {
                $aulas = $aulas->merge(Aula::query()
                    ->where('modalidade_id', $this->modalidades[$slug]->id)
                    ->whereHas('turma', fn ($q) => $q->where('hora', $hora))
                    ->where('inicio', '<=', $this->agora->addDays(7))
                    ->with('modalidade', 'instrutora')
                    ->get()
                    ->filter(fn (Aula $a) => in_array($a->inicio->dayOfWeekIso, $dias, true)));
            }
        }

        $aulas = $aulas->sortBy('inicio')->values();
        $totalPassadas = $aulas->filter(fn (Aula $a) => $a->inicio->lessThan($this->agora))->count();
        $passadas = 0;
        foreach ($aulas as $aula) {
            if ($aula->inicio->lessThan($this->agora)) {
                $doFim = $totalPassadas - $passadas;
                $falta = $faltasContandoDoFim ? in_array($doFim, $faltasContandoDoFim, true) : mt_rand(1, 100) <= 6;
                $this->reservarNoPassado($aluna, $aula, $falta ? StatusReserva::Falta : StatusReserva::Presente);
                $passadas++;

                continue;
            }

            try {
                app(ReservarAula::class)($aluna, $aula, OrigemReserva::Aluna, avisar: false);
            } catch (RegraDeNegocio) {
                // Sem vaga ou sem aula no saldo: segue a vida, como na vida real.
            }
        }
    }

    private function reservarNoPassado(User $aluna, Aula $aula, StatusReserva $status): void
    {
        if ($aula->lotada() || $aula->fechada_em !== null) {
            return;
        }

        $carteira = Carteira::de($aluna);
        $lote = $aluna->lotes()->where('valido_de', '<=', $aula->inicio)->where('valido_ate', '>=', $aula->inicio)
            ->where('saldo', '>', 0)->orderBy('valido_ate')->get()
            ->first(fn ($l) => $l->valeParaModalidade($aula->modalidade->slug));

        if ($lote === null) {
            return;
        }

        $marcada = $aula->inicio->subDays(mt_rand(1, 5))->setTime(mt_rand(7, 21), mt_rand(0, 59));
        $reserva = Reserva::query()->create([
            'aula_id' => $aula->id,
            'aluna_id' => $aluna->id,
            'lote_id' => $lote->id,
            'status' => $status,
            'origem' => OrigemReserva::Aluna,
            'lembrete_enviado_em' => $aula->inicio->subHours(3),
            'presenca_em' => $status === StatusReserva::Presente ? $aula->inicio->addMinutes(mt_rand(-8, 4)) : null,
            'created_at' => $marcada,
            'updated_at' => $marcada,
        ]);
        $reserva->setRelation('aula', $aula);
        $carteira->debitar($lote, $reserva, 'Reserva: '.$aula->descricao().' ('.$aula->inicio->format('d/m').')');
        $this->datarUltimoMovimento($aluna, $marcada);
        $aula->increment('ocupadas');

        if ($status === StatusReserva::Falta) {
            $carteira->registrar('Falta em '.$aula->descricao().' ('.$aula->inicio->format('d/m').')', $reserva);
            $this->datarUltimoMovimento($aluna, $aula->fim->addMinutes(15));
            if ($reposicao = app(UsarReposicao::class)($reserva)) {
                $reposicao->update(['valido_de' => $aula->fim->addMinutes(15), 'valido_ate' => $aula->fim->addDays(config('talo.reposicao.validade_dias'))->endOfDay()]);
                $this->datarUltimoMovimento($aluna, $aula->fim->addMinutes(15));
            }
        }
    }

    /**
     * Horários disputados ficam cheios e com fila, como num estúdio de verdade.
     *
     * @param  Collection<int, User>  $alunas
     */
    private function lotarAulasConcorridas(Collection $alunas, User $marina): void
    {
        $concorridas = Aula::query()->futuras()
            ->where('inicio', '<=', $this->agora->addDays(3))
            ->whereIn('turma_id', Turma::query()->whereIn('hora', ['17:30', '18:30', '19:30'])->pluck('id'))
            ->with('modalidade', 'instrutora')
            ->orderBy('inicio')
            ->get();

        foreach ($concorridas as $indice => $aula) {
            foreach ($this->embaralhar($alunas) as $aluna) {
                if ($aula->refresh()->lotada()) {
                    break;
                }
                try {
                    app(ReservarAula::class)($aluna, $aula, OrigemReserva::Aluna, avisar: false);
                } catch (RegraDeNegocio) {
                }
            }

            if ($indice % 2 === 0) {
                foreach ($this->embaralhar($alunas)->take(mt_rand(1, 3)) as $aluna) {
                    try {
                        app(EntrarNaFila::class)($aluna, $aula->refresh(), mt_rand(0, 1) ? ModoFila::Automatico : ModoFila::Avisar);
                    } catch (RegraDeNegocio) {
                    }
                }
            }
        }

        // A Marina está na fila de uma aula de Aparelhos lotada, esperando aviso.
        $aulaDaFila = $concorridas->first(fn (Aula $a) => $a->modalidade->slug === 'aparelhos'
            && $a->refresh()->lotada()
            && ! $a->reservas()->where('aluna_id', $marina->id)->exists());

        if ($aulaDaFila) {
            // Ela entrou na fila ontem: é a primeira, então a próxima vaga que abrir é oferecida a ela.
            $entrada = app(EntrarNaFila::class)($marina, $aulaDaFila, ModoFila::Avisar);
            $entrada->created_at = $this->agora->subDay()->setTime(21, 14);
            $entrada->save();
        }
    }

    /** @param  Collection<int, User>  $alunas */
    private function notasDeSaude(Collection $alunas, User $recepcao): void
    {
        $notas = [
            'Hérnia de disco L4-L5. Evitar flexão de tronco com carga.',
            'Pós-operatório de joelho direito (LCA), liberada pelo fisioterapeuta em setembro.',
            'Hipertensa, controlada. Evitar inversões prolongadas.',
            'Gestante de 24 semanas. Sem decúbito dorsal por mais de 3 minutos.',
            'Escoliose lombar leve. Fortalecer core e alongar cadeia posterior.',
            'Tendinite no ombro esquerdo. Sem apoio de peso no braço por enquanto.',
        ];

        foreach ($alunas->take(count($notas)) as $i => $aluna) {
            NotaDeSaude::query()->create(['aluna_id' => $aluna->id, 'texto' => $notas[$i], 'criado_por' => $recepcao->id]);
        }
    }

    private function umaDivergencia(): void
    {
        // Um Pix pago com valor diferente do cobrado, para a recepção resolver.
        $cobranca = Cobranca::query()->where('status', StatusCobranca::Paga)->latest('paga_em')->skip(3)->first();
        $cobranca?->update(['status' => StatusCobranca::Divergente, 'valor_pago_centavos' => $cobranca->valor_centavos - 2800]);
    }

    /** Sobra de ciclos passados vence na data certa, como teria vencido. */
    private function vencerLotesAntigos(User $aluna): void
    {
        foreach ($aluna->lotes()->whereNull('expirado_em')->where('valido_ate', '<', $this->agora)->get() as $lote) {
            $tinhaSobra = $lote->saldo > 0;
            Carteira::de($aluna)->expirar($lote);
            $quando = $lote->valido_ate->addDay()->setTime(0, 5);
            $lote->update(['expirado_em' => $quando]);
            if ($tinhaSobra) {
                $this->datarUltimoMovimento($aluna, $quando);
            }
        }
    }

    /**
     * Embaralha sempre na mesma ordem (mt_srand no começo), para o cenário ser repetível.
     *
     * @template T
     *
     * @param  Collection<int, T>  $itens
     * @return Collection<int, T>
     */
    private function embaralhar(Collection $itens): Collection
    {
        $lista = $itens->values()->all();
        for ($i = count($lista) - 1; $i > 0; $i--) {
            $j = mt_rand(0, $i);
            [$lista[$i], $lista[$j]] = [$lista[$j], $lista[$i]];
        }

        return collect($lista);
    }

    /**
     * Plano, modalidade e horários de costume de cada aluna. As rotinas se
     * repetem em ciclo para todo horário da grade ter suas frequentadoras.
     *
     * @return array{0: Plano, 1: Modalidade|null, 2: array<string, array<string, list<int>>>}
     */
    private function perfil(int $i): array
    {
        $rotinas = [
            ['2x', 'aparelhos', ['aparelhos' => ['17:30' => [1, 3]]]],
            ['2x', 'solo', ['solo' => ['18:30' => [2, 4]]]],
            ['3x', null, ['aparelhos' => ['17:30' => [2, 4]], 'solo' => ['18:30' => [5]]]],
            ['2x', 'aparelhos', ['aparelhos' => ['06:30' => [1, 3]]]],
            ['2x', 'solo', ['solo' => ['07:30' => [2, 4]]]],
            ['2x', 'aparelhos', ['aparelhos' => ['19:30' => [2, 4]]]],
            ['2x', 'sessenta', ['sessenta' => ['09:00' => [2, 4]]]],
            ['3x', null, ['aparelhos' => ['06:30' => [2, 4]], 'solo' => ['07:30' => [6]]]],
            ['2x', 'gestantes', ['gestantes' => ['10:00' => [1, 3]]]],
            ['2x', 'solo', ['solo' => ['18:30' => [1, 3]]]],
            ['2x', 'aparelhos', ['aparelhos' => ['17:30' => [3, 5]]]],
            ['avulsa', null, ['solo' => ['07:30' => [6]]]],
            ['2x', 'aparelhos', ['aparelhos' => ['19:30' => [1, 3]]]],
            ['3x', null, ['solo' => ['07:30' => [1, 3, 5]]]],
            ['2x', 'sessenta', ['sessenta' => ['09:00' => [4, 6]]]],
            ['2x', 'aparelhos', ['aparelhos' => ['06:30' => [3, 5]]]],
            ['2x', 'gestantes', ['gestantes' => ['10:00' => [3, 5]]]],
            ['2x', 'solo', ['solo' => ['18:30' => [3, 5]]]],
            ['3x', null, ['aparelhos' => ['19:30' => [2]], 'solo' => ['18:30' => [1, 3]]]],
            ['2x', 'solo', ['solo' => ['07:30' => [1, 5]]]],
        ];

        [$plano, $modalidade, $horarios] = $rotinas[$i % count($rotinas)];

        return [$this->planos[$plano], $modalidade ? $this->modalidades[$modalidade] : null, $horarios];
    }

    private function datarUltimoMovimento(User $aluna, CarbonImmutable $quando): void
    {
        $aluna->movimentos()->latest('id')->limit(1)->update(['created_at' => $quando]);
    }

    /** @return list<string> */
    private function nomes(): array
    {
        return [
            'Joana Ribeiro', 'Helena Matos', 'Luíza Campos', 'Beatriz Andrade', 'Carolina Teles', 'Fernanda Lima',
            'Juliana Prates', 'Patrícia Nery', 'Renata Queiroz', 'Sofia Brandão', 'Isabela Pires', 'Mariana Freitas',
            'Paula Sampaio', 'Débora Cardoso', 'Tatiana Leal', 'Gabriela Dantas', 'Aline Barreto', 'Simone Galvão',
            'Rosângela Dias', 'Iara Menezes', 'Natália Seixas', 'Elaine Sá', 'Bruna Guimarães', 'Clara Novaes',
            'Raquel Pedreira', 'Ana Paula Lacerda', 'Lívia Carneiro', 'Tânia Mascarenhas', 'Yasmin Oliveira', 'Marta Coutinho',
            'Adriana Peixoto', 'Cecília Moraes', 'Vitória Bastos', 'Kátia Albuquerque', 'Manuela Rios', 'Silvia Torres',
            'Laura Medrado', 'Teresa Gondim', 'Heloísa Pinho', 'Michele Araújo', 'Daniela Vilas Boas', 'Lorena Brito Neves',
            'Regina Falcão', 'Viviane Souza', 'Alice Mendes', 'Cláudia Reis',
        ];
    }
}
