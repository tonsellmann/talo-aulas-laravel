<?php

namespace App\Support\Pix;

use App\Models\Cobranca;
use Illuminate\Http\Request;

interface GatewayPix
{
    public function nome(): string;

    public function criarCobranca(Cobranca $cobranca): DadosDoPix;

    /** O aviso veio mesmo do gateway? */
    public function autentico(Request $request): bool;

    public function interpretar(Request $request): EventoPix;
}
