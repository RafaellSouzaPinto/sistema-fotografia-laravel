<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use App\Models\TrabalhoCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class GaleriaTest extends TestCase
{
    use RefreshDatabase;

    private string $token;
    private Trabalho $trabalho;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trabalho = Trabalho::create([
            'titulo' => 'Casamento Ana e João',
            'data_trabalho' => '2026-03-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);

        $this->cliente = Cliente::create([
            'nome' => 'Ana Silva',
            'telefone' => '(11) 98765-4321',
        ]);

        $this->token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, ['token' => $this->token]);
    }

    public function test_galeria_publica_carrega_com_token_valido(): void
    {
        $response = $this->get("/galeria/{$this->token}");
        $response->assertStatus(200);
        $response->assertSee('Olá, Ana Silva');
        $response->assertSee('Casamento Ana e João');
    }

    public function test_galeria_token_invalido_retorna_404(): void
    {
        $response = $this->get('/galeria/tokeninvalidoquenaoexiste123');
        $response->assertStatus(404);
    }

    public function test_galeria_nao_exige_login(): void
    {
        $response = $this->get("/galeria/{$this->token}");
        $response->assertStatus(200);
        $this->assertGuest();
    }

    public function test_galeria_mostra_botao_baixar_todas_para_completo(): void
    {
        $response = $this->get("/galeria/{$this->token}");
        $response->assertSee('Baixar todas as fotos');
    }

    public function test_galeria_previa_nao_mostra_baixar_todas(): void
    {
        $trabPrevia = Trabalho::create([
            'titulo' => 'Prévia Teste',
            'data_trabalho' => '2026-01-01',
            'tipo' => 'previa',
            'status' => 'publicado',
        ]);

        $tokenPrevia = Str::random(64);
        $trabPrevia->clientes()->attach($this->cliente->id, ['token' => $tokenPrevia]);

        $response = $this->get("/galeria/{$tokenPrevia}");
        $response->assertStatus(200);
        $response->assertDontSee('Baixar todas as fotos');
    }

    public function test_galeria_mostra_nome_cliente_correto(): void
    {
        $response = $this->get("/galeria/{$this->token}");
        $response->assertSee('Ana Silva');
    }

    public function test_galeria_mostra_data_formatada(): void
    {
        $response = $this->get("/galeria/{$this->token}");
        // Deve mostrar data em português: "15 de março de 2026"
        $response->assertSee('2026');
    }
}
