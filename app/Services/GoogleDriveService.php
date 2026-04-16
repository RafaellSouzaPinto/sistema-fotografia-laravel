<?php

namespace App\Services;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;

class GoogleDriveService
{
    protected Drive $service;
    protected string $pastaRaizId;

    public function __construct()
    {
        $client = new Client();
        $credentialsPath = storage_path('app/google/credentials.json');
        
        if (!file_exists($credentialsPath)) {
            throw new \RuntimeException("Credenciais do Google Drive não encontradas em: {$credentialsPath}");
        }

        $client->setAuthConfig($credentialsPath);
        $client->addScope(Drive::DRIVE);
        $client->setApplicationName('Silvia Souza Fotografa');

        $this->service = new Drive($client);
        $this->pastaRaizId = config('services.google.drive_folder_id');
    }

    public function criarPasta(string $nome, ?string $pastaRaizId = null): string
    {
        $metadata = new DriveFile([
            'name' => $nome,
            'mimeType' => 'application/vnd.google-apps.folder',
            'parents' => [$pastaRaizId ?? $this->pastaRaizId],
        ]);

        $pasta = $this->service->files->create($metadata, ['fields' => 'id']);
        return $pasta->id;
    }

    public function upload(string $pastaId, string $nomeArquivo, string $caminhoLocal, string $mimeType): array
    {
        $metadata = new DriveFile([
            'name' => $nomeArquivo,
            'parents' => [$pastaId],
        ]);

        $conteudo = file_get_contents($caminhoLocal);

        $arquivo = $this->service->files->create($metadata, [
            'data' => $conteudo,
            'mimeType' => $mimeType,
            'uploadType' => 'multipart',
            'fields' => 'id, thumbnailLink, size',
        ]);

        return [
            'id' => $arquivo->id,
            'thumbnailLink' => $arquivo->thumbnailLink ?? "https://drive.google.com/thumbnail?id={$arquivo->id}&sz=w400",
            'size' => $arquivo->size ?? 0,
        ];
    }

    public function deletar(string $arquivoId): void
    {
        $this->service->files->delete($arquivoId);
    }

    public function deletarPasta(string $pastaId): void
    {
        $this->service->files->delete($pastaId);
    }

    public function download(string $arquivoId)
    {
        $response = $this->service->files->get($arquivoId, ['alt' => 'media']);
        return $response->getBody();
    }

    public function obterArquivo(string $arquivoId): DriveFile
    {
        return $this->service->files->get($arquivoId, [
            'fields' => 'id, name, mimeType, size, thumbnailLink',
        ]);
    }
}
