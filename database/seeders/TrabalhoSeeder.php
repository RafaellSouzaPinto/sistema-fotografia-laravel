<?php

namespace Database\Seeders;

use App\Models\Trabalho;
use Illuminate\Database\Seeder;

class TrabalhoSeeder extends Seeder
{
    public function run(): void
    {
        Trabalho::firstOrCreate(['titulo' => 'Casamento Ana e João'], [
            'data_trabalho' => '2026-03-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);
        Trabalho::firstOrCreate(['titulo' => 'Aniversário 15 anos Maria'], [
            'data_trabalho' => '2026-02-22',
            'tipo' => 'previa',
            'status' => 'publicado',
        ]);
        Trabalho::firstOrCreate(['titulo' => 'Ensaio Família Santos'], [
            'data_trabalho' => '2026-04-10',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);
    }
}
