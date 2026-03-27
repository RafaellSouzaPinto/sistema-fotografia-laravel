<?php

namespace Database\Factories;

use App\Models\Foto;
use Illuminate\Database\Eloquent\Factories\Factory;

class FotoFactory extends Factory
{
    protected $model = Foto::class;

    public function definition(): array
    {
        return [
            'trabalho_id'     => \App\Models\Trabalho::factory(),
            'nome_arquivo'    => $this->faker->word() . '.jpg',
            'drive_arquivo_id' => $this->faker->uuid(),
            'drive_thumbnail' => null,
            'caminho_thumbnail' => null,
            'tamanho_bytes'   => $this->faker->numberBetween(100000, 5000000),
            'ordem'           => $this->faker->numberBetween(1, 100),
        ];
    }
}
