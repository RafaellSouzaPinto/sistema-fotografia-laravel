<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Usuario::create([
            'nome' => 'Silvia Souza',
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => bcrypt('123456'),
        ]);
    }

    public function test_tela_login_carrega(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Silvia Souza');
        $response->assertSee('Entrar');
    }

    public function test_login_com_credenciais_corretas(): void
    {
        $response = $this->post('/login', [
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => '123456',
        ]);
        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_com_senha_errada(): void
    {
        $response = $this->post('/login', [
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => 'senhaerrada',
        ]);
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_login_com_email_inexistente(): void
    {
        $response = $this->post('/login', [
            'email' => 'naoexiste@gmail.com',
            'senha' => '123456',
        ]);
        $response->assertSessionHasErrors();
        $this->assertGuest();
    }

    public function test_acesso_admin_sem_login_redireciona(): void
    {
        $response = $this->get('/admin/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_logout_funciona(): void
    {
        $usuario = Usuario::first();
        $this->actingAs($usuario);

        $response = $this->post('/logout');
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_rota_raiz_carrega_home_publica(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }
}
