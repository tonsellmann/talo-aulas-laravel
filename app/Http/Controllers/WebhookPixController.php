<?php

namespace App\Http\Controllers;

use App\Actions\ProcessarWebhookPix;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookPixController
{
    public function __invoke(Request $request, ProcessarWebhookPix $processar): JsonResponse
    {
        [$status, $resultado] = $processar($request);

        return response()->json(['resultado' => $resultado], $status);
    }
}
