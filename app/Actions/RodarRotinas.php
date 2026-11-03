<?php

namespace App\Actions;

/**
 * Todas as varreduras, na ordem certa. Cada uma processa tudo o que venceu até
 * agora, então rodar de novo não repete nada. Em produção o agendador chama
 * cada uma no seu ritmo; na demonstração, o botão de avançar o relógio chama esta.
 */
class RodarRotinas
{
    /** @return array<string, int> */
    public function __invoke(): array
    {
        return [
            'aulas_geradas' => app(GerarAulas::class)(),
            'aulas_fechadas' => FecharAula::pendentes(),
            'ofertas_vencidas' => app(ExpirarOfertas::class)(),
            'lembretes' => app(EnviarLembretes::class)(),
            'lotes_vencidos' => app(ExpirarCreditos::class)(),
            'pix_gerados' => app(GerarRenovacoes::class)(),
        ];
    }
}
