<?php

namespace App\Models;

use App\Enums\ModoFila;
use App\Enums\StatusFila;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $aula_id
 * @property int $aluna_id
 * @property ModoFila $modo
 * @property StatusFila $status
 * @property CarbonImmutable|null $oferta_enviada_em
 * @property CarbonImmutable|null $oferta_expira_em
 * @property CarbonImmutable|null $resolvida_em
 * @property CarbonImmutable $created_at
 * @property Aula $aula
 * @property User $aluna
 */
class EntradaNaFila extends Model
{
    protected $table = 'fila_de_espera';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'modo' => ModoFila::class,
            'status' => StatusFila::class,
            'oferta_enviada_em' => 'immutable_datetime',
            'oferta_expira_em' => 'immutable_datetime',
            'resolvida_em' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<Aula, $this> */
    public function aula(): BelongsTo
    {
        return $this->belongsTo(Aula::class);
    }

    /** @return BelongsTo<User, $this> */
    public function aluna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluna_id');
    }

    /** Posição na fila, contando a partir de 1. */
    public function posicao(): int
    {
        return EntradaNaFila::query()
            ->where('aula_id', $this->aula_id)
            ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta])
            ->where(fn ($q) => $q->where('created_at', '<', $this->created_at)
                ->orWhere(fn ($q) => $q->where('created_at', $this->created_at)->where('id', '<', $this->id)))
            ->count() + 1;
    }

    public function ofertaValida(): bool
    {
        return $this->status === StatusFila::Oferta
            && $this->oferta_expira_em !== null
            && now()->lessThanOrEqualTo($this->oferta_expira_em);
    }
}
