<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Foto;
use App\Services\ImageCompressorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CompressaoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $usuario;
    private ImageCompressorService $compressor;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->usuario = Usuario::create([
            'nome' => 'Silvia Souza',
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => bcrypt('123456'),
        ]);

        $this->compressor = new ImageCompressorService();
    }

    // ===================================================
    // TESTES DO SERVICE — ImageCompressorService
    // ===================================================

    public function test_service_comprime_jpg(): void
    {
        // Criar imagem fake com GD (2000x1500)
        $img = imagecreatetruecolor(2000, 1500);
        $cor = imagecolorallocate($img, 200, 100, 150);
        imagefill($img, 0, 0, $cor);

        $caminhoOriginal = storage_path('app/public/test_original.jpg');
        $caminhoComprimido = storage_path('app/public/test_compressed.jpg');

        // Garantir diretório
        if (!is_dir(dirname($caminhoOriginal))) {
            mkdir(dirname($caminhoOriginal), 0755, true);
        }

        imagejpeg($img, $caminhoOriginal, 100);
        imagedestroy($img);

        $this->compressor->comprimir($caminhoOriginal, $caminhoComprimido, 70, 1920);

        $this->assertFileExists($caminhoComprimido);

        // Comprimido deve ser menor que original
        $this->assertLessThan(
            filesize($caminhoOriginal),
            filesize($caminhoComprimido)
        );

        // Limpar
        @unlink($caminhoOriginal);
        @unlink($caminhoComprimido);
    }

    public function test_service_redimensiona_imagem_grande(): void
    {
        // Criar imagem 4000x3000 (maior que 1920)
        $img = imagecreatetruecolor(4000, 3000);
        $cor = imagecolorallocate($img, 100, 150, 200);
        imagefill($img, 0, 0, $cor);

        $caminhoOriginal = storage_path('app/public/test_grande.jpg');
        $caminhoComprimido = storage_path('app/public/test_grande_compressed.jpg');

        if (!is_dir(dirname($caminhoOriginal))) {
            mkdir(dirname($caminhoOriginal), 0755, true);
        }

        imagejpeg($img, $caminhoOriginal, 100);
        imagedestroy($img);

        $this->compressor->comprimir($caminhoOriginal, $caminhoComprimido, 70, 1920);

        // Verificar que foi redimensionado
        $info = getimagesize($caminhoComprimido);
        $this->assertLessThanOrEqual(1920, $info[0]); // largura <= 1920

        @unlink($caminhoOriginal);
        @unlink($caminhoComprimido);
    }

    public function test_service_nao_redimensiona_imagem_pequena(): void
    {
        // Criar imagem 800x600 (menor que 1920)
        $img = imagecreatetruecolor(800, 600);
        $cor = imagecolorallocate($img, 50, 100, 150);
        imagefill($img, 0, 0, $cor);

        $caminhoOriginal = storage_path('app/public/test_pequena.jpg');
        $caminhoComprimido = storage_path('app/public/test_pequena_compressed.jpg');

        if (!is_dir(dirname($caminhoOriginal))) {
            mkdir(dirname($caminhoOriginal), 0755, true);
        }

        imagejpeg($img, $caminhoOriginal, 100);
        imagedestroy($img);

        $this->compressor->comprimir($caminhoOriginal, $caminhoComprimido, 70, 1920);

        $info = getimagesize($caminhoComprimido);
        $this->assertEquals(800, $info[0]); // mantém largura original

        @unlink($caminhoOriginal);
        @unlink($caminhoComprimido);
    }

    public function test_service_gera_thumbnail_quadrado(): void
    {
        $img = imagecreatetruecolor(3000, 2000);
        $cor = imagecolorallocate($img, 150, 80, 120);
        imagefill($img, 0, 0, $cor);

        $caminhoOriginal = storage_path('app/public/test_thumb_original.jpg');
        $caminhoThumbnail = storage_path('app/public/test_thumb.jpg');

        if (!is_dir(dirname($caminhoOriginal))) {
            mkdir(dirname($caminhoOriginal), 0755, true);
        }

        imagejpeg($img, $caminhoOriginal, 100);
        imagedestroy($img);

        $this->compressor->gerarThumbnail($caminhoOriginal, $caminhoThumbnail, 600, 60);

        $this->assertFileExists($caminhoThumbnail);

        $info = getimagesize($caminhoThumbnail);
        $this->assertEquals(600, $info[0]); // largura 600
        $this->assertEquals(600, $info[1]); // altura 600 (quadrado)

        @unlink($caminhoOriginal);
        @unlink($caminhoThumbnail);
    }

    public function test_service_thumbnail_muito_menor_que_original(): void
    {
        $img = imagecreatetruecolor(3000, 2000);
        $cor = imagecolorallocate($img, 200, 150, 100);
        imagefill($img, 0, 0, $cor);

        $caminhoOriginal = storage_path('app/public/test_size_original.jpg');
        $caminhoThumbnail = storage_path('app/public/test_size_thumb.jpg');

        if (!is_dir(dirname($caminhoOriginal))) {
            mkdir(dirname($caminhoOriginal), 0755, true);
        }

        imagejpeg($img, $caminhoOriginal, 100);
        imagedestroy($img);

        $this->compressor->gerarThumbnail($caminhoOriginal, $caminhoThumbnail, 600, 60);

        // Thumbnail deve ser MUITO menor que original
        $this->assertLessThan(
            filesize($caminhoOriginal) * 0.5, // pelo menos 50% menor
            filesize($caminhoThumbnail)
        );

        @unlink($caminhoOriginal);
        @unlink($caminhoThumbnail);
    }

    // ===================================================
    // TESTES DE SUPORTE A EXTENSÕES
    // ===================================================

    public function test_suporta_jpg(): void
    {
        $this->assertTrue($this->compressor->suportaCompressao('jpg'));
        $this->assertTrue($this->compressor->suportaCompressao('jpeg'));
        $this->assertTrue($this->compressor->suportaCompressao('JPG'));
        $this->assertTrue($this->compressor->suportaCompressao('JPEG'));
    }

    public function test_suporta_png(): void
    {
        $this->assertTrue($this->compressor->suportaCompressao('png'));
        $this->assertTrue($this->compressor->suportaCompressao('PNG'));
    }

    public function test_nao_suporta_psd(): void
    {
        $this->assertFalse($this->compressor->suportaCompressao('psd'));
        $this->assertFalse($this->compressor->suportaCompressao('PSD'));
    }

    public function test_nao_suporta_tif(): void
    {
        $this->assertFalse($this->compressor->suportaCompressao('tif'));
        $this->assertFalse($this->compressor->suportaCompressao('tiff'));
    }

    // ===================================================
    // TESTES DE UPLOAD — PRÉVIA (comprime e substitui)
    // ===================================================

    public function test_upload_previa_comprime_foto(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Prévia Teste',
            'data_trabalho' => '2026-06-01',
            'tipo' => 'previa',
            'status' => 'rascunho',
        ]);

        // Criar imagem fake grande
        $img = imagecreatetruecolor(3000, 2000);
        imagefill($img, 0, 0, imagecolorallocate($img, 200, 100, 100));
        $tempPath = tempnam(sys_get_temp_dir(), 'test_') . '.jpg';
        imagejpeg($img, $tempPath, 100);
        imagedestroy($img);

        $tamanhoOriginal = filesize($tempPath);

        // Simular compressão como o PhotoUploader faria
        $compressor = new ImageCompressorService();
        $caminhoComprimido = storage_path('app/public/fotos/' . $trabalho->id . '/teste_compressed.jpg');
        $dir = dirname($caminhoComprimido);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $compressor->comprimir($tempPath, $caminhoComprimido, 70, 1920);

        $tamanhoComprimido = filesize($caminhoComprimido);

        // Comprimido deve ser menor
        $this->assertLessThan($tamanhoOriginal, $tamanhoComprimido);

        // Foto criada no banco referencia o comprimido
        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'teste.jpg',
            'drive_arquivo_id' => "fotos/{$trabalho->id}/teste_compressed.jpg",
            'caminho_thumbnail' => "fotos/{$trabalho->id}/teste_compressed.jpg",
            'tamanho_bytes' => $tamanhoComprimido,
            'ordem' => 0,
        ]);

        // Na prévia, drive_arquivo_id e caminho_thumbnail são iguais
        $this->assertEquals($foto->drive_arquivo_id, $foto->caminho_thumbnail);

        @unlink($tempPath);
        @unlink($caminhoComprimido);
    }

    // ===================================================
    // TESTES DE UPLOAD — COMPLETO (mantém original + thumbnail)
    // ===================================================

    public function test_upload_completo_mantem_original_e_gera_thumbnail(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Completo Teste',
            'data_trabalho' => '2026-06-01',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);

        // Criar imagem fake
        $img = imagecreatetruecolor(4000, 3000);
        imagefill($img, 0, 0, imagecolorallocate($img, 100, 200, 150));
        $tempPath = tempnam(sys_get_temp_dir(), 'test_') . '.jpg';
        imagejpeg($img, $tempPath, 100);
        imagedestroy($img);

        // Salvar original
        $pathOriginal = "fotos/{$trabalho->id}/originais/foto_original.jpg";
        $caminhoAbsolutoOriginal = storage_path("app/public/{$pathOriginal}");
        $dir = dirname($caminhoAbsolutoOriginal);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        copy($tempPath, $caminhoAbsolutoOriginal);

        // Gerar thumbnail
        $pathThumbnail = "fotos/{$trabalho->id}/thumbnails/foto_thumb.jpg";
        $caminhoAbsolutoThumbnail = storage_path("app/public/{$pathThumbnail}");
        $dir = dirname($caminhoAbsolutoThumbnail);
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $compressor = new ImageCompressorService();
        $compressor->gerarThumbnail($caminhoAbsolutoOriginal, $caminhoAbsolutoThumbnail, 600, 60);

        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'foto_original.jpg',
            'drive_arquivo_id' => $pathOriginal,
            'caminho_thumbnail' => $pathThumbnail,
            'tamanho_bytes' => filesize($caminhoAbsolutoOriginal),
            'ordem' => 0,
        ]);

        // Original e thumbnail são DIFERENTES
        $this->assertNotEquals($foto->drive_arquivo_id, $foto->caminho_thumbnail);

        // Ambos existem
        $this->assertFileExists($caminhoAbsolutoOriginal);
        $this->assertFileExists($caminhoAbsolutoThumbnail);

        // Thumbnail é menor que original
        $this->assertLessThan(
            filesize($caminhoAbsolutoOriginal),
            filesize($caminhoAbsolutoThumbnail)
        );

        @unlink($tempPath);
        @unlink($caminhoAbsolutoOriginal);
        @unlink($caminhoAbsolutoThumbnail);
    }

    // ===================================================
    // TESTES DO MODEL FOTO — campo caminho_thumbnail
    // ===================================================

    public function test_foto_tem_campo_caminho_thumbnail(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Teste Campo',
            'data_trabalho' => '2026-01-01',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);

        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'foto.jpg',
            'drive_arquivo_id' => 'fotos/1/originais/foto.jpg',
            'caminho_thumbnail' => 'fotos/1/thumbnails/foto_thumb.jpg',
            'tamanho_bytes' => 5000,
            'ordem' => 0,
        ]);

        $this->assertEquals('fotos/1/thumbnails/foto_thumb.jpg', $foto->caminho_thumbnail);
    }

    public function test_foto_caminho_thumbnail_pode_ser_null(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Teste PSD',
            'data_trabalho' => '2026-01-01',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);

        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'arquivo.psd',
            'drive_arquivo_id' => 'fotos/1/originais/arquivo.psd',
            'caminho_thumbnail' => null,
            'tamanho_bytes' => 50000000,
            'ordem' => 0,
        ]);

        $this->assertNull($foto->caminho_thumbnail);
    }

    // ===================================================
    // TESTES DE EXIBIÇÃO — galeria usa thumbnail
    // ===================================================

    public function test_galeria_usa_thumbnail_para_exibicao(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Casamento',
            'data_trabalho' => '2026-03-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);

        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'foto.jpg',
            'drive_arquivo_id' => 'fotos/1/originais/foto.jpg',
            'caminho_thumbnail' => 'fotos/1/thumbnails/foto_thumb.jpg',
            'tamanho_bytes' => 7000000,
            'ordem' => 0,
        ]);

        $cliente = \App\Models\Cliente::create(['nome' => 'Ana', 'telefone' => '(11) 99999-0000']);
        $token = \Illuminate\Support\Str::random(64);
        $trabalho->clientes()->attach($cliente->id, [
            'token' => $token,
            'expira_em' => now()->addDays(30),
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}");
        $response->assertStatus(200);

        // A view deve referenciar o thumbnail, não o original
        $response->assertSee('foto_thumb.jpg');
    }

    // ===================================================
    // TESTES DE DOWNLOAD — sempre entrega original
    // ===================================================

    public function test_download_entrega_original_nao_thumbnail(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Download Teste',
            'data_trabalho' => '2026-03-15',
            'tipo' => 'completo',
            'status' => 'publicado',
        ]);

        // Criar arquivo fake no storage
        $pathOriginal = 'fotos/1/originais/foto_download.jpg';
        Storage::disk('public')->put($pathOriginal, 'conteudo original fake');

        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'foto_download.jpg',
            'drive_arquivo_id' => $pathOriginal,
            'caminho_thumbnail' => 'fotos/1/thumbnails/foto_download_thumb.jpg',
            'tamanho_bytes' => 7000000,
            'ordem' => 0,
        ]);

        $cliente = \App\Models\Cliente::create(['nome' => 'João', 'telefone' => '(11) 88888-0000']);
        $token = \Illuminate\Support\Str::random(64);
        $trabalho->clientes()->attach($cliente->id, [
            'token' => $token,
            'expira_em' => now()->addDays(30),
            'status_link' => 'disponivel',
        ]);

        $response = $this->get("/galeria/{$token}/foto/{$foto->id}");
        $response->assertStatus(200);
        $response->assertDownload('foto_download.jpg');
    }

    // ===================================================
    // TESTE DE DELEÇÃO — limpa thumbnail junto
    // ===================================================

    public function test_deletar_foto_remove_thumbnail(): void
    {
        $trabalho = Trabalho::create([
            'titulo' => 'Delete Teste',
            'data_trabalho' => '2026-01-01',
            'tipo' => 'completo',
            'status' => 'rascunho',
        ]);

        $pathOriginal = 'fotos/99/originais/deletar.jpg';
        $pathThumb = 'fotos/99/thumbnails/deletar_thumb.jpg';

        Storage::disk('public')->put($pathOriginal, 'original');
        Storage::disk('public')->put($pathThumb, 'thumbnail');

        $foto = Foto::create([
            'trabalho_id' => $trabalho->id,
            'nome_arquivo' => 'deletar.jpg',
            'drive_arquivo_id' => $pathOriginal,
            'caminho_thumbnail' => $pathThumb,
            'tamanho_bytes' => 1000,
            'ordem' => 0,
        ]);

        // Deletar
        Storage::disk('public')->delete($foto->drive_arquivo_id);
        if ($foto->caminho_thumbnail && $foto->caminho_thumbnail !== $foto->drive_arquivo_id) {
            Storage::disk('public')->delete($foto->caminho_thumbnail);
        }
        $foto->delete();

        Storage::disk('public')->assertMissing($pathOriginal);
        Storage::disk('public')->assertMissing($pathThumb);
        $this->assertSoftDeleted('fotos', ['id' => $foto->id]);
    }

    // ===================================================
    // TESTE DE PNG
    // ===================================================

    public function test_service_comprime_png_para_jpg(): void
    {
        $img = imagecreatetruecolor(2000, 1500);
        imagefill($img, 0, 0, imagecolorallocate($img, 100, 200, 100));

        $caminhoOriginal = storage_path('app/public/test_png_original.png');
        $caminhoComprimido = storage_path('app/public/test_png_compressed.jpg');

        if (!is_dir(dirname($caminhoOriginal))) {
            mkdir(dirname($caminhoOriginal), 0755, true);
        }

        imagepng($img, $caminhoOriginal);
        imagedestroy($img);

        $this->compressor->comprimir($caminhoOriginal, $caminhoComprimido, 70, 1920);

        $this->assertFileExists($caminhoComprimido);

        // Resultado é JPG (verificar mime type)
        $info = getimagesize($caminhoComprimido);
        $this->assertEquals(IMAGETYPE_JPEG, $info[2]);

        @unlink($caminhoOriginal);
        @unlink($caminhoComprimido);
    }
}
