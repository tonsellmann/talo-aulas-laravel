<?php

namespace App\Actions;

use App\Models\Cobranca;
use App\Models\EventoDeWebhook;
use App\Support\Pix\GatewayPix;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;

class ProcessarWebhookPix
{
    public function __construct(private readonly GatewayPix $gateway, private readonly ConfirmarPagamento $confirmar) {}

    /** @return array{0: int, 1: string} status HTTP e resultado */
    public function __invoke(Request $request): array
    {
        if (! $this->gateway->autentico($request)) {
            return [401, 'assinatura_invalida'];
        }

        $evento = $this->gateway->interpretar($request);

        if ($evento->id === '') {
            return [422, 'evento_sem_id'];
        }

        try {
            $registro = EventoDeWebhook::query()->create([
                'gateway' => $this->gateway->nome(),
                'evento_id' => $evento->id,
                'tipo' => $evento->tipo,
                'payload' => $request->json()->all(),
                'recebido_em' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Mesmo aviso de novo (o gateway reenvia quando não recebe resposta a tempo).
            return [200, 'duplicado'];
        }

        $resultado = 'ignorado';

        if ($evento->pagamentoConfirmado) {
            $cobranca = Cobranca::query()
                ->where('gateway', $this->gateway->nome())
                ->where('gateway_id', $evento->cobrancaGatewayId)
                ->first();

            $resultado = $cobranca
                ? ($this->confirmar)($cobranca, (int) $evento->valorCentavos, $evento->pagoEm ?? now())
                : 'cobranca_desconhecida';
        }

        $registro->update(['processado_em' => now(), 'resultado' => $resultado]);

        // Sempre 200 depois de registrado: erro de negócio não se resolve com o gateway reenviando.
        return [200, $resultado];
    }
}
