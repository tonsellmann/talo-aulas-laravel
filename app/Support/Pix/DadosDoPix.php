<?php

namespace App\Support\Pix;

use Carbon\CarbonImmutable;

final class DadosDoPix
{
    public function __construct(
        public readonly string $gatewayId,
        public readonly string $copiaECola,
        public readonly CarbonImmutable $venceEm,
    ) {}
}
