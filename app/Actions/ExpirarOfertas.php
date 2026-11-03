<?php

namespace App\Actions;

use App\Enums\StatusFila;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Notifications\OfertaExpirou;

/**
 * Varredura: ofertas vencidas saem da fila, e toda aula futura com vaga livre
 * e gente esperando passa pela fila de novo.
 */
class ExpirarOfertas
{
    public function __construct(private readonly PromoverFila $promoverFila) {}

    public function __invoke(): int
    {
        $vencidas = EntradaNaFila::query()
            ->where('status', StatusFila::Oferta)
            ->where('oferta_expira_em', '<', now())
            ->with('aula.modalidade', 'aluna')
            ->get();

        foreach ($vencidas as $entrada) {
            $entrada->update(['status' => StatusFila::Expirada, 'resolvida_em' => now()]);

            // Na corrida final (aula em cima da hora) não vale mandar mais uma mensagem.
            if (now()->diffInMinutes($entrada->aula->inicio, false) > config('talo.fila.corrida_final_minutos')) {
                $entrada->aluna->notify(new OfertaExpirou($entrada));
            }
        }

        Aula::query()
            ->whereNull('cancelada_em')
            ->where('inicio', '>', now()->subHours(3))
            ->whereHas('fila')
            ->get()
            ->each(fn (Aula $aula) => ($this->promoverFila)($aula));

        return $vencidas->count();
    }
}
