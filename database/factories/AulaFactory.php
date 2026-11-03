<?php

namespace Database\Factories;

use App\Models\Aula;
use App\Models\Modalidade;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aula>
 */
class AulaFactory extends Factory
{
    public function definition(): array
    {
        $inicio = now()->addDay()->setTime(17, 30);

        return [
            'modalidade_id' => Modalidade::query()->firstOrCreate(
                ['slug' => 'aparelhos'],
                ['nome' => 'Aparelhos', 'capacidade' => 4, 'cor' => '#4a3560'],
            )->id,
            'instrutora_id' => User::factory()->instrutora(),
            'inicio' => $inicio,
            'fim' => $inicio->addMinutes(50),
            'nivel' => 'Iniciante',
            'capacidade' => 4,
            'ocupadas' => 0,
        ];
    }
}
