<?php

namespace App\Actions;

use App\Enums\OrigemLote;
use App\Enums\StatusCobranca;
use App\Enums\StatusMatricula;
use App\Models\Ciclo;
use App\Models\Cobranca;
use App\Notifications\PagamentoConfirmado;
use App\Support\Carteira;
use App\Support\Dinheiro;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ConfirmarPagamento
{
    /**
     * @param  bool  $aceitarDiferenca  a recepção conferiu um Pix com valor diferente e libera o ciclo mesmo assim
     * @return string o que aconteceu, para o registro do webhook
     */
    public function __invoke(Cobranca $cobranca, int $valorPago, CarbonImmutable $pagoEm, bool $aceitarDiferenca = false): string
    {
        $resultado = DB::transaction(function () use ($cobranca, $valorPago, $pagoEm, $aceitarDiferenca) {
            $cobranca = Cobranca::query()->whereKey($cobranca->id)->lockForUpdate()->firstOrFail();

            if ($cobranca->status === StatusCobranca::Paga) {
                return ['ja_paga', null, null];
            }

            if ($valorPago !== $cobranca->valor_centavos && ! $aceitarDiferenca) {
                $cobranca->update(['status' => StatusCobranca::Divergente, 'valor_pago_centavos' => $valorPago, 'paga_em' => $pagoEm]);

                return ['valor_divergente: pago '.Dinheiro::formatar($valorPago), null, null];
            }

            $cobranca->update(['status' => StatusCobranca::Paga, 'valor_pago_centavos' => $valorPago, 'paga_em' => $pagoEm]);

            $matricula = $cobranca->matricula()->with('plano', 'modalidade', 'aluna')->firstOrFail();
            $plano = $matricula->plano;
            $inicio = $cobranca->ciclo_inicio ?? now()->startOfDay();
            $fim = $inicio->addDays($plano->ciclo_dias - 1);

            $ciclo = Ciclo::query()->create([
                'matricula_id' => $matricula->id,
                'inicio' => $inicio,
                'fim' => $fim,
                'reposicoes_disponiveis' => $plano->reposicoes_por_ciclo,
                'status' => 'pago',
            ]);

            $lote = Carteira::de($matricula->aluna)->creditar(
                quantidade: $plano->aulas_por_ciclo,
                origem: OrigemLote::Ciclo,
                validoDe: $inicio->startOfDay(),
                validoAte: $fim->endOfDay(),
                modalidades: $matricula->modalidadesPermitidas(),
                descricao: "{$plano->nome}: {$plano->aulas_por_ciclo} aulas de {$inicio->format('d/m')} a {$fim->format('d/m')}",
                ciclo: $ciclo,
            );

            $matricula->update(['status' => StatusMatricula::Ativa]);

            return ['ciclo_criado', $cobranca, $lote];
        });

        [$descricao, $paga, $lote] = $resultado;

        if ($paga && $lote) {
            $paga->aluna->notify(new PagamentoConfirmado($paga, $lote));
        }

        return $descricao;
    }
}
