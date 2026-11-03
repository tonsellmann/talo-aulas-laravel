<?php

namespace App\Casts;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Data sem hora, gravada sempre como "AAAA-MM-DD".
 *
 * O cast "date" do Laravel grava "AAAA-MM-DD 00:00:00" quando o banco não
 * tem tipo de data (SQLite). Aí "inicio <= '2026-11-07'" falha no próprio
 * dia 7, e a regra muda conforme o banco. Com este cast, MySQL e SQLite
 * guardam e comparam igual.
 *
 * @implements CastsAttributes<CarbonImmutable, DateTimeInterface|string>
 */
class Data implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?CarbonImmutable
    {
        return $value === null ? null : CarbonImmutable::parse($value)->startOfDay();
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return ($value instanceof DateTimeInterface ? CarbonImmutable::instance($value) : CarbonImmutable::parse($value))->toDateString();
    }
}
