<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\TrabalhoCliente;
use App\Livewire\Admin\ClientManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

class RenovarLinkTest extends TestCase
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
            'data_trabalho' => '2026-03-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);
        $this->cliente = Cliente::create([
            'nome' => 'Ana Lima',
            'telefone' => '(11) 99999-0001',
        ]);
    }

    public function test_renovar_link_expirado_por_data(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'disponivel',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', 30)
            ->call('renovar')
            ->assertDispatched('notify');

        $vinculo->refresh();
        $this->assertEquals('disponivel', $vinculo->status_link);
        $this->assertTrue($vinculo->expira_em->isFuture());
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $vinculo->expira_em->timestamp, 60);
    }

    public function test_renovar_link_com_status_expirado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(10), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', 15)
            ->call('renovar');

        $vinculo->refresh();
        $this->assertEquals('disponivel', $vinculo->status_link);
        $this->assertFalse($vinculo->estaExpirado());
    }

    public function test_galeria_acessivel_apos_renovacao(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', 30)
            ->call('renovar');

        $this->get("/galeria/{$token}")
            ->assertStatus(200)
            ->assertSee($this->cliente->nome);
    }

    public function test_renovar_todos_os_prazos(): void
    {
        foreach ([7, 15, 30, 60, 90] as $dias) {
            $token = Str::random(64);
            $this->trabalho->clientes()->attach($this->cliente->id, [
                'token' => $token, 'expira_em' => now()->subDays(1), 'status_link' => 'expirado',
            ]);
            $vinculo = TrabalhoCliente::where('token', $token)->first();

            Livewire::actingAs($this->usuario)
                ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
                ->call('abrirRenovacao', $vinculo->id)
                ->set('diasRenovacao', $dias)
                ->call('renovar');

            $vinculo->refresh();
            $this->assertEquals('disponivel', $vinculo->status_link, "Falhou para $dias dias");
            $this->assertEqualsWithDelta(now()->addDays($dias)->timestamp, $vinculo->expira_em->timestamp, 60);

            $vinculo->forceDelete();
        }
    }

    public function test_renovar_rejeita_zero_dias(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', 0)
            ->call('renovar')
            ->assertHasErrors(['diasRenovacao']);

        $vinculo->refresh();
        $this->assertEquals('expirado', $vinculo->status_link); // não alterou
    }

    public function test_renovar_rejeita_mais_de_365_dias(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', 366)
            ->call('renovar')
            ->assertHasErrors(['diasRenovacao']);
    }

    public function test_abrir_renovacao_define_estado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->assertSet('renovandoVinculoId', $vinculo->id)
            ->assertSet('diasRenovacao', 30);
    }

    public function test_cancelar_renovacao_zera_estado(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->call('cancelarRenovacao')
            ->assertSet('renovandoVinculoId', 0)
            ->assertSet('diasRenovacao', 30);
    }

    public function test_renovar_zera_estado_apos_sucesso(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', 30)
            ->call('renovar')
            ->assertSet('renovandoVinculoId', 0);
    }

    public function test_botao_renovar_nao_aparece_para_link_valido(): void
    {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->addDays(20), 'status_link' => 'disponivel',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->assertDontSee('wire:click="abrirRenovacao');
    }
}
