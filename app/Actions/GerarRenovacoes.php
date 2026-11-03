<?php

namespace App\Actions;

use App\Enums\StatusCobranca;
use App\Enums\StatusMatricula;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Notifications\CobrancaGerada;
use App\Support\Pix\GatewayPix;

/**
 * Alguns dias antes do fim do ciclo, gera o Pix do próximo. Também marca as
 * matrículas cujo ciclo acabou sem pagamento e os Pix que venceram.
 */
class GerarRenovacoes
{
    public function __construct(private readonly GatewayPix $gateway) {}

    public function __invoke(): int
    {
        Cobranca::query()->where('status', StatusCobranca::Pendente)->where('vence_em', '<', now())
            ->update(['status' => StatusCobranca::Expirada]);

        $geradas = 0;

        $matriculas = Matricula::query()
            ->whereIn('status', [StatusMatricula::Ativa, StatusMatricula::Pendente])
            ->where('renovacao_automatica', true)
            ->with('plano', 'aluna', 'ultimoCiclo')
            ->get();

        foreach ($matriculas as $matricula) {
            $ultimo = $matricula->ultimoCiclo;

            if ($ultimo === null) {
                continue;
            }

            if ($ultimo->fim->lessThan(now()->startOfDay())) {
                $matricula->update(['status' => StatusMatricula::Pendente]);
            }

            if ($ultimo->fim->subDays(config('talo.renovacao.dias_antes'))->greaterThan(now())) {
                continue;
            }

            $proximoInicio = $ultimo->fim->addDay();
            $existente = Cobranca::query()->where('matricula_id', $matricula->id)
                ->where('ciclo_inicio', $proximoInicio->toDateString())->first();

            // Pix vencido sem pagamento: gera outro para o mesmo ciclo.
            if ($existente && $existente->status !== StatusCobranca::Expirada) {
                continue;
            }

            $cobranca = $existente ?? new Cobranca([
                'aluna_id' => $matricula->aluna_id,
                'matricula_id' => $matricula->id,
                'ciclo_inicio' => $proximoInicio,
            ]);

            $cobranca->fill([
                'descricao' => $matricula->plano->nome.': '.$proximoInicio->format('d/m').' a '.$proximoInicio->addDays($matricula->plano->ciclo_dias - 1)->format('d/m'),
                'valor_centavos' => $matricula->plano->preco_centavos,
                'status' => StatusCobranca::Pendente,
                'gateway' => $this->gateway->nome(),
                'vence_em' => now(),
            ])->save();

            $pix = $this->gateway->criarCobranca($cobranca);
            $cobranca->update([
                'gateway_id' => $pix->gatewayId,
                'pix_copia_e_cola' => $pix->copiaECola,
                'vence_em' => $pix->venceEm,
            ]);

            $matricula->aluna->notify(new CobrancaGerada($cobranca));
            $geradas++;
        }

        return $geradas;
    }
}
