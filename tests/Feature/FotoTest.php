<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Foto;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FotoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private Trabalho $trabalho;

    protected function setUp(): void
    {
        parent::setUp();
        $this->usuario = Usuario::create([
            'nome' => 'Silvia Souza',
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => bcrypt('123456'),
        ]);

        $this->trabalho = Trabalho::create([
            'titulo' => 'Trabalho com Fotos',
            'data_trabalho' => '2026-05-01',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);
    }

    public function test_criar_foto_vinculada_a_trabalho(): void
    {
        $foto = Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'foto_casamento_001.jpg',
            'drive_arquivo_id' => 'fotos/1/foto_casamento_001.jpg',
            'tamanho_bytes' => 7340032,
            'ordem' => 0,
        ]);

        $this->assertDatabaseHas('fotos', [
            'nome_arquivo' => 'foto_casamento_001.jpg',
            'trabalho_id' => $this->trabalho->id,
        ]);

        $this->assertEquals(1, $this->trabalho->fotos()->count());
    }

    public function test_deletar_foto_usa_soft_delete(): void
    {
        $foto = Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'deletar.jpg',
            'drive_arquivo_id' => 'fotos/1/deletar.jpg',
            'tamanho_bytes' => 1000,
            'ordem' => 0,
        ]);

        $foto->delete();
        $this->assertSoftDeleted('fotos', ['id' => $foto->id]);
    }

    public function test_trabalho_contagem_fotos(): void
    {
        Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'foto1.jpg',
            'drive_arquivo_id' => 'fotos/1/foto1.jpg',
            'tamanho_bytes' => 5000,
            'ordem' => 0,
        ]);
        Foto::create([
            'trabalho_id' => $this->trabalho->id,
            'nome_arquivo' => 'foto2.jpg',
            'drive_arquivo_id' => 'fotos/1/foto2.jpg',
            'tamanho_bytes' => 6000,
            'ordem' => 1,
        ]);

        $trabalho = Trabalho::withCount('fotos')->find($this->trabalho->id);
        $this->assertEquals(2, $trabalho->fotos_count);
    }
}
