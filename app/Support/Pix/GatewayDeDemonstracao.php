<?php

namespace App\Support\Pix;

use App\Models\Cobranca;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Gateway de mentira para a demonstração e os testes. Gera um BR Code válido
 * (com CRC certo) e assina os avisos com HMAC, como os gateways de verdade.
 */
class GatewayDeDemonstracao implements GatewayPix
{
    public function nome(): string
    {
        return 'demonstracao';
    }

    public function criarCobranca(Cobranca $cobranca): DadosDoPix
    {
        $id = 'demo_'.Str::lower((string) Str::ulid());

        return new DadosDoPix(
            gatewayId: $id,
            copiaECola: BrCode::gerar(
                config('talo.estudio.chave_pix'),
                $cobranca->valor_centavos,
                config('talo.estudio.nome'),
                config('talo.estudio.cidade'),
                'TALO'.$cobranca->id,
            ),
            venceEm: now()->addHours(config('talo.renovacao.pix_validade_horas')),
        );
    }

    public function autentico(Request $request): bool
    {
        $assinatura = (string) $request->header('X-Talo-Assinatura');

        return $assinatura !== '' && hash_equals(self::assinar($request->getContent()), $assinatura);
    }

    public function interpretar(Request $request): EventoPix
    {
        $dados = $request->json()->all();

        return new EventoPix(
            id: (string) ($dados['id'] ?? ''),
            tipo: (string) ($dados['evento'] ?? ''),
            pagamentoConfirmado: ($dados['evento'] ?? null) === 'PAGAMENTO_CONFIRMADO',
            cobrancaGatewayId: $dados['cobranca'] ?? null,
            valorCentavos: isset($dados['valor_centavos']) ? (int) $dados['valor_centavos'] : null,
            pagoEm: isset($dados['pago_em']) ? CarbonImmutable::parse($dados['pago_em']) : null,
        );
    }

    public static function assinar(string $corpo): string
    {
        return hash_hmac('sha256', $corpo, (string) config('talo.pix.demonstracao.segredo'));
    }

    /**
     * Corpo de um aviso de pagamento, igual ao que o gateway mandaria.
     *
     * @return array<string, mixed>
     */
    public static function avisoDePagamento(Cobranca $cobranca, ?int $valorCentavos = null): array
    {
        return [
            'id' => 'evt_'.Str::lower((string) Str::ulid()),
            'evento' => 'PAGAMENTO_CONFIRMADO',
            'cobranca' => $cobranca->gateway_id,
            'valor_centavos' => $valorCentavos ?? $cobranca->valor_centavos,
            'pago_em' => now()->toIso8601String(),
        ];
    }
}
