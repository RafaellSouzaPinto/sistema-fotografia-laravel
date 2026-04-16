<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use App\Livewire\Admin\JobList;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class FotoCapaTest extends TestCase
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

    public function test_foto_capa_retorna_thumbnail_local_quando_existe(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('thumbnails/foto.jpg', 'conteudo fake');

        $trabalho = Trabalho::factory()->create();
        Foto::factory()->create([
            'trabalho_id'       => $trabalho->id,
            'caminho_thumbnail' => 'thumbnails/foto.jpg',
            'drive_thumbnail'   => null,
            'ordem'             => 1,
        ]);

        $this->assertNotNull($trabalho->fotoCapa());
        $this->assertStringContainsString('thumbnails/foto.jpg', $trabalho->fotoCapa());
    }

    public function test_foto_capa_usa_drive_thumbnail_como_fallback(): void
    {
        Storage::fake('public'); // vazio — arquivo local não existe

        $trabalho = Trabalho::factory()->create();
        Foto::factory()->create([
            'trabalho_id'       => $trabalho->id,
            'caminho_thumbnail' => 'thumbnails/nao_existe.jpg',
            'drive_thumbnail'   => 'https://drive.google.com/thumb/abc123',
            'ordem'             => 1,
        ]);

        $this->assertEquals('https://drive.google.com/thumb/abc123', $trabalho->fotoCapa());
    }

    public function test_foto_capa_retorna_null_sem_fotos(): void
    {
        $trabalho = Trabalho::factory()->create();
        $this->assertNull($trabalho->fotoCapa());
    }

    public function test_foto_capa_retorna_null_sem_thumbnails(): void
    {
        Storage::fake('public');

        $trabalho = Trabalho::factory()->create();
        Foto::factory()->create([
            'trabalho_id'       => $trabalho->id,
            'caminho_thumbnail' => null,
            'drive_thumbnail'   => null,
            'ordem'             => 1,
        ]);

        $this->assertNull($trabalho->fotoCapa());
    }

    public function test_foto_capa_usa_menor_ordem(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('thumbnails/primeira.jpg', 'fake');
        Storage::disk('public')->put('thumbnails/segunda.jpg', 'fake');

        $trabalho = Trabalho::factory()->create();

        Foto::factory()->create([
            'trabalho_id'       => $trabalho->id,
            'caminho_thumbnail' => 'thumbnails/segunda.jpg',
            'ordem'             => 2,
        ]);
        Foto::factory()->create([
            'trabalho_id'       => $trabalho->id,
            'caminho_thumbnail' => 'thumbnails/primeira.jpg',
            'ordem'             => 1,
        ]);

        $this->assertStringContainsString('primeira.jpg', $trabalho->fotoCapa());
    }

    public function test_dashboard_exibe_imagem_quando_trabalho_tem_foto(): void
    {
        Storage::fake('public');

        $trabalho = Trabalho::factory()->create(['status' => 'publicado', 'titulo' => 'Casamento Teste']);
        Foto::factory()->create([
            'trabalho_id'     => $trabalho->id,
            'drive_thumbnail' => 'https://drive.google.com/thumb/xyz',
            'ordem'           => 1,
        ]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertSee('https://drive.google.com/thumb/xyz');
    }

    public function test_dashboard_exibe_placeholder_sem_fotos(): void
    {
        Trabalho::factory()->create(['status' => 'publicado', 'titulo' => 'Trabalho Sem Fotos']);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertSee('Sem fotos');
    }

    public function test_sem_n_mais_1_na_listagem(): void
    {
        $trabalhos = Trabalho::factory()->count(5)->create(['status' => 'publicado']);
        foreach ($trabalhos as $t) {
            Foto::factory()->count(3)->create(['trabalho_id' => $t->id]);
        }

        // Conta queries — deve ser constante, não crescer com número de trabalhos
        $queryCount = 0;
        \DB::listen(function () use (&$queryCount) { $queryCount++; });

        Livewire::actingAs($this->usuario)->test(JobList::class);

        // Com eager load, não deve executar 1 query por trabalho para fotos
        $this->assertLessThan(10, $queryCount, "Possível N+1 detectado: {$queryCount} queries");
    }

    public function test_dashboard_renderiza_sem_erros_sem_capas(): void
    {
        Trabalho::factory()->count(3)->create(['status' => 'publicado']);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertStatus(200);
    }

    public function test_imagem_capa_tem_altura_definida(): void
    {
        Storage::fake('public');
        $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
        Foto::factory()->create([
            'trabalho_id'     => $trabalho->id,
            'drive_thumbnail' => 'https://drive.google.com/thumb/test',
            'ordem'           => 1,
        ]);

        Livewire::actingAs($this->usuario)
            ->test(JobList::class)
            ->assertSee('height:180px');
    }
}
