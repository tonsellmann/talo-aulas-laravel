<?php

namespace App\Support\Pix;

use App\Models\Cobranca;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Integração com o Asaas: cobrança Pix e webhook de pagamento. */
class Asaas implements GatewayPix
{
    public function nome(): string
    {
        return 'asaas';
    }

    public function criarCobranca(Cobranca $cobranca): DadosDoPix
    {
        $cliente = $this->cliente($cobranca);
        $vencimento = now()->addHours(config('talo.renovacao.pix_validade_horas'));

        $pagamento = $this->http()->post('/payments', [
            'customer' => $cliente,
            'billingType' => 'PIX',
            'value' => $cobranca->valor_centavos / 100,
            'dueDate' => $vencimento->toDateString(),
            'description' => $cobranca->descricao,
            'externalReference' => (string) $cobranca->id,
        ])->throw()->json();

        $qr = $this->http()->get("/payments/{$pagamento['id']}/pixQrCode")->throw()->json();

        return new DadosDoPix($pagamento['id'], $qr['payload'], $vencimento);
    }

    public function autentico(Request $request): bool
    {
        $esperado = (string) config('talo.pix.asaas.token_webhook');

        return $esperado !== '' && hash_equals($esperado, (string) $request->header('asaas-access-token'));
    }

    public function interpretar(Request $request): EventoPix
    {
        $dados = $request->json()->all();
        $pagamento = $dados['payment'] ?? [];
        $evento = (string) ($dados['event'] ?? '');

        return new EventoPix(
            id: (string) ($dados['id'] ?? $evento.':'.($pagamento['id'] ?? '')),
            tipo: $evento,
            pagamentoConfirmado: in_array($evento, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true),
            cobrancaGatewayId: $pagamento['id'] ?? null,
            valorCentavos: isset($pagamento['value']) ? (int) round($pagamento['value'] * 100) : null,
            pagoEm: isset($pagamento['paymentDate']) ? CarbonImmutable::parse($pagamento['paymentDate']) : null,
        );
    }

    private function cliente(Cobranca $cobranca): string
    {
        $aluna = $cobranca->aluna;
        $existente = $this->http()->get('/customers', ['externalReference' => (string) $aluna->id])->throw()->json('data.0.id');

        return $existente ?? $this->http()->post('/customers', [
            'name' => $aluna->name,
            'email' => $aluna->email,
            'mobilePhone' => $aluna->telefone,
            'externalReference' => (string) $aluna->id,
        ])->throw()->json('id');
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl(config('talo.pix.asaas.url'))
            ->withHeaders(['access_token' => (string) config('talo.pix.asaas.chave')])
            ->acceptJson()
            ->timeout(10)
            ->retry(2, 500, throw: false);
    }
}
