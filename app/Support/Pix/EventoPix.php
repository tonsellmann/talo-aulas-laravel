<?php

namespace App\Support\Pix;

use Carbon\CarbonImmutable;

/** Aviso do gateway já traduzido para o que o sistema precisa saber. */
final class EventoPix
{
    public function __construct(
        public readonly string $id,
        public readonly string $tipo,
        public readonly bool $pagamentoConfirmado,
        public readonly ?string $cobrancaGatewayId,
        public readonly ?int $valorCentavos,
        public readonly ?CarbonImmutable $pagoEm,
    ) {}
}
