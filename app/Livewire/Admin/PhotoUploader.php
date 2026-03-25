<?php

namespace App\Livewire\Admin;

use App\Models\Foto;
use App\Models\Trabalho;
use App\Services\GoogleDriveService;
use App\Services\ImageCompressorService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class PhotoUploader extends Component
{
    use WithFileUploads;

    public int $trabalhoId;
    public $arquivos = [];

    protected function rules(): array
    {
        return [
            'arquivos.*' => 'file|mimes:jpg,jpeg,png,psd,tif,tiff|max:204800', // 200MB
        ];
    }

    public function updatedArquivos(): void
    {
        if (empty($this->arquivos)) return;

        $processadas = 0;
        $falhas = [];

        try {
            $trabalho = Trabalho::findOrFail($this->trabalhoId);
            $compressor = app(ImageCompressorService::class);

            foreach ($this->arquivos as $arquivo) {
                try {
                    $nomeOriginal = $arquivo->getClientOriginalName();
                    $tamanho = $arquivo->getSize();
                    $mimeType = $arquivo->getMimeType();
                    $extensao = strtolower($arquivo->getClientOriginalExtension());

                    // Tentar fazer upload no Google Drive
                    if ($trabalho->drive_pasta_id) {
                        try {
                            $driveService = app(GoogleDriveService::class);
                            $resultado = $driveService->upload(
                                $trabalho->drive_pasta_id,
                                $nomeOriginal,
                                $arquivo->getRealPath(),
                                $mimeType
                            );

                            Foto::create([
                                'trabalho_id' => $this->trabalhoId,
                                'nome_arquivo' => $nomeOriginal,
                                'drive_arquivo_id' => $resultado['id'],
                                'drive_thumbnail' => $resultado['thumbnailLink'] ?? null,
                                'tamanho_bytes' => $resultado['size'] ?? $tamanho,
                                'ordem' => Foto::where('trabalho_id', $this->trabalhoId)->count(),
                            ]);

                            $processadas++;
                            continue;
                        } catch (\Exception $e) {
                            // Fallback para storage local
                        }
                    }

                    // Salvar original
                    $pathOriginal = $arquivo->store("fotos/{$this->trabalhoId}/originais", 'public');
                    $caminhoAbsolutoOriginal = storage_path("app/public/{$pathOriginal}");

                    $pathFinal = $pathOriginal;
                    $pathThumbnail = null;

                    if ($compressor->suportaCompressao($extensao)) {
                        if ($trabalho->tipo === 'previa') {
                            // PRÉVIA: comprimir e usar como arquivo principal
                            $nomeComprimido = pathinfo($nomeOriginal, PATHINFO_FILENAME) . '_compressed.jpg';
                            $pathComprimidoRelativo = "fotos/{$this->trabalhoId}/{$nomeComprimido}";
                            $caminhoAbsolutoComprimido = storage_path("app/public/{$pathComprimidoRelativo}");

                            $dir = dirname($caminhoAbsolutoComprimido);
                            if (!is_dir($dir)) mkdir($dir, 0755, true);

                            $compressor->comprimir($caminhoAbsolutoOriginal, $caminhoAbsolutoComprimido, 70, 1920);

                            $pathFinal = $pathComprimidoRelativo;
                            $pathThumbnail = $pathComprimidoRelativo;

                            Storage::disk('public')->delete($pathOriginal);
                            $tamanho = filesize($caminhoAbsolutoComprimido);
                        } else {
                            // COMPLETO: manter original, gerar thumbnail separado
                            $nomeThumbnail = pathinfo($nomeOriginal, PATHINFO_FILENAME) . '_thumb.jpg';
                            $pathThumbnailRelativo = "fotos/{$this->trabalhoId}/thumbnails/{$nomeThumbnail}";
                            $caminhoAbsolutoThumbnail = storage_path("app/public/{$pathThumbnailRelativo}");

                            $dir = dirname($caminhoAbsolutoThumbnail);
                            if (!is_dir($dir)) mkdir($dir, 0755, true);

                            $compressor->gerarThumbnail($caminhoAbsolutoOriginal, $caminhoAbsolutoThumbnail, 600, 60);

                            $pathFinal = $pathOriginal;
                            $pathThumbnail = $pathThumbnailRelativo;
                        }
                    }

                    Foto::create([
                        'trabalho_id' => $this->trabalhoId,
                        'nome_arquivo' => $nomeOriginal,
                        'drive_arquivo_id' => $pathFinal,
                        'caminho_thumbnail' => $pathThumbnail,
                        'drive_thumbnail' => $pathThumbnail ? asset("storage/{$pathThumbnail}") : null,
                        'tamanho_bytes' => $tamanho,
                        'ordem' => Foto::where('trabalho_id', $this->trabalhoId)->count(),
                    ]);

                    $processadas++;
                } catch (\Exception $e) {
                    $falhas[] = $arquivo->getClientOriginalName();
                }
            }
        } catch (\Exception $e) {
            // Erro geral — marca todos os arquivos do lote como falha
            foreach ($this->arquivos as $arquivo) {
                if (!in_array($arquivo->getClientOriginalName(), $falhas)) {
                    $falhas[] = $arquivo->getClientOriginalName();
                }
            }
        }

        $this->arquivos = [];

        // Sempre despacha o evento — Alpine.js usa para controlar o progresso
        $this->dispatch('loteProcessado', processadas: $processadas, falhas: $falhas);
    }

    public function removerFoto(int $fotoId): void
    {
        $foto = Foto::findOrFail($fotoId);

        // Tentar deletar do Drive
        if ($foto->drive_arquivo_id && !str_starts_with($foto->drive_arquivo_id, 'fotos/')) {
            try {
                $driveService = app(GoogleDriveService::class);
                $driveService->deletar($foto->drive_arquivo_id);
            } catch (\Exception $e) {
                // Continua mesmo se falhar
            }
        } else {
            // Deletar arquivo principal do storage local
            Storage::disk('public')->delete($foto->drive_arquivo_id);

            // Deletar thumbnail se existir e for diferente do principal
            if ($foto->caminho_thumbnail && $foto->caminho_thumbnail !== $foto->drive_arquivo_id) {
                Storage::disk('public')->delete($foto->caminho_thumbnail);
            }
        }

        $foto->delete();
        $this->dispatch('notify', message: 'Foto removida.');
    }

    public function render()
    {
        $fotos = Foto::where('trabalho_id', $this->trabalhoId)->orderBy('ordem')->get();
        return view('livewire.admin.photo-uploader', compact('fotos'));
    }
}
