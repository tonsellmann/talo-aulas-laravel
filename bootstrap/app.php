<?php

use App\Http\Middleware\AplicarRelogio;
use App\Http\Middleware\ExigePapel;
use App\Support\Demonstracao;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Só na demonstração: o navegador monta o estúdio ao abrir a página.
            // Fica fora do grupo "web" porque roda antes de existir a tabela de sessões.
            if (config('talo.demonstracao')) {
                Route::post('/_demonstracao/montar', function () {
                    Demonstracao::montar();
                    foreach (['config:cache', 'route:cache', 'view:cache'] as $comando) {
                        Artisan::call($comando);
                    }

                    return response()->json(['agora' => now()->toIso8601String()]);
                });
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['papel' => ExigePapel::class]);
        $middleware->web(prepend: [AplicarRelogio::class]);
        // O gateway não tem o token CSRF; a autenticidade vem da assinatura do aviso.
        $middleware->validateCsrfTokens(except: ['webhooks/*']);
        $middleware->redirectGuestsTo(fn () => route('entrar'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
