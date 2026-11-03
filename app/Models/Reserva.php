<?php

namespace App\Models;

use App\Enums\OrigemReserva;
use App\Enums\StatusReserva;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $aula_id
 * @property int $aluna_id
 * @property int|null $lote_id
 * @property StatusReserva $status
 * @property OrigemReserva $origem
 * @property CarbonImmutable|null $lembrete_enviado_em
 * @property CarbonImmutable|null $cancelada_em
 * @property CarbonImmutable|null $presenca_em
 * @property Aula $aula
 * @property User $aluna
 * @property LoteDeCredito|null $lote
 */
class Reserva extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => StatusReserva::class,
            'origem' => OrigemReserva::class,
            'lembrete_enviado_em' => 'immutable_datetime',
            'cancelada_em' => 'immutable_datetime',
            'presenca_em' => 'immutable_datetime',
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

    /** @return BelongsTo<LoteDeCredito, $this> */
    public function lote(): BelongsTo
    {
        return $this->belongsTo(LoteDeCredito::class, 'lote_id');
    }

    /** Até quando dá para cancelar recebendo a aula de volta. */
    public function prazoDeCancelamento(): CarbonImmutable
    {
        return $this->aula->inicio->subHours(config('talo.cancelamento.prazo_horas'));
    }

    public function dentroDoPrazo(): bool
    {
        return now()->lessThanOrEqualTo($this->prazoDeCancelamento());
    }
}
