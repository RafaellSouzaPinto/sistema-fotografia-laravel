<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use App\Livewire\Admin\PhotoUploader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

class ReordenarFotosTest extends TestCase
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

    public function test_reordenar_salva_nova_ordem(): void
    {
        $trabalho = Trabalho::factory()->create();
        $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);
        $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);
        $fotoC = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 3]);

        Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
            ->call('reordenar', [$fotoC->id, $fotoA->id, $fotoB->id]);

        $this->assertDatabaseHas('fotos', ['id' => $fotoC->id, 'ordem' => 1]);
        $this->assertDatabaseHas('fotos', ['id' => $fotoA->id, 'ordem' => 2]);
        $this->assertDatabaseHas('fotos', ['id' => $fotoB->id, 'ordem' => 3]);
    }

    public function test_reordenar_ordem_comeca_em_1(): void
    {
        $trabalho = Trabalho::factory()->create();
        $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);
        $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);

        Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
            ->call('reordenar', [$fotoA->id, $fotoB->id]);

        $this->assertDatabaseHas('fotos', ['id' => $fotoA->id, 'ordem' => 1]);
        $this->assertDatabaseHas('fotos', ['id' => $fotoB->id, 'ordem' => 2]);
        $this->assertDatabaseMissing('fotos', ['ordem' => 0]);
    }

    public function test_reordenar_dispara_notificacao_sucesso(): void
    {
        $trabalho = Trabalho::factory()->create();
        $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);
        $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);

        Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
            ->call('reordenar', [$fotoB->id, $fotoA->id])
            ->assertDispatched('notify');
    }

    public function test_reordenar_uma_foto_sem_erro(): void
    {
        $trabalho = Trabalho::factory()->create();
        $foto = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);

        Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
            ->call('reordenar', [$foto->id])
            ->assertHasNoErrors();

        $this->assertDatabaseHas('fotos', ['id' => $foto->id, 'ordem' => 1]);
    }

    public function test_galeria_reflete_nova_ordem(): void
    {
        $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
        $cliente = Cliente::factory()->create();
        $token = Str::random(64);
        $trabalho->clientes()->attach($cliente->id, [
            'token' => $token, 'expira_em' => now()->addDays(30), 'status_link' => 'disponivel',
        ]);

        $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'nome_arquivo' => 'foto_a.jpg', 'ordem' => 1]);
        $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'nome_arquivo' => 'foto_b.jpg', 'ordem' => 2]);
        $fotoC = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'nome_arquivo' => 'foto_c.jpg', 'ordem' => 3]);

        // Reordena: C primeiro
        Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
            ->call('reordenar', [$fotoC->id, $fotoA->id, $fotoB->id]);

        $fotoC->refresh();
        $fotoA->refresh();
        $fotoB->refresh();

        $this->assertEquals(1, $fotoC->ordem);
        $this->assertEquals(2, $fotoA->ordem);
        $this->assertEquals(3, $fotoB->ordem);
    }

    public function test_reordenar_ignora_fotos_de_outro_trabalho(): void
    {
        $trabalhoA = Trabalho::factory()->create();
        $trabalhoB = Trabalho::factory()->create();

        $fotoA = Foto::factory()->create(['trabalho_id' => $trabalhoA->id, 'ordem' => 1]);
        $fotoBOutro = Foto::factory()->create(['trabalho_id' => $trabalhoB->id, 'ordem' => 5]);

        // Tenta incluir foto de outro trabalho na reordenação
        Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalhoA->id])
            ->call('reordenar', [$fotoBOutro->id, $fotoA->id]);

        // Foto do outro trabalho não deve ser alterada
        $fotoBOutro->refresh();
        $this->assertEquals(5, $fotoBOutro->ordem);
    }

    public function test_nao_autenticado_redirecionado_para_login(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_fotos_do_trabalho_ordenadas_por_ordem(): void
    {
        $trabalho = Trabalho::factory()->create();
        Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 3]);
        Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);
        Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);

        $component = Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id]);

        $fotos = $component->get('fotosDoTrabalho');
        $ordens = $fotos->pluck('ordem')->toArray();

        $this->assertEquals([1, 2, 3], $ordens);
    }

    public function test_fotos_do_trabalho_vazia_sem_fotos(): void
    {
        $trabalho = Trabalho::factory()->create();

        $component = Livewire::actingAs($this->usuario)
            ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id]);

        $this->assertCount(0, $component->get('fotosDoTrabalho'));
    }
}
