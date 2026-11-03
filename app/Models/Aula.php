<?php

namespace App\Models;

use App\Enums\StatusFila;
use App\Enums\StatusReserva;
use Carbon\CarbonImmutable;
use Database\Factories\AulaFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $modalidade_id
 * @property int $instrutora_id
 * @property CarbonImmutable $inicio
 * @property CarbonImmutable $fim
 * @property string $nivel
 * @property int $capacidade
 * @property int $ocupadas
 * @property CarbonImmutable|null $cancelada_em
 * @property CarbonImmutable|null $fechada_em
 * @property Modalidade $modalidade
 * @property User $instrutora
 */
class Aula extends Model
{
    /** @use HasFactory<AulaFactory> */
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'inicio' => 'immutable_datetime',
            'fim' => 'immutable_datetime',
            'cancelada_em' => 'immutable_datetime',
            'fechada_em' => 'immutable_datetime',
        ];
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

    /** @return BelongsTo<Turma, $this> */
    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class);
    }

    /** @return HasMany<Reserva, $this> */
    public function reservasAtivas(): HasMany
    {
        return $this->hasMany(Reserva::class)->whereIn('status', [
            StatusReserva::Confirmada, StatusReserva::Presente, StatusReserva::Falta,
        ]);
    }

    /** @return HasMany<EntradaNaFila, $this> */
    public function fila(): HasMany
    {
        return $this->hasMany(EntradaNaFila::class)
            ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta])
            ->orderBy('created_at')
            ->orderBy('id');
    }

    public function vagas(): int
    {
        return max(0, $this->capacidade - $this->ocupadas);
    }

    public function lotada(): bool
    {
        return $this->ocupadas >= $this->capacidade;
    }

    public function cancelada(): bool
    {
        return $this->cancelada_em !== null;
    }

    public function comecou(): bool
    {
        return now()->greaterThanOrEqualTo($this->inicio);
    }

    /** Ex.: "Aparelhos, terça 06:30". */
    public function descricao(): string
    {
        return $this->modalidade->nome.', '.$this->inicio->translatedFormat('l').' '.$this->inicio->format('H:i');
    }

    /** Ex.: "terça, 11/11 às 06:30". */
    public function quando(): string
    {
        return $this->inicio->translatedFormat('l, d/m').' às '.$this->inicio->format('H:i');
    }

    /** @param Builder<Aula> $query */
    public function scopeFuturas(Builder $query): void
    {
        $query->where('inicio', '>', now())->whereNull('cancelada_em');
    }

    /** @param Builder<Aula> $query */
    public function scopeNoDia(Builder $query, CarbonImmutable $dia): void
    {
        $query->whereBetween('inicio', [$dia->startOfDay(), $dia->endOfDay()]);
    }
}
