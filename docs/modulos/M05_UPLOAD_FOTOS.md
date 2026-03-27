# M05 — Upload de Fotos

## Propósito

Silvia faz upload de fotos diretamente na tela de edição do trabalho. O sistema processa as fotos em lotes, comprime conforme o tipo do trabalho, e tenta enviar ao Google Drive (com fallback para storage local).

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Livewire/Admin/PhotoUploader.php` | Lógica de upload e processamento |
| `resources/views/livewire/admin/photo-uploader.blade.php` | UI de upload |
| `app/Services/GoogleDriveService.php` | Envio ao Drive |
| `app/Services/ImageCompressorService.php` | Compressão e thumbnails |
| `app/Models/Foto.php` | Model de foto |
| `database/migrations/2026_03_17_221505_create_fotos_table.php` | Estrutura base |
| `database/migrations/2026_03_24_000001_add_caminho_thumbnail_to_fotos_table.php` | Adiciona thumbnail local |

## Tabela `fotos`

```sql
id                 bigint unsigned, PK
trabalho_id        bigint unsigned, FK → trabalhos (cascadeOnDelete)
nome_arquivo       varchar(255)
drive_arquivo_id   varchar(255), nullable    ← ID no Google Drive
drive_thumbnail    text, nullable            ← URL de thumbnail do Drive
caminho_thumbnail  varchar(255), nullable    ← caminho local do thumbnail
tamanho_bytes      bigint
ordem              int
created_at         timestamp
updated_at         timestamp
deleted_at         timestamp, nullable       ← SoftDeletes
```

## Model Foto

```php
class Foto extends Model
{
    use SoftDeletes;

    protected $table = 'fotos';
    protected $fillable = [
        'trabalho_id', 'nome_arquivo', 'drive_arquivo_id',
        'drive_thumbnail', 'caminho_thumbnail', 'tamanho_bytes', 'ordem'
    ];

    public function trabalho(): BelongsTo
    {
        return $this->belongsTo(Trabalho::class);
    }
}
```

## Componente PhotoUploader

**Arquivo**: `app/Livewire/Admin/PhotoUploader.php`

```php
class PhotoUploader extends Component
{
    use WithFileUploads;

    public int $trabalhoId;
    public array $arquivos = [];

    // Regras de validação por arquivo
    protected function rules(): array
    {
        return [
            'arquivos.*' => 'file|mimes:jpg,jpeg,png,psd,tif,tiff|max:204800', // 200MB
        ];
    }

    // Disparado automaticamente ao selecionar arquivos
    public function updatedArquivos(): void
    {
        $this->validate();
        $this->processarLote();
    }

    private function processarLote(): void
    {
        $trabalho = Trabalho::findOrFail($this->trabalhoId);
        $drive = app(GoogleDriveService::class);
        $compressor = app(ImageCompressorService::class);
        $ultimaOrdem = Foto::where('trabalho_id', $this->trabalhoId)->max('ordem') ?? 0;

        foreach ($this->arquivos as $arquivo) {
            $ultimaOrdem++;
            $nomeOriginal = $arquivo->getClientOriginalName();
            $extensao = strtolower($arquivo->getClientOriginalExtension());
            $caminhoTemp = $arquivo->getRealPath();

            // Gera thumbnail local
            $nomeThumbnail = 'thumb_' . uniqid() . '.jpg';
            $caminhoThumb = storage_path("app/public/thumbnails/{$nomeThumbnail}");
            $compressor->gerarThumbnail($caminhoTemp, $caminhoThumb);

            // Para prévia: comprime imagem antes de enviar
            // Para completo: envia original
            if ($trabalho->tipo === 'previa' && $compressor->suportaCompressao($extensao)) {
                $nomeComprimido = 'prev_' . uniqid() . '.jpg';
                $caminhoEnvio = storage_path("app/private/{$nomeComprimido}");
                $compressor->comprimir($caminhoTemp, $caminhoEnvio);
            } else {
                $caminhoEnvio = $caminhoTemp;
                $nomeComprimido = $nomeOriginal;
            }

            $driveId = null;
            $driveThumbnail = null;

            // Tenta Google Drive
            try {
                $resultado = $drive->upload(
                    $trabalho->drive_pasta_id,
                    $nomeComprimido,
                    $caminhoEnvio,
                    'image/jpeg'
                );
                $driveId = $resultado['id'];
                $driveThumbnail = $resultado['thumbnailLink'] ?? null;
            } catch (\Exception $e) {
                // Fallback: mantém arquivo no storage local
                $nomeLocal = uniqid() . '_' . $nomeOriginal;
                Storage::disk('public')->putFileAs(
                    "fotos/{$this->trabalhoId}",
                    $arquivo,
                    $nomeLocal
                );
            }

            // Salva thumbnail no storage público
            $caminhoThumbnailRelativo = "thumbnails/{$nomeThumbnail}";

            Foto::create([
                'trabalho_id'       => $this->trabalhoId,
                'nome_arquivo'      => $nomeOriginal,
                'drive_arquivo_id'  => $driveId,
                'drive_thumbnail'   => $driveThumbnail,
                'caminho_thumbnail' => $caminhoThumbnailRelativo,
                'tamanho_bytes'     => $arquivo->getSize(),
                'ordem'             => $ultimaOrdem,
            ]);
        }

        $this->arquivos = [];
        $this->dispatch('loteProcessado');
        $this->dispatch('notify', tipo: 'sucesso', mensagem: count($this->arquivos) . ' fotos enviadas!');
    }

