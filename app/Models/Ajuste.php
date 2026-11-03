<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ajuste extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $primaryKey = 'chave';

    protected $keyType = 'string';

    protected $guarded = [];

    public static function ler(string $chave, ?string $padrao = null): ?string
    {
        return static::query()->find($chave)->valor ?? $padrao;
    }

    public static function gravar(string $chave, ?string $valor): void
    {
        static::query()->updateOrCreate(['chave' => $chave], ['valor' => $valor]);
    }
}
