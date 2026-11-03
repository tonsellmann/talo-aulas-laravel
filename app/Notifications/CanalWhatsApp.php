<?php

namespace App\Notifications;

use App\Jobs\EnviarWhatsApp;
use App\Models\Mensagem;
use App\Models\User;
use Illuminate\Notifications\Notification;

class CanalWhatsApp
{
    public function send(User $aluna, Notification $aviso): void
    {
        if (! $aviso instanceof AvisoWhatsApp) {
            return;
        }

        $mensagem = $aviso->toWhatsApp($aluna);

        // Primeiro grava; o envio vai para a fila. Se a API do WhatsApp cair,
        // a mensagem continua na caixa de saída e o job tenta de novo.
        $registro = Mensagem::query()->create([
            'aluna_id' => $aluna->id,
            'canal' => 'whatsapp',
            'destino' => $aluna->routeNotificationForWhatsApp(),
            'modelo' => $mensagem->modelo,
            'texto' => $mensagem->texto,
            'link' => $mensagem->link,
            'rotulo_link' => $mensagem->rotuloLink,
            'status' => 'pendente',
            'created_at' => now(),
        ]);

        EnviarWhatsApp::dispatch($registro->id)->afterCommit();
    }
}
