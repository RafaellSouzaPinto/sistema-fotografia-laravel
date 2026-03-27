<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use App\Models\TrabalhoCliente;
use App\Livewire\Admin\JobList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

class DashboardNumerosTest extends TestCase
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

    public function test_total_publicados_ignora_rascunhos(): void
    {
        Trabalho::factory()->count(3)->create(['status' => 'publicado']);
        Trabalho::factory()->count(2)->create(['status' => 'rascunho']);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('totalPublicados', 3);
    }

    public function test_total_clientes_conta_todos(): void
    {
        Cliente::factory()->count(5)->create();

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('totalClientes', 5);
    }

    public function test_total_clientes_ignora_deletados(): void
    {
        $clientes = Cliente::factory()->count(5)->create();
        $clientes->take(2)->each->delete();

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('totalClientes', 3);
    }

    public function test_total_fotos_conta_corretamente(): void
    {
        $trabalho = Trabalho::factory()->create();
        Foto::factory()->count(10)->create(['trabalho_id' => $trabalho->id]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('totalFotos', 10);
    }

    public function test_total_fotos_ignora_deletadas(): void
    {
        $trabalho = Trabalho::factory()->create();
        $fotos = Foto::factory()->count(10)->create(['trabalho_id' => $trabalho->id]);
        $fotos->take(3)->each->delete();

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('totalFotos', 7);
    }

    public function test_links_expirando_em_breve_conta_proximos_7_dias(): void
    {
        $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
        $clientes = Cliente::factory()->count(3)->create();

        $trabalho->clientes()->attach($clientes[0]->id, [
            'token' => Str::random(64), 'expira_em' => now()->addDays(3), 'status_link' => 'disponivel',
        ]);
        $trabalho->clientes()->attach($clientes[1]->id, [
            'token' => Str::random(64), 'expira_em' => now()->addDays(5), 'status_link' => 'disponivel',
        ]);
        $trabalho->clientes()->attach($clientes[2]->id, [
            'token' => Str::random(64), 'expira_em' => now()->addDays(10), 'status_link' => 'disponivel',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('linksExpirandoEmBreve', 2);
    }

    public function test_links_expirados_nao_entram_no_alerta(): void
    {
        $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
        $clientes = Cliente::factory()->count(2)->create();

        $trabalho->clientes()->attach($clientes[0]->id, [
            'token' => Str::random(64), 'expira_em' => now()->subDays(2), 'status_link' => 'expirado',
        ]);
        $trabalho->clientes()->attach($clientes[1]->id, [
            'token' => Str::random(64), 'expira_em' => now()->subDays(1), 'status_link' => 'disponivel',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('linksExpirandoEmBreve', 0);
    }

    public function test_alerta_aparece_na_view_quando_ha_links_expirando(): void
    {
        $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
        $cliente = Cliente::factory()->create();

        $trabalho->clientes()->attach($cliente->id, [
            'token' => Str::random(64), 'expira_em' => now()->addDays(3), 'status_link' => 'disponivel',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertSee('expira');
    }

    public function test_alerta_nao_aparece_sem_links_expirando(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertDontSee('links expiram');
    }

    public function test_botao_ver_trabalhos_ativa_filtro_expirados(): void
    {
        $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
        $cliente = Cliente::factory()->create();
        $trabalho->clientes()->attach($cliente->id, [
            'token' => Str::random(64), 'expira_em' => now()->addDays(3), 'status_link' => 'disponivel',
        ]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->call('$set', 'filtroTipo', 'expirados')
            ->assertSet('filtroTipo', 'expirados');
    }

    public function test_dashboard_banco_vazio_sem_erros(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertStatus(200);
    }

    public function test_totais_zerados_com_banco_vazio(): void
    {
        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertViewHas('totalPublicados', 0)
            ->assertViewHas('totalClientes', 0)
            ->assertViewHas('totalFotos', 0)
            ->assertViewHas('linksExpirandoEmBreve', 0);
    }
}
