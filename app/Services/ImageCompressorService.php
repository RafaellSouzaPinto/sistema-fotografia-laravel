<?php

declare(strict_types=1);

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ImageCompressorService
{
    private ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Comprime imagem para exibição web.
     * Reduz qualidade para 70% e limita largura a 1920px.
     * Retorna o caminho do arquivo comprimido.
     */
    public function comprimir(string $caminhoOriginal, string $caminhoDestino, int $qualidade = 70, int $larguraMaxima = 1920): string
    {
        $imagem = $this->manager->read($caminhoOriginal);

        // Redimensionar se maior que largura máxima (mantém proporção)
        $larguraAtual = $imagem->width();
        if ($larguraAtual > $larguraMaxima) {
            $imagem->scaleDown(width: $larguraMaxima);
        }

        // Salvar como JPG com qualidade reduzida
        $imagem->toJpeg($qualidade)->save($caminhoDestino);

        return $caminhoDestino;
    }

    /**
     * Gera thumbnail pequeno para grid da galeria.
     * 600x600px, qualidade 60%.
     */
    public function gerarThumbnail(string $caminhoOriginal, string $caminhoDestino, int $tamanho = 600, int $qualidade = 60): string
    {
        $imagem = $this->manager->read($caminhoOriginal);

        // Cover: redimensiona e corta pra ficar quadrado
        $imagem->cover($tamanho, $tamanho);

        $imagem->toJpeg($qualidade)->save($caminhoDestino);

        return $caminhoDestino;
    }

    /**
     * Verifica se o arquivo é uma imagem suportada para compressão.
     * PSD e TIF não são comprimidos — ficam como estão.
     */
    public function suportaCompressao(string $extensao): bool
    {
        return in_array(strtolower($extensao), ['jpg', 'jpeg', 'png', 'webp']);
    }
}
