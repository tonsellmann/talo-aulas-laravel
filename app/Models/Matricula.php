<?php

namespace App\Models;

use App\Casts\Data;
use App\Enums\StatusMatricula;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $aluna_id
 * @property StatusMatricula $status
 * @property bool $renovacao_automatica
 * @property CarbonImmutable $inicio
 * @property Plano $plano
 * @property Modalidade|null $modalidade
 * @property User $aluna
 */
class Matricula extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => StatusMatricula::class,
            'renovacao_automatica' => 'boolean',
            'inicio' => Data::class,
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function aluna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluna_id');
    }

    /** @return BelongsTo<Plano, $this> */
    public function plano(): BelongsTo
    {
        return $this->belongsTo(Plano::class);
    }

    /** @return BelongsTo<Modalidade, $this> */
    public function modalidade(): BelongsTo
    {
        return $this->belongsTo(Modalidade::class);
    }

    /** @return HasMany<Ciclo, $this> */
    public function ciclos(): HasMany
    {
        return $this->hasMany(Ciclo::class)->orderBy('inicio');
    }

    /** @return HasOne<Ciclo, $this> */
    public function ultimoCiclo(): HasOne
    {
        return $this->hasOne(Ciclo::class)->ofMany('inicio', 'max');
    }

    /** Ciclo que cobre a data (por padrão, hoje). */
    public function cicloEm(?CarbonImmutable $data = null): ?Ciclo
    {
        $dia = ($data ?? now())->toDateString();

        return $this->ciclos()->where('inicio', '<=', $dia)->where('fim', '>=', $dia)->first();
    }

    /**
     * Modalidades em que as aulas desta matrícula valem.
     *
     * @return list<string>|null nulo quando valem em todas
     */
    public function modalidadesPermitidas(): ?array
    {
        if ($this->plano->escolhe_modalidade && $this->modalidade) {
            return [$this->modalidade->slug];
        }

        return $this->plano->modalidades;
    }
}
