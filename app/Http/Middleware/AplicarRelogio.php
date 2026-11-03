<?php

namespace App\Http\Middleware;

use App\Support\Relogio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AplicarRelogio
{
    public function handle(Request $request, Closure $next): Response
    {
        Relogio::aplicar();

        return $next($request);
    }
}
