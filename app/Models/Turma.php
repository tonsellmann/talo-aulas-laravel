<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $dia_semana
 * @property string $hora
 * @property int $duracao_minutos
 * @property string $nivel
 * @property int $capacidade
 * @property bool $ativa
 */
class Turma extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['ativa' => 'boolean'];
    }

    /** @return BelongsTo<Modalidade, $this> */
    public function modalidade(): BelongsTo
    {
        return $this->belongsTo(Modalidade::class);
    }

    /** @return BelongsTo<User, $this> */
    public function instrutora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instrutora_id');
    }

    /** @return HasMany<Aula, $this> */
    public function aulas(): HasMany
    {
        return $this->hasMany(Aula::class);
    }
}
