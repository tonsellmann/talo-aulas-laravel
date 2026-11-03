<?php

namespace App\Models;

use App\Enums\Papel;
use App\Enums\StatusMatricula;
use App\Enums\StatusReserva;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $telefone
 * @property Papel $papel
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'telefone', 'papel', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'papel' => Papel::class,
        ];
    }

    public function primeiroNome(): string
    {
        return explode(' ', $this->name)[0];
    }

    public function iniciais(): string
    {
        $partes = explode(' ', $this->name);

        return mb_strtoupper(mb_substr($partes[0], 0, 1).mb_substr(end($partes), 0, 1));
    }

    public function eh(Papel $papel): bool
    {
        return $this->papel === $papel;
    }

    /** @return HasMany<Reserva, $this> */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'aluna_id');
    }

    /** @return HasMany<LoteDeCredito, $this> */
    public function lotes(): HasMany
    {
        return $this->hasMany(LoteDeCredito::class, 'aluna_id');
    }

    /** @return HasMany<MovimentoDeCredito, $this> */
    public function movimentos(): HasMany
    {
        return $this->hasMany(MovimentoDeCredito::class, 'aluna_id');
    }

    /** @return HasMany<Matricula, $this> */
    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class, 'aluna_id');
    }

    /** @return HasOne<Matricula, $this> */
    public function matriculaAtual(): HasOne
    {
        return $this->hasOne(Matricula::class, 'aluna_id')
            ->whereIn('status', [StatusMatricula::Ativa, StatusMatricula::Pendente])
            ->latestOfMany();
    }

    /** @return HasMany<EntradaNaFila, $this> */
    public function filas(): HasMany
    {
        return $this->hasMany(EntradaNaFila::class, 'aluna_id');
    }

    /** @return HasMany<NotaDeSaude, $this> */
    public function notasDeSaude(): HasMany
    {
        return $this->hasMany(NotaDeSaude::class, 'aluna_id');
    }

    /** @return HasMany<Mensagem, $this> */
    public function mensagens(): HasMany
    {
        return $this->hasMany(Mensagem::class, 'aluna_id');
    }

    /** @return HasMany<Cobranca, $this> */
    public function cobrancas(): HasMany
    {
        return $this->hasMany(Cobranca::class, 'aluna_id');
    }

    /**
     * Reservas confirmadas de aulas que ainda vão acontecer, da mais próxima para a mais distante.
     *
     * @return HasMany<Reserva, $this>
     */
    public function proximasReservas(): HasMany
    {
        return $this->reservas()
            ->select('reservas.*')
            ->join('aulas', 'aulas.id', '=', 'reservas.aula_id')
            ->where('reservas.status', StatusReserva::Confirmada)
            ->where('aulas.inicio', '>', now())
            ->whereNull('aulas.cancelada_em')
            ->orderBy('aulas.inicio')
            ->with('aula.modalidade', 'aula.instrutora');
    }

    public function routeNotificationForWhatsApp(): ?string
    {
        return $this->telefone;
    }
}
