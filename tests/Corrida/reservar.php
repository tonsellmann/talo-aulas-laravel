<?php

// Um processo PHP de verdade tentando reservar: php tests/Corrida/reservar.php {aluna} {aula}
// Escreve "ok" ou o código da regra que impediu.

use App\Actions\ReservarAula;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\User;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$aluna, $aula] = [User::query()->findOrFail((int) $argv[1]), Aula::query()->findOrFail((int) $argv[2])];
// Espera o mesmo instante dos outros processos, para todos chegarem juntos.
time_sleep_until((float) $argv[3]);
try {
    app(ReservarAula::class)($aluna, $aula, avisar: false);
    echo 'ok';
} catch (RegraDeNegocio $erro) {
    echo $erro->codigo;
}
