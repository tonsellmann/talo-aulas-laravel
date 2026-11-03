<?php

use App\Enums\OrigemLote;
use App\Models\Aula;
use App\Models\Modalidade;
use App\Models\User;
use App\Support\Carteira;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // Segunda, 2 de novembro de 2026, 9h em Ilhéus.
        $this->travelTo(CarbonImmutable::parse('2026-11-02 09:00:00', 'America/Bahia'));
        Notification::fake();
    })
    ->in('Feature');

// A corrida grava de verdade (sem transação em volta), para os outros processos enxergarem os dados.
pest()->extend(Tests\TestCase::class)
    ->beforeEach(fn () => Notification::fake())
    ->in('Corrida');

/** Uma aluna com aulas avulsas na carteira. */
function alunaCom(int $aulas = 4, ?array $modalidades = null, ?CarbonImmutable $validoAte = null): User
{
    $aluna = User::factory()->create();
    if ($aulas > 0) {
        Carteira::de($aluna)->creditar(
            quantidade: $aulas,
            origem: OrigemLote::Avulsa,
            validoDe: now()->startOfDay(),
            validoAte: $validoAte ?? now()->addDays(30)->endOfDay(),
            modalidades: $modalidades,
            descricao: 'Pacote de teste',
        );
    }

    return $aluna;
}

/** Uma aula de aparelhos daqui a $horas horas, com $capacidade vagas. */
function aulaEm(float $horas, int $capacidade = 4): Aula
{
    $inicio = now()->addMinutes((int) round($horas * 60));

    return Aula::factory()->create(['inicio' => $inicio, 'fim' => $inicio->addMinutes(50), 'capacidade' => $capacidade]);
}

function modalidade(string $slug): Modalidade
{
    return Modalidade::query()->firstOrCreate(['slug' => $slug], ['nome' => ucfirst($slug), 'capacidade' => 4, 'cor' => '#4a3560']);
}
