<?php

namespace App\Models;

use App\Enums\OrigemLote;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $aluna_id
 * @property int|null $ciclo_id
 * @property OrigemLote $origem
 * @property int $quantidade
 * @property int $saldo
 * @property list<string>|null $modalidades
 * @property CarbonImmutable $valido_de
 * @property CarbonImmutable $valido_ate
 * @property CarbonImmutable|null $expirado_em
 * @property CarbonImmutable|null $aviso_vencimento_em
 * @property string|null $motivo
 */
class LoteDeCredito extends Model
{
    protected $table = 'lotes_de_credito';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'origem' => OrigemLote::class,
            'modalidades' => 'array',
            'valido_de' => 'immutable_datetime',
            'valido_ate' => 'immutable_datetime',
            'expirado_em' => 'immutable_datetime',
            'aviso_vencimento_em' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function aluna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluna_id');
    }

    /** @return BelongsTo<Ciclo, $this> */
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(Ciclo::class);
    }

    public function valeParaModalidade(string $slug): bool
    {
        return $this->modalidades === null || in_array($slug, $this->modalidades, true);
    }

    /**
     * Lotes com saldo que valem numa data.
     *
     * @param  Builder<LoteDeCredito>  $query
     */
    public function scopeUsaveisEm(Builder $query, CarbonImmutable $data): void
    {
        $query->where('saldo', '>', 0)
            ->whereNull('expirado_em')
            ->where('valido_de', '<=', $data)
            ->where('valido_ate', '>=', $data);
    }
}
