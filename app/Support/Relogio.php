<?php

namespace App\Support;

use App\Models\Ajuste;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Date;

/**
 * Relógio da demonstração. Guarda só a diferença para a hora real, então o
 * tempo continua correndo normalmente depois de avançar: um prazo de
 * 30 minutos vence de verdade se a pessoa esperar.
 */
final class Relogio
{
    private const CHAVE = 'relogio_diferenca_segundos';

    public static function aplicar(): void
    {
        if (! config('talo.demonstracao')) {
            return;
        }

        Date::setTestNow();

        try {
            $diferenca = (int) Ajuste::ler(self::CHAVE, '0');
        } catch (QueryException) {
            // Banco ainda vazio (a demonstração está sendo montada): relógio normal.
            return;
        }

        if ($diferenca !== 0) {
            Date::setTestNow(CarbonImmutable::now()->addSeconds($diferenca));
        }
    }

    public static function avancar(int $segundos): void
    {
        $atual = (int) Ajuste::ler(self::CHAVE, '0');
        Ajuste::gravar(self::CHAVE, (string) ($atual + $segundos));
        self::aplicar();
    }

    /** Começa a demonstração num horário em que o estúdio está funcionando. */
    public static function comecarEm(CarbonImmutable $momento): void
    {
        Date::setTestNow();
        Ajuste::gravar(self::CHAVE, (string) (int) CarbonImmutable::now()->diffInSeconds($momento, false));
        self::aplicar();
    }
}
