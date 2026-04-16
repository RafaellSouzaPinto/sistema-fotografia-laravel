<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TrabalhoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = Usuario::create([
            'nome' => 'Silvia Souza',
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => bcrypt('123456'),
        ]);
    }

    public function test_dashboard_carrega_autenticado(): void
    {
        $response = $this->actingAs($this->usuario)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Meus Trabalhos');
    }

    public function test_dashboard_sem_login_redireciona(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_criar_trabalho_pagina_carrega(): void
    {
        $response = $this->actingAs($this->usuario)->get('/admin/jobs/create');
        $response->assertStatus(200);
        $response->assertSee('Novo Trabalho');
    }

    public function test_trabalho_aparece_no_dashboard(): void
    {
        Trabalho::create([
            'titulo' => 'Casamento Ana e João',
            'data_trabalho' => '2026-03-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);

        $response = $this->actingAs($this->usuario)->get('/admin/dashboard');
        $response->assertSee('Casamento Ana e João');
    }

    public function test_excluir_trabalho_remove_do_banco(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Teste Deletar',
            'data_trabalho' => '2026-01-01',
            'tipo' => 'previa',
            'status' => 'rascunho',
        ]);

        $this->assertDatabaseHas('trabalhos', ['id' => $trabalho->id]);

        $trabalho->delete();

        $this->assertSoftDeleted('trabalhos', ['id' => $trabalho->id]);
    }
}
