<?php

namespace App\Support;

use App\Enums\OrigemLote;
use App\Enums\TipoMovimento;
use App\Exceptions\RegraDeNegocio;
use App\Models\Aula;
use App\Models\Ciclo;
use App\Models\LoteDeCredito;
use App\Models\MovimentoDeCredito;
use App\Models\Reserva;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * As aulas de uma aluna. Todo crédito e débito passa por aqui e deixa um
 * movimento no extrato; o saldo do lote nunca é editado à mão.
 */
final class Carteira
{
    private function __construct(private readonly User $aluna) {}

    public static function de(User $aluna): self
    {
        return new self($aluna);
    }

    /**
     * Lote que paga a aula: o que vence primeiro entre os que valem na data e
     * na modalidade. Assim a aluna nunca perde uma aula que podia ter usado.
     */
    public function loteParaAula(Aula $aula, bool $travar = false): ?LoteDeCredito
    {
        $query = $this->aluna->lotes()
            ->usaveisEm($aula->inicio)
            ->orderBy('valido_ate')
            ->orderBy('id');

        if ($travar) {
            $query->lockForUpdate();
        }

        return $query->get()->first(fn (LoteDeCredito $lote) => $lote->valeParaModalidade($aula->modalidade->slug));
    }

    public function debitar(LoteDeCredito $lote, Reserva $reserva, string $descricao, ?User $por = null): void
    {
        // Só desconta se ainda houver saldo no momento da escrita. Se duas reservas
        // disputarem a última aula do lote, uma delas não altera nenhuma linha.
        $alteradas = LoteDeCredito::query()->whereKey($lote->id)->where('saldo', '>', 0)->decrement('saldo');

        if ($alteradas !== 1) {
            throw RegraDeNegocio::semCredito($reserva->aula);
        }

        $this->movimento(TipoMovimento::Reserva, -1, $descricao, $lote->id, $reserva, $por);
    }

    public function estornar(Reserva $reserva, string $descricao, ?User $por = null): void
    {
        if ($reserva->lote_id === null) {
            return;
        }

        LoteDeCredito::query()->whereKey($reserva->lote_id)->increment('saldo');
        $this->movimento(TipoMovimento::Estorno, 1, $descricao, $reserva->lote_id, $reserva, $por);
    }

    /** Linha no extrato sem mexer no saldo: falta, cancelamento tardio. */
    public function registrar(string $descricao, ?Reserva $reserva = null, ?User $por = null): void
    {
        $this->movimento(TipoMovimento::Registro, 0, $descricao, $reserva?->lote_id, $reserva, $por);
    }

    /**
     * @param  list<string>|null  $modalidades
     */
    public function creditar(
        int $quantidade,
        OrigemLote $origem,
        CarbonImmutable $validoDe,
        CarbonImmutable $validoAte,
        ?array $modalidades,
        string $descricao,
        ?Ciclo $ciclo = null,
        ?User $por = null,
        ?string $motivo = null,
    ): LoteDeCredito {
        $lote = $this->aluna->lotes()->create([
            'ciclo_id' => $ciclo?->id,
            'origem' => $origem,
            'quantidade' => $quantidade,
            'saldo' => $quantidade,
            'modalidades' => $modalidades,
            'valido_de' => $validoDe,
            'valido_ate' => $validoAte,
            'motivo' => $motivo,
            'criado_por' => $por?->id,
        ]);

        $this->movimento(TipoMovimento::Credito, $quantidade, $descricao, $lote->id, null, $por);

        return $lote;
    }

    public function expirar(LoteDeCredito $lote): void
    {
        $sobra = $lote->saldo;
        $lote->update(['saldo' => 0, 'expirado_em' => now()]);

        if ($sobra > 0) {
            $this->movimento(
                TipoMovimento::Expiracao,
                -$sobra,
                ($sobra === 1 ? '1 aula venceu' : "{$sobra} aulas venceram").' sem uso ('.$lote->origem->rotulo().', válido até '.$lote->valido_ate->format('d/m').')',
                $lote->id,
            );
        }
    }

    public function ajustar(LoteDeCredito $lote, int $quantidade, string $motivo, User $por): void
    {
        if ($lote->saldo + $quantidade < 0) {
            throw new RegraDeNegocio('O lote não tem aulas suficientes para esse ajuste.');
        }

        $lote->increment('saldo', $quantidade);
        $this->movimento(TipoMovimento::Ajuste, $quantidade, 'Ajuste da recepção: '.$motivo, $lote->id, null, $por);
    }

    /** Aulas disponíveis agora, somando todos os lotes válidos. */
    public function saldo(): int
    {
        return (int) $this->aluna->lotes()
            ->where('saldo', '>', 0)
            ->whereNull('expirado_em')
            ->where('valido_ate', '>=', now())
            ->sum('saldo');
    }

    /**
     * Lotes com saldo, do que vence primeiro para o último.
     *
     * @return Collection<int, LoteDeCredito>
     */
    public function lotesAtivos(): Collection
    {
        return $this->aluna->lotes()
            ->where('saldo', '>', 0)
            ->whereNull('expirado_em')
            ->where('valido_ate', '>=', now())
            ->orderBy('valido_ate')
            ->get();
    }

    private function movimento(TipoMovimento $tipo, int $quantidade, string $descricao, ?int $loteId, ?Reserva $reserva = null, ?User $por = null): void
    {
        MovimentoDeCredito::query()->create([
            'aluna_id' => $this->aluna->id,
            'lote_id' => $loteId,
            'reserva_id' => $reserva?->id,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'descricao' => $descricao,
            'criado_por' => $por?->id,
            'created_at' => now(),
        ]);
    }
}
