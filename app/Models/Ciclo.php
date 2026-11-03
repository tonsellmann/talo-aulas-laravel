<?php

namespace App\Models;

use App\Casts\Data;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $matricula_id
 * @property CarbonImmutable $inicio
 * @property CarbonImmutable $fim
 * @property int $reposicoes_disponiveis
 * @property string $status
 * @property Matricula $matricula
 */
class Ciclo extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'inicio' => Data::class,
            'fim' => Data::class,
        ];
    }

    /** @return BelongsTo<Matricula, $this> */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }

    /** @return HasOne<LoteDeCredito, $this> */
    public function lote(): HasOne
    {
        return $this->hasOne(LoteDeCredito::class)->where('origem', 'ciclo');
    }

    public function periodo(): string
    {
        return $this->inicio->format('d/m').' a '.$this->fim->format('d/m');
    }
}
