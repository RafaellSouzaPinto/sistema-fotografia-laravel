<?php

namespace App\Http\Controllers;

use App\Models\Trabalho;
use App\Services\GoogleDriveService;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class AdminJobController extends Controller
{
    public function downloadFotos(Trabalho $trabalho)
    {
        $fotos = $trabalho->fotos()->orderBy('ordem')->get();

        if ($fotos->isEmpty()) {
            return back()->with('erro', 'Este trabalho não tem fotos para baixar.');
        }

        $nomeArquivo = str($trabalho->titulo)->slug()->append('.zip')->toString();
        $tmpPath = tempnam(sys_get_temp_dir(), 'fotos_');

        $zip = new ZipArchive();
        $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($fotos as $foto) {
            $ehDrive = !str_starts_with($foto->drive_arquivo_id, 'fotos/');

            if ($ehDrive) {
                try {
                    $driveService = app(GoogleDriveService::class);
                    $conteudo = $driveService->download($foto->drive_arquivo_id)->getContents();
                    $zip->addFromString($foto->nome_arquivo, $conteudo);
                } catch (\Exception) {
                    continue;
                }
            } else {
                $caminho = Storage::disk('public')->path($foto->drive_arquivo_id);
                if (file_exists($caminho)) {
                    $zip->addFile($caminho, $foto->nome_arquivo);
                }
            }
        }

        $zip->close();

        return response()->download($tmpPath, $nomeArquivo, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }
}
