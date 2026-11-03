<?php

namespace App\Support;

use App\Models\Feriado;
use Carbon\CarbonImmutable;
use Database\Seeders\DemonstracaoSeeder;
use Database\Seeders\EstudioSeeder;
use Illuminate\Support\Facades\Artisan;

final class Demonstracao
{
    /**
     * Monta o estúdio do zero. O relógio começa num dia útil às 9h40: aulas da
     * manhã já aconteceram, as do fim da tarde estão cheias e com fila.
     */
    public static function montar(): void
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', ['--class' => EstudioSeeder::class, '--force' => true]);
        Relogio::comecarEm(self::momentoInicial(CarbonImmutable::now()));
        Artisan::call('db:seed', ['--class' => DemonstracaoSeeder::class, '--force' => true]);
    }

    public static function momentoInicial(CarbonImmutable $base): CarbonImmutable
    {
        $dia = $base->startOfDay();
        $feriados = Feriado::query()->pluck('data')->map(fn ($d) => $d->toDateString())->all();

        while ($dia->isWeekend() || in_array($dia->toDateString(), $feriados, true)) {
            $dia = $dia->addDay();
        }

        return $dia->setTime(9, 40);
    }
}
