<?php

namespace App\Http\Controllers;

use App\Models\Foto;
use App\Models\TrabalhoCliente;
use App\Services\GoogleDriveService;
use Illuminate\Support\Str;

class GalleryController extends Controller
{
    public function show(string $token)
    {
        $pivot = TrabalhoCliente::with(['trabalho', 'cliente'])->where('token', $token)->firstOrFail();

        // Verificar expiração
        if ($pivot->status_link === 'expirado' || ($pivot->expira_em && $pivot->expira_em->isPast())) {
            $pivot->marcarComoExpirado();
            return view('gallery.expirado', [
                'nomeTrabalho' => $pivot->trabalho->titulo,
                'nomeFotografa' => config('site.nome', 'Silvia Souza'),
                'telefone' => config('site.telefone', '(11) 99950-2677'),
                'whatsappLink' => config('site.whatsapp_link', 'https://wa.me/5511999502677'),
            ]);
        }

        $trabalho = $pivot->trabalho;
        $cliente = $pivot->cliente;
        $fotos = $trabalho->fotos()->orderBy('ordem')->get();

        return view('gallery.show', compact('trabalho', 'cliente', 'fotos', 'token', 'pivot'));
    }

    public function downloadFoto(string $token, Foto $foto)
    {
        $pivot = TrabalhoCliente::where('token', $token)->firstOrFail();

        // Verificar expiração
        if ($pivot->status_link === 'expirado' || ($pivot->expira_em && $pivot->expira_em->isPast())) {
            $pivot->marcarComoExpirado();
            abort(403, 'Link expirado.');
        }

        abort_if($foto->trabalho_id !== $pivot->trabalho_id, 403);

        // Arquivo local
        if (str_starts_with($foto->drive_arquivo_id, 'fotos/')) {
            $path = \Storage::disk('public')->path($foto->drive_arquivo_id);
            if (!file_exists($path)) abort(404);
            return response()->download($path, $foto->nome_arquivo);
        }

        // Google Drive
        try {
            $driveService = app(GoogleDriveService::class);
            $stream = $driveService->download($foto->drive_arquivo_id);

            return response($stream)
                ->header('Content-Type', 'application/octet-stream')
                ->header('Content-Disposition', "attachment; filename=\"{$foto->nome_arquivo}\"");
        } catch (\Exception $e) {
            abort(500, 'Erro ao baixar arquivo.');
        }
    }

    public function downloadTodas(string $token)
    {
        $pivot = TrabalhoCliente::with('trabalho.fotos')->where('token', $token)->firstOrFail();

        // Verificar expiração
        if ($pivot->status_link === 'expirado' || ($pivot->expira_em && $pivot->expira_em->isPast())) {
            $pivot->marcarComoExpirado();
            abort(403, 'Link expirado.');
        }

        $trabalho = $pivot->trabalho;

        if ($trabalho->tipo !== 'completo') {
            abort(403, 'Download não disponível para prévias.');
        }

        $nomeArquivo = Str::slug($trabalho->titulo) . '.zip';

        return response()->streamDownload(function () use ($trabalho) {
            $zip = new \ZipArchive();
            $tmpFile = tempnam(sys_get_temp_dir(), 'galeria_');
            $zip->open($tmpFile, \ZipArchive::CREATE);

            $driveService = null;
            try {
                $driveService = app(GoogleDriveService::class);
            } catch (\Exception $e) {
                // Drive não disponível
            }

            foreach ($trabalho->fotos as $foto) {
                try {
                    if ($driveService && !str_starts_with($foto->drive_arquivo_id, 'fotos/')) {
                        $stream = $driveService->download($foto->drive_arquivo_id);
                        $zip->addFromString($foto->nome_arquivo, (string) $stream);
                    } elseif (str_starts_with($foto->drive_arquivo_id, 'fotos/')) {
                        $localPath = storage_path('app/public/' . $foto->drive_arquivo_id);
                        if (file_exists($localPath)) {
                            $zip->addFile($localPath, $foto->nome_arquivo);
                        }
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }

            $zip->close();
            readfile($tmpFile);
            unlink($tmpFile);
        }, $nomeArquivo, [
            'Content-Type' => 'application/zip',
        ]);
    }
}
