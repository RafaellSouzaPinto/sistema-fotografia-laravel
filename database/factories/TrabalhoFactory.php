<?php

namespace Database\Factories;

use App\Models\Trabalho;
use Illuminate\Database\Eloquent\Factories\Factory;

class TrabalhoFactory extends Factory
{
    protected $model = Trabalho::class;

    public function definition(): array
    {
        return [
            'titulo'       => $this->faker->sentence(3),
            'data_trabalho' => $this->faker->date(),
            'tipo'         => $this->faker->randomElement(['previa', 'completo']),
            'status'       => $this->faker->randomElement(['rascunho', 'publicado']),
            'drive_pasta_id' => null,
        ];
    }
}
