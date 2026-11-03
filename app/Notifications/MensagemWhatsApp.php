<?php

namespace App\Notifications;

final class MensagemWhatsApp
{
    public function __construct(
        public readonly string $modelo,
        public readonly string $texto,
        public readonly ?string $link = null,
        public readonly ?string $rotuloLink = null,
    ) {}
}
