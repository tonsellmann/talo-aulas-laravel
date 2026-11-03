<?php

namespace App\Models;

use App\Casts\Data;
use App\Enums\StatusCobranca;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $aluna_id
 * @property int|null $matricula_id
 * @property CarbonImmutable|null $ciclo_inicio
 * @property string $descricao
 * @property int $valor_centavos
 * @property StatusCobranca $status
 * @property string $gateway
 * @property string|null $gateway_id
 * @property string|null $pix_copia_e_cola
 * @property CarbonImmutable $vence_em
 * @property CarbonImmutable|null $paga_em
 * @property int|null $valor_pago_centavos
 * @property User $aluna
 * @property Matricula|null $matricula
 */
class Cobranca extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => StatusCobranca::class,
            'ciclo_inicio' => Data::class,
            'vence_em' => 'immutable_datetime',
            'paga_em' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function aluna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluna_id');
    }

    /** @return BelongsTo<Matricula, $this> */
    public function matricula(): BelongsTo
    {
        return $this->belongsTo(Matricula::class);
    }
}
