<?php

namespace App\Http\Middleware;

use App\Enums\Papel;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExigePapel
{
    public function handle(Request $request, Closure $next, string ...$papeis): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && in_array($usuario->papel, array_map(fn ($p) => Papel::from($p), $papeis), true), 403);

        return $next($request);
    }
}