    public function removerFoto(int $fotoId): void
    {
        $foto = Foto::findOrFail($fotoId);

        // Remove do Drive
        if ($foto->drive_arquivo_id) {
            app(GoogleDriveService::class)->deletar($foto->drive_arquivo_id);
        }

        // Remove thumbnail local
        if ($foto->caminho_thumbnail) {
            Storage::disk('public')->delete($foto->caminho_thumbnail);
        }

        $foto->delete();
        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Foto removida!');
    }
}
```

## Formatos aceitos

| Extensão | Compressão | Thumbnail |
|----------|-----------|-----------|
| jpg, jpeg | Sim (prévia) | Sim |
| png | Sim (prévia) | Sim |
| psd | Não | Sim (se GD suportar) |
| tif, tiff | Não | Sim (se GD suportar) |

## Lógica por tipo de trabalho

| Tipo do trabalho | Arquivo enviado ao Drive | Thumbnail |
|-----------------|-------------------------|-----------|
| `previa` | Comprimido (qualidade 70%, máx 1920px) | 600x600px |
| `completo` | Original sem alteração | 600x600px |

## Limites de upload

- Máximo por arquivo: **200MB**
- Configurado em:
  - `php.ini` (raiz): `upload_max_filesize=200M`, `post_max_size=250M`
  - `config/livewire.php`: `'max_upload_size' => 204800` (em KB)
  - Validação Livewire: `max:204800`

## Layout do PhotoUploader

```
┌─────────────────────────────────────────────────────┐
│ FOTOS (12 fotos)                                    │
│                                                     │
│  ┌──────────────────────────────────────────────┐   │
│  │                                              │   │
│  │   📁 Arraste fotos aqui ou clique para       │   │
│  │      selecionar                              │   │
│  │   JPG, PNG, PSD, TIF — até 200MB cada        │   │
│  │                                              │   │
│  └──────────────────────────────────────────────┘   │
│                                                     │
│  [thumb] [thumb] [thumb] [thumb] [thumb]            │
│  [×]     [×]     [×]     [×]     [×]               │
│  foto1   foto2   foto3   foto4   foto5              │
└─────────────────────────────────────────────────────┘
```

## Thumbnail na galeria

1. Se `caminho_thumbnail` preenchido → exibe via `/admin/thumbnail/{foto}` (rota proxy)
2. Se `drive_thumbnail` preenchido → exibe URL direta do Drive
3. Sem thumbnail → placeholder (ícone câmera)

## Rota proxy de thumbnail

```php
Route::get('/admin/thumbnail/{foto}', function (Foto $foto) {
    if ($foto->caminho_thumbnail && Storage::disk('public')->exists($foto->caminho_thumbnail)) {
        return response()->file(Storage::disk('public')->path($foto->caminho_thumbnail));
    }
    // Fallback para drive_thumbnail ou 404
})->middleware('auth');
```
