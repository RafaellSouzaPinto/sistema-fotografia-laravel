<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Cliente;
use App\Models\Trabalho;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ClienteTest extends TestCase
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

    public function test_criar_cliente(): void
    {
        $cliente = Cliente::create([
            'nome' => 'Ana Silva',
            'telefone' => '(11) 98765-4321',
        ]);

        $this->assertDatabaseHas('clientes', [
            'nome' => 'Ana Silva',
            'telefone' => '(11) 98765-4321',
        ]);
    }

    public function test_vincular_cliente_a_trabalho_gera_token(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Casamento Teste',
            'data_trabalho' => '2026-06-15',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);

        $cliente = Cliente::create([
            'nome' => 'João Oliveira',
            'telefone' => '(11) 91234-5678',
        ]);

        $token = Str::random(64);
        $trabalho->clientes()->attach($cliente->id, ['token' => $token]);

        $this->assertDatabaseHas('trabalho_cliente', [
            'trabalho_id' => $trabalho->id,
            'cliente_id' => $cliente->id,
            'token' => $token,
        ]);
    }

    public function test_cliente_reutilizavel_em_varios_trabalhos(): void
    {
        $cliente = Cliente::create(['nome' => 'Maria', 'telefone' => '(21) 99999-0000']);
        $trabalho1 = Trabalho::create(['titulo' => 'Trabalho 1', 'data_trabalho' => '2026-01-01', 'tipo' => 'previa', 'status' => 'rascunho']);
        $trabalho2 = Trabalho::create(['titulo' => 'Trabalho 2', 'data_trabalho' => '2026-02-01', 'tipo' => 'completo', 'status' => 'rascunho']);

        $trabalho1->clientes()->attach($cliente->id, ['token' => Str::random(64)]);
        $trabalho2->clientes()->attach($cliente->id, ['token' => Str::random(64)]);

        $this->assertEquals(2, $cliente->trabalhos()->count());
    }

    public function test_listagem_clientes_carrega(): void
    {
        $response = $this->actingAs($this->usuario)->get('/admin/clients');
        $response->assertStatus(200);
    }
}
