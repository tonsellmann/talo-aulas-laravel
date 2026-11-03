<?php

namespace Database\Factories;

use App\Enums\Papel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->firstNameFemale().' '.fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'telefone' => '55739'.fake()->numerify('########'),
            'papel' => Papel::Aluna,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function recepcao(): static
    {
        return $this->state(['papel' => Papel::Recepcao]);
    }

    public function instrutora(): static
    {
        return $this->state(['papel' => Papel::Instrutora]);
    }
}
