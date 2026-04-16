<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Livewire\Admin\AlterarSenha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

class AlterarSenhaTest extends TestCase
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

    public function test_alterar_senha_com_dados_corretos(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'nova123')
            ->set('confirmacaoSenha', 'nova123')
            ->call('salvar')
            ->assertHasNoErrors()
            ->assertDispatched('notify');

        $this->usuario->refresh();
        $this->assertTrue(Hash::check('nova123', $this->usuario->senha));
    }

    public function test_senha_antiga_nao_funciona_apos_troca(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'nova_senha_456')
            ->set('confirmacaoSenha', 'nova_senha_456')
            ->call('salvar');

        $this->usuario->refresh();
        $this->assertFalse(Hash::check('123456', $this->usuario->senha));
        $this->assertTrue(Hash::check('nova_senha_456', $this->usuario->senha));
    }

    public function test_campos_limpos_apos_salvar(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'nova123')
            ->set('confirmacaoSenha', 'nova123')
            ->call('salvar')
            ->assertSet('senhaAtual', '')
            ->assertSet('novaSenha', '')
            ->assertSet('confirmacaoSenha', '');
    }

    public function test_senha_atual_incorreta_gera_erro(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', 'errada999')
            ->set('novaSenha', 'nova123')
            ->set('confirmacaoSenha', 'nova123')
            ->call('salvar')
            ->assertHasErrors(['senhaAtual']);

        $this->usuario->refresh();
        $this->assertTrue(Hash::check('123456', $this->usuario->senha)); // não alterou
    }

    public function test_nova_senha_muito_curta_gera_erro(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'abc')
            ->set('confirmacaoSenha', 'abc')
            ->call('salvar')
            ->assertHasErrors(['novaSenha']);
    }

    public function test_confirmacao_diferente_gera_erro(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'nova123')
            ->set('confirmacaoSenha', 'diferente')
            ->call('salvar')
            ->assertHasErrors(['confirmacaoSenha']);
    }

    public function test_campos_em_branco_geram_erros(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->call('salvar')
            ->assertHasErrors(['senhaAtual', 'novaSenha', 'confirmacaoSenha']);
    }

    public function test_nova_senha_com_exatamente_6_caracteres_aceita(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'abc123')
            ->set('confirmacaoSenha', 'abc123')
            ->call('salvar')
            ->assertHasNoErrors();
    }

    public function test_nao_autenticado_redirecionado_do_perfil(): void
    {
        $this->get('/admin/perfil')->assertRedirect('/login');
    }

    public function test_autenticado_acessa_perfil(): void
    {
        $this->actingAs($this->usuario)
            ->get('/admin/perfil')
            ->assertStatus(200)
            ->assertSee('Alterar Senha');
    }

    public function test_verificacao_usa_hash_check_nao_auth_attempt(): void
    {
        // Verifica indiretamente: se usasse Auth::attempt(), falharia
        // porque Auth::attempt() espera coluna 'password', não 'senha'
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'nova123')
            ->set('confirmacaoSenha', 'nova123')
            ->call('salvar')
            ->assertHasNoErrors(); // se usasse Auth::attempt() quebraria aqui

        $this->usuario->refresh();
        $this->assertTrue(Hash::check('nova123', $this->usuario->senha));
    }

    public function test_senha_armazenada_como_hash_bcrypt(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(AlterarSenha::class)
            ->set('senhaAtual', '123456')
            ->set('novaSenha', 'nova123')
            ->set('confirmacaoSenha', 'nova123')
            ->call('salvar');

        $this->usuario->refresh();
        $this->assertNotEquals('nova123', $this->usuario->senha);
        $this->assertStringStartsWith('$2y$', $this->usuario->senha);
    }
}
