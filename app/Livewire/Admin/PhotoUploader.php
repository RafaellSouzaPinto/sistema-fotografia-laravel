<?php

namespace App\Livewire\Admin;

use App\Models\Foto;
use App\Models\Trabalho;
use App\Services\GoogleDriveService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

class PhotoUploader extends Component
{
    use WithFileUploads;

    public int $trabalhoId;
    public $arquivos = [];
    public bool $enviando = false;

    protected function rules(): array
    {
        return [
            'arquivos.*' => 'file|mimes:jpg,jpeg,png,psd,tif,tiff|max:204800', // 200MB
        ];
    }

    public function updatedArquivos(): void
    {
        $this->enviando = true;

        try {
            $trabalho = Trabalho::findOrFail($this->trabalhoId);

            foreach ($this->arquivos as $arquivo) {
                $nomeOriginal = $arquivo->getClientOriginalName();
                $tamanho = $arquivo->getSize();
                $mimeType = $arquivo->getMimeType();

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
                        continue;
                    } catch (\Exception $e) {
                        // Fallback para storage local
                    }
                }

                // Fallback: salvar localmente
                $path = $arquivo->store("fotos/{$this->trabalhoId}", 'public');

                Foto::create([
                    'trabalho_id' => $this->trabalhoId,
                    'nome_arquivo' => $nomeOriginal,
                    'drive_arquivo_id' => $path,
                    'drive_thumbnail' => asset("storage/{$path}"),
                    'tamanho_bytes' => $tamanho,
                    'ordem' => Foto::where('trabalho_id', $this->trabalhoId)->count(),
                ]);
            }

            $this->dispatch('notify', message: count($this->arquivos) . ' foto(s) enviada(s) com sucesso!');
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Erro ao enviar fotos: ' . $e->getMessage(), type: 'error');
        }

        $this->arquivos = [];
        $this->enviando = false;
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
            // Deletar do storage local
            Storage::disk('public')->delete($foto->drive_arquivo_id);
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
