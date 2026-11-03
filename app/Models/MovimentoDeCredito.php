<?php

namespace App\Models;

use App\Enums\TipoMovimento;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property TipoMovimento $tipo
 * @property int $quantidade
 * @property string $descricao
 * @property CarbonImmutable $created_at
 * @property LoteDeCredito|null $lote
 */
class MovimentoDeCredito extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'movimentos_de_credito';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'tipo' => TipoMovimento::class,
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<LoteDeCredito, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteDeCredito::class, 'lote_id');
    }

    /** @return BelongsTo<User, $this> */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
