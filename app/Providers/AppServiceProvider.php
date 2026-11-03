<?php

namespace App\Providers;

use App\Support\Pix\Asaas;
use App\Support\Pix\GatewayDeDemonstracao;
use App\Support\Pix\GatewayPix;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(GatewayPix::class, fn () => match (config('talo.pix.gateway')) {
            'asaas' => new Asaas,
            default => new GatewayDeDemonstracao,
        });
    }

    public function boot(): void
    {
        // Datas imutáveis: somar um dia nunca altera a data original por engano.
        Date::use(CarbonImmutable::class);
        CarbonImmutable::setLocale('pt_BR');

        // Fora de produção, o Eloquent reclama de consulta escondida (N+1) e de atributo inexistente.
        // Nos testes isso derruba o teste; no resto, só vai para o log.
        Model::shouldBeStrict(! $this->app->isProduction());
        if (! $this->app->runningUnitTests()) {
            Model::handleLazyLoadingViolationUsing(fn ($model, string $relacao) => Log::warning('Consulta escondida: '.$model::class.'::'.$relacao));
        }
    }
}
