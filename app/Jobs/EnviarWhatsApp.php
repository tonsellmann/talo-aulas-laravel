<?php

namespace App\Jobs;

use App\Models\Mensagem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EnviarWhatsApp implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300, 900];

    public function __construct(public readonly int $mensagemId) {}

    public function handle(): void
    {
        $mensagem = Mensagem::query()->find($this->mensagemId);

        if ($mensagem === null || $mensagem->status === 'enviada') {
            return;
        }

        if (config('talo.demonstracao')) {
            // Na demonstração nada sai do navegador: a mensagem aparece na caixa de saída.
            $mensagem->update(['status' => 'enviada', 'enviada_em' => now()]);

            return;
        }

        $token = config('talo.whatsapp.token');

        if (! $token || ! $mensagem->destino) {
            Log::warning('WhatsApp sem token ou sem destino; mensagem fica pendente.', ['mensagem' => $mensagem->id]);

            return;
        }

        // Mensagens iniciadas pelo estúdio usam modelos aprovados na Meta,
        // com o mesmo nome guardado em "modelo".
        Http::withToken($token)
            ->post('https://graph.facebook.com/v21.0/'.config('talo.whatsapp.numero_id').'/messages', [
                'messaging_product' => 'whatsapp',
                'to' => $mensagem->destino,
                'type' => 'template',
                'template' => [
                    'name' => $mensagem->modelo,
                    'language' => ['code' => 'pt_BR'],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => $mensagem->texto]],
                    ]],
                ],
            ])
            ->throw();

        $mensagem->update(['status' => 'enviada', 'enviada_em' => now()]);
    }

    public function failed(): void
    {
        Mensagem::query()->whereKey($this->mensagemId)->update(['status' => 'falhou']);
    }
}
