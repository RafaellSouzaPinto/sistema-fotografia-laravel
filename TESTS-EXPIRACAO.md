# TESTES — Feature de Expiração de Links

Criar `tests/Feature/ExpiracaoTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use App\Models\TrabalhoCliente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExpiracaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private Trabalho $trabalho;
    private Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->usuario = Usuario::create([
            'nome' => 'Silvia Souza',
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => bcrypt('123456'),
        ]);

        $this->trabalho = Trabalho::create([
            'titulo' => 'Casamento Teste',
            'data_trabalho' => '2026-06-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);

        $this->cliente = Cliente::create([
            'nome' => 'Ana Silva',
            'telefone' => '(11) 98765-4321',
        ]);
    }

    // ===================================================
    // TESTES DE LINK VÁLIDO (não expirado)
    // ===================================================

    public function test_link_valido_carrega_galeria(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->addDays(30),
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertStatus(200);
        $response->assertSee('Ana Silva');
        $response->assertSee('Casamento Teste');
    }

    public function test_link_valido_mostra_contador_regressivo(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->addDays(15),
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertStatus(200);
        $response->assertSee('Link disponível por');
    }

    public function test_link_sem_prazo_funciona_normalmente(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => null,
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertStatus(200);
        $response->assertSee('Ana Silva');
    }

    // ===================================================
    // TESTES DE LINK EXPIRADO
    // ===================================================

    public function test_link_expirado_mostra_pagina_de_expirado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subDays(1), // expirou ontem
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertStatus(200);
        $response->assertSee('Link expirado');
        $response->assertSee('Casamento Teste');
        $response->assertDontSee('Baixar todas as fotos');
    }

    public function test_link_expirado_atualiza_status_no_banco(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subHours(2),
            'status_link' => 'disponivel',
        ]);

        $this->get("/galeria/{$token}");

        $this->assertDatabaseHas('trabalho_cliente', [
            'token' => $token,
            'status_link' => 'expirado',
        ]);
    }

    public function test_link_expirado_mostra_botao_whatsapp(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subDays(5),
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertSee('wa.me');
        $response->assertSee('Falar com');
    }

    public function test_link_ja_marcado_como_expirado_mostra_pagina_expirado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subDays(10),
            'status_link' => 'expirado',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertSee('Link expirado');
    }

    // ===================================================
    // TESTES DE DOWNLOAD COM LINK EXPIRADO
    // ===================================================

    public function test_download_individual_bloqueado_se_expirado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subDays(1),
            'status_link' => 'disponivel',
        ]);

        $foto = Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'teste.jpg',
            'drive_arquivo_id' => 'fotos/1/teste.jpg',
            'tamanho_bytes' => 1000,
            'ordem' => 0,
        ]);

        $response = $this->get("/galeria/{$token}/foto/{$foto->id}");
        $response->assertStatus(403);
    }

    public function test_download_zip_bloqueado_se_expirado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subDays(1),
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}/download");
        $response->assertStatus(403);
    }

    public function test_download_individual_funciona_se_valido(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->addDays(30),
            'status_link' => 'disponivel',
        ]);

        $foto = Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'teste.jpg',
            'drive_arquivo_id' => 'fotos/1/teste.jpg',
            'tamanho_bytes' => 1000,
            'ordem' => 0,
        ]);

        // Criar arquivo fake para download funcionar
        \Storage::disk('public')->put('fotos/1/teste.jpg', 'conteudo fake');

        $response = $this->get("/galeria/{$token}/foto/{$foto->id}");
        $response->assertStatus(200);
        $response->assertDownload('teste.jpg');
    }

    // ===================================================
    // TESTES DO MODEL TrabalhoCliente
    // ===================================================

    public function test_metodo_esta_expirado_retorna_true_quando_passado(): void
    {
        $pivot = new TrabalhoCliente([
            'expira_em' => now()->subDays(1),
        ]);
        // Simular cast
        $pivot->expira_em = Carbon::parse($pivot->expira_em);

        $this->assertTrue($pivot->estaExpirado());
    }

    public function test_metodo_esta_expirado_retorna_false_quando_futuro(): void
    {
        $pivot = new TrabalhoCliente([
            'expira_em' => now()->addDays(10),
        ]);
        $pivot->expira_em = Carbon::parse($pivot->expira_em);

        $this->assertFalse($pivot->estaExpirado());
    }

    public function test_metodo_esta_expirado_retorna_false_quando_null(): void
    {
        $pivot = new TrabalhoCliente([
            'expira_em' => null,
        ]);

        $this->assertFalse($pivot->estaExpirado());
    }

    public function test_metodo_dias_restantes(): void
    {
        $pivot = new TrabalhoCliente([
            'expira_em' => now()->addDays(15),
        ]);
        $pivot->expira_em = Carbon::parse($pivot->expira_em);

        $this->assertEquals(15, $pivot->diasRestantes());
    }

    public function test_metodo_dias_restantes_zero_quando_expirado(): void
    {
        $pivot = new TrabalhoCliente([
            'expira_em' => now()->subDays(3),
        ]);
        $pivot->expira_em = Carbon::parse($pivot->expira_em);

        $this->assertEquals(0, $pivot->diasRestantes());
    }

    // ===================================================
    // TESTES DO DASHBOARD — FILTRO EXPIRADOS
    // ===================================================

    public function test_dashboard_mostra_filtro_expirados(): void
    {
        $response = $this->actingAs($this->usuario)->get('/admin/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Expirados');
    }

    public function test_dashboard_filtro_expirados_mostra_trabalhos_expirados(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => now()->subDays(5),
            'status_link' => 'expirado',
        ]);

        $response = $this->actingAs($this->usuario)->get('/admin/dashboard');
        $response->assertStatus(200);
        // O trabalho com link expirado deve existir na página
        $response->assertSee('Casamento Teste');
    }

    // ===================================================
    // TESTES DE CRIAÇÃO COM DIAS DE EXPIRAÇÃO
    // ===================================================

    public function test_vincular_cliente_com_expiracao_30_dias(): void
    {
        $token = Str::random(64);
        $expiraEm = now()->addDays(30);

        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => $expiraEm,
            'status_link' => 'disponivel',
        ]);

        $pivot = TrabalhoCliente::where('token', $token)->first();
        $this->assertNotNull($pivot->expira_em);
        $this->assertEquals('disponivel', $pivot->status_link);
        $this->assertTrue($pivot->expira_em->isFuture());
    }

    public function test_vincular_cliente_com_expiracao_7_dias(): void
    {
        $token = Str::random(64);
        $expiraEm = now()->addDays(7);

        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token,
            'expira_em' => $expiraEm,
            'status_link' => 'disponivel',
        ]);

        $pivot = TrabalhoCliente::where('token', $token)->first();
        $diasRestantes = (int) now()->diffInDays($pivot->expira_em, false);
        $this->assertLessThanOrEqual(7, $diasRestantes);
        $this->assertGreaterThanOrEqual(6, $diasRestantes);
    }

    // ===================================================
    // TESTE DE LIBERAR ESPAÇO
    // ===================================================

    public function test_liberar_espaco_deleta_fotos_do_banco(): void
    {
        Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'foto1.jpg',
            'drive_arquivo_id' => 'fotos/1/foto1.jpg',
            'tamanho_bytes' => 5000,
            'ordem' => 0,
        ]);

        $this->assertDatabaseHas('fotos', ['trabalho_id' => $this->trabalho->id]);

        // Simular liberar espaço
        $this->trabalho->fotos()->delete();
        $this->trabalho->update(['drive_pasta_id' => null]);

        $this->assertSoftDeleted('fotos', ['trabalho_id' => $this->trabalho->id]);
        $this->assertNull($this->trabalho->fresh()->drive_pasta_id);
    }

    // ===================================================
    // TESTE DE TOKEN INVÁLIDO
    // ===================================================

    public function test_token_invalido_retorna_404(): void
    {
        $response = $this->get('/galeria/tokenquenaoexiste123456');
        $response->assertStatus(404);
    }
}
```

---

## Rodar testes

```bash
php artisan test --filter=ExpiracaoTest
```

Todos os 22 testes devem passar. Se algum falhar, corrigir o código da feature (não o teste).

Depois rodar todos os testes do projeto para garantir que nada quebrou:

```bash
php artisan test
```
