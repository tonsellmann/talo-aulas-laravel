<?php

use App\Enums\StatusReserva;
use App\Models\Reserva;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

// Precisa de um banco que trave linha (MySQL): no SQLite o arquivo inteiro trava e a corrida não acontece.
uses(DatabaseMigrations::class);

it('oito processos disputando a última vaga no mesmo segundo: entra uma e sete recebem "lotada"', function () {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('A corrida roda no MySQL (DB_CONNECTION=mysql).');
    }
    $aula = aulaEm(24, capacidade: 1);
    $alunas = collect(range(1, 8))->map(fn () => alunaCom(4));
    $largada = microtime(true) + 2;

    $processos = $alunas->map(function ($aluna) use ($aula, $largada) {
        $processo = new Process(['php', 'tests/Corrida/reservar.php', $aluna->id, $aula->id, (string) $largada], base_path(), timeout: 60);
        $processo->start();

        return $processo;
    });
    $resultados = $processos->map(function (Process $p) {
        $p->wait();

        return trim($p->getOutput()) ?: trim($p->getErrorOutput());
    })->countBy()->all();

    expect($resultados)->toEqual(['ok' => 1, 'lotada' => 7])
        ->and($aula->fresh()->ocupadas)->toBe(1)
        ->and(Reserva::query()->where('aula_id', $aula->id)->where('status', StatusReserva::Confirmada)->count())->toBe(1);
});
