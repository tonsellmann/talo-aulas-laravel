<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Aviso que chega pelo WhatsApp. Cada subclasse só escreve o texto;
 * guardar na caixa de saída e enviar fica com o canal.
 */
abstract class AvisoWhatsApp extends Notification
{
    /** @return list<class-string> */
    public function via(object $notifiable): array
    {
        return [CanalWhatsApp::class];
    }

    abstract public function toWhatsApp(User $aluna): MensagemWhatsApp;
}
