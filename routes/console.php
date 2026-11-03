<?php

use App\Actions\EnviarLembretes;
use App\Actions\ExpirarCreditos;
use App\Actions\ExpirarOfertas;
use App\Actions\FecharAula;
use App\Actions\GerarAulas;
use App\Actions\GerarRenovacoes;
use App\Actions\ReservarAula;
use App\Actions\RodarRotinas;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\User;
use App\Support\Demonstracao;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('talo:demonstracao', function () {
    Demonstracao::montar();
    $this->info('Estúdio montado. Agora é '.now()->translatedFormat('l, d/m/Y H:i').' no relógio da demonstração.');
})->purpose('Apaga o banco e monta o estúdio da demonstração');

Artisan::command('talo:rotinas', function () {
    foreach (app(RodarRotinas::class)() as $rotina => $quantidade) {
        $this->line(str_pad($rotina, 18).$quantidade);
    }
})->purpose('Roda todas as varreduras do estúdio de uma vez');

Artisan::command('talo:reservar {aluna} {aula} {--esperar-ate=}', function (string $aluna, string $aula) {
    // Espera o mesmo instante dos outros processos, para as reservas baterem juntas no banco.
    if ($instante = $this->option('esperar-ate')) {
        time_sleep_until((float) $instante);
    }

    try {
        app(ReservarAula::class)(User::query()->findOrFail($aluna), Aula::query()->findOrFail($aula), avisar: false);

        return 0;
    } catch (RegraDeNegocio $e) {
        $this->line($e->codigo);

        return $e->codigo === 'lotada' ? 3 : 4;
    }
})->purpose('Reserva uma aula pela linha de comando (usado no teste de corrida)');

// Cada varredura processa tudo o que venceu até agora; se uma execução falhar,
// a próxima recupera o atraso sem repetir nada.
Schedule::call(fn () => app(ExpirarOfertas::class)())->name('fila')->everyMinute()->withoutOverlapping();
Schedule::call(fn () => FecharAula::pendentes())->name('fechar-aulas')->everyFiveMinutes()->withoutOverlapping();
Schedule::call(fn () => app(EnviarLembretes::class)())->name('lembretes')->everyFiveMinutes()->withoutOverlapping();
Schedule::call(fn () => app(GerarAulas::class)())->name('gerar-aulas')->dailyAt('02:00');
Schedule::call(fn () => app(ExpirarCreditos::class)())->name('expirar-creditos')->dailyAt('00:05');
Schedule::call(fn () => app(GerarRenovacoes::class)())->name('renovacoes')->dailyAt('08:00');
