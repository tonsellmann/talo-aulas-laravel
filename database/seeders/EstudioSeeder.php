<?php

namespace Database\Seeders;

use App\Enums\Papel;
use App\Models\Feriado;
use App\Models\Modalidade;
use App\Models\Plano;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/** Estrutura do estúdio: equipe, modalidades, turmas da semana, planos e feriados. */
class EstudioSeeder extends Seeder
{
    public function run(): void
    {
        $senha = Hash::make('talo2026');

        User::query()->create(['name' => 'Camila Rocha', 'email' => 'recepcao@talo.test', 'telefone' => '5573991110001', 'papel' => Papel::Recepcao, 'password' => $senha]);
        $taina = User::query()->create(['name' => 'Tainá Brito', 'email' => 'taina@talo.test', 'telefone' => '5573991110002', 'papel' => Papel::Instrutora, 'password' => $senha]);
        $bianca = User::query()->create(['name' => 'Bianca Moura', 'email' => 'bianca@talo.test', 'telefone' => '5573991110003', 'papel' => Papel::Instrutora, 'password' => $senha]);
        $luana = User::query()->create(['name' => 'Luana Santos', 'email' => 'luana@talo.test', 'telefone' => '5573991110004', 'papel' => Papel::Instrutora, 'password' => $senha]);

        $solo = Modalidade::query()->create(['slug' => 'solo', 'nome' => 'Solo', 'capacidade' => 8, 'cor' => '#c9b8ef', 'descricao' => 'No colchonete, com bola e faixa elástica.']);
        $aparelhos = Modalidade::query()->create(['slug' => 'aparelhos', 'nome' => 'Aparelhos', 'capacidade' => 4, 'cor' => '#4a3560', 'descricao' => 'Reformer, cadillac e chair. Quatro alunas por aula.']);
        $gestantes = Modalidade::query()->create(['slug' => 'gestantes', 'nome' => 'Gestantes', 'capacidade' => 4, 'cor' => '#f4a7c8', 'descricao' => 'A partir da 12ª semana, com liberação médica.']);
        $sessenta = Modalidade::query()->create(['slug' => 'sessenta', 'nome' => '60+', 'capacidade' => 4, 'cor' => '#8fd3b0', 'descricao' => 'Equilíbrio, força e mobilidade.']);

        // A mesma grade do site da Talo: manhã cedo, fim da tarde e sábado de manhã.
        $grade = [
            ['06:30', $aparelhos, $bianca, 'Intermediário', [1, 2, 3, 4, 5]],
            ['07:30', $solo, $taina, 'Iniciante', [1, 2, 3, 4, 5, 6]],
            ['09:00', $sessenta, $luana, 'Iniciante', [2, 4, 6]],
            ['10:00', $gestantes, $luana, 'Iniciante', [1, 3, 5]],
            ['17:30', $aparelhos, $bianca, 'Iniciante', [1, 2, 3, 4, 5]],
            ['18:30', $solo, $taina, 'Intermediário', [1, 2, 3, 4, 5]],
            ['19:30', $aparelhos, $bianca, 'Avançado', [1, 2, 3, 4]],
        ];

        foreach ($grade as [$hora, $modalidade, $instrutora, $nivel, $dias]) {
            foreach ($dias as $dia) {
                Turma::query()->create([
                    'modalidade_id' => $modalidade->id,
                    'instrutora_id' => $instrutora->id,
                    'dia_semana' => $dia,
                    'hora' => $hora,
                    'duracao_minutos' => 50,
                    'nivel' => $nivel,
                    'capacidade' => $modalidade->capacidade,
                ]);
            }
        }

        Plano::query()->create(['slug' => '2x', 'nome' => '2x por semana', 'aulas_por_ciclo' => 8, 'reposicoes_por_ciclo' => 1, 'modalidades' => null, 'escolhe_modalidade' => true, 'preco_centavos' => 28000, 'ciclo_dias' => 30]);
        Plano::query()->create(['slug' => '3x', 'nome' => '3x por semana', 'aulas_por_ciclo' => 12, 'reposicoes_por_ciclo' => 2, 'modalidades' => ['solo', 'aparelhos'], 'escolhe_modalidade' => false, 'preco_centavos' => 36000, 'ciclo_dias' => 30]);
        Plano::query()->create(['slug' => 'avulsa', 'nome' => 'Aula avulsa', 'aulas_por_ciclo' => 1, 'reposicoes_por_ciclo' => 0, 'modalidades' => null, 'escolhe_modalidade' => false, 'preco_centavos' => 6500, 'ciclo_dias' => 30]);

        $ano = now()->year;
        foreach ([$ano, $ano + 1] as $a) {
            foreach (['01-01' => 'Confraternização Universal', '04-21' => 'Tiradentes', '05-01' => 'Dia do Trabalho', '06-24' => 'São João', '07-02' => 'Independência da Bahia', '09-07' => 'Independência do Brasil', '10-12' => 'Nossa Senhora Aparecida', '11-02' => 'Finados', '11-15' => 'Proclamação da República', '11-20' => 'Consciência Negra', '12-25' => 'Natal'] as $data => $nome) {
                Feriado::query()->create(['data' => "{$a}-{$data}", 'nome' => $nome]);
            }
        }
    }
}
