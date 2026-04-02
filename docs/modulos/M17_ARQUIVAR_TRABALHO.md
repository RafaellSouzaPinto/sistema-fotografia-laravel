# M17 — Arquivar Trabalho (Baixar para HD + Finalizar)

## Propósito

Após os links de um trabalho expirarem, a Silvia precisa de um fluxo de encerramento: baixar todas as fotos em alta qualidade para backup no computador e marcar o trabalho como **finalizado**, removendo-o da fila ativa sem apagar os dados.

**Fluxo completo:**
```
Links expiram → Silvia vê badge "Expirado" no dashboard
→ Clica "Baixar para HD" → ZIP com fotos em alta qualidade
→ Clica "Finalizar trabalho" → status = 'finalizado'
→ Trabalho some da lista ativa → aparece no filtro "Finalizados"
```

---

## Arquivos Envolvidos

| Arquivo | Papel |
|---------|-------|
| `database/migrations/YYYY_MM_DD_arquivar_trabalho.php` | Adiciona `'finalizado'` ao enum `status` |
| `app/Models/Trabalho.php` | Método `estaFinalizado()`, scope `finalizado()` |
| `app/Http/Controllers/AdminJobController.php` | Download ZIP admin autenticado |
| `app/Livewire/Admin/JobList.php` | Action `finalizarTrabalho()`, filtro, badge |
| `routes/web.php` | Rota admin para download |
| `resources/views/livewire/admin/job-list.blade.php` | Botões, badge, filtro |

---

## Banco de Dados

### Migration — alterar enum status

```php
// database/migrations/YYYY_MM_DD_add_finalizado_to_trabalhos_table.php

public function up(): void
{
    DB::statement("ALTER TABLE trabalhos MODIFY COLUMN status ENUM('rascunho', 'publicado', 'finalizado') NOT NULL DEFAULT 'rascunho'");
}

public function down(): void
{
    DB::statement("ALTER TABLE trabalhos MODIFY COLUMN status ENUM('rascunho', 'publicado') NOT NULL DEFAULT 'rascunho'");
}
```

### Estrutura final do campo status

| Valor | Descrição |
|-------|-----------|
| `rascunho` | Em criação, sem clientes ou fotos suficientes |
| `publicado` | Links ativos para clientes |
| `finalizado` | Links expirados, fotos baixadas, trabalho encerrado |

---

## Model — Trabalho.php

```php
// Novo método: verificar se está finalizado
public function estaFinalizado(): bool
{
    return $this->status === 'finalizado';
}

// Scope para filtrar trabalhos finalizados
public function scopeFinalizado(Builder $query): Builder
{
    return $query->where('status', 'finalizado');
}

// Scope para trabalhos ativos (rascunho ou publicado)
public function scopeAtivo(Builder $query): Builder
{
    return $query->whereIn('status', ['rascunho', 'publicado']);
}
```

---

## Controller Admin — AdminJobController.php

Rota autenticada. A Silvia baixa todas as fotos do trabalho em alta qualidade (arquivo original para trabalhos completos, arquivo comprimido para prévias — melhor versão disponível).

```php
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
            return back()->with('erro', 'Este trabalho não tem fotos.');
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
                    // Pula foto se Drive falhar
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
```

---

## Rota Admin — routes/web.php

Dentro do grupo `middleware('auth')`:

```php
Route::get('/admin/jobs/{trabalho}/download-fotos', [AdminJobController::class, 'downloadFotos'])
    ->name('admin.jobs.download-fotos');
```

---

## Livewire — JobList.php

### Nova action: finalizarTrabalho

```php
public function finalizarTrabalho(int $id): void
{
    $trabalho = Trabalho::findOrFail($id);

    if ($trabalho->status !== 'publicado') {
        $this->dispatch('notify', tipo: 'erro', mensagem: 'Apenas trabalhos publicados podem ser finalizados.');
        return;
    }

    $trabalho->update(['status' => 'finalizado']);

    $this->dispatch('notify', tipo: 'sucesso', mensagem: "Trabalho \"{$trabalho->titulo}\" finalizado com sucesso.");
}
```

### Filtro atualizado

```php
// Adicionar opção 'finalizado' ao filtro existente
$query = match($this->filtroTipo) {
    'previa'      => Trabalho::where('tipo', 'previa')->whereIn('status', ['rascunho', 'publicado']),
    'completo'    => Trabalho::where('tipo', 'completo')->whereIn('status', ['rascunho', 'publicado']),
    'expirados'   => Trabalho::whereIn('status', ['rascunho', 'publicado'])
                        ->whereHas('clientes', fn($q) =>
                            $q->where('status_link', 'expirado')
                              ->orWhere('expira_em', '<', now())
                        ),
    'finalizados' => Trabalho::where('status', 'finalizado'),
    default       => Trabalho::whereIn('status', ['rascunho', 'publicado']),
};
```

---

## View — job-list.blade.php

### Badge "Finalizado"

```blade
@if($trabalho->status === 'finalizado')
    <span class="badge bg-secondary">Finalizado</span>
@elseif($trabalho->status === 'publicado')
    <span class="badge bg-success">Publicado</span>
@else
    <span class="badge bg-warning text-dark">Rascunho</span>
@endif
```

### Botões de arquivamento (visíveis apenas quando todos os links expiraram)

```blade
@if($trabalho->status === 'publicado' && $trabalho->todosLinksExpirados())
    <div class="d-flex gap-2 mt-2">
        {{-- Passo 1: Baixar fotos --}}
        <a href="{{ route('admin.jobs.download-fotos', $trabalho) }}"
           class="btn btn-outline-primary btn-sm"
           title="Baixar todas as fotos em alta qualidade">
            <i class="bi bi-download"></i> Baixar para HD
        </a>

        {{-- Passo 2: Finalizar --}}
        <button
            wire:click="finalizarTrabalho({{ $trabalho->id }})"
            wire:confirm="Marcar este trabalho como finalizado? Esta ação indica que as fotos foram salvas localmente."
            class="btn btn-outline-secondary btn-sm"
            title="Marcar trabalho como finalizado">
            <i class="bi bi-check-circle"></i> Finalizar
        </button>
    </div>
@endif
```

### Filtro no dashboard

```blade
<button wire:click="$set('filtroTipo', 'finalizados')"
        class="btn btn-sm {{ $filtroTipo === 'finalizados' ? 'btn-secondary' : 'btn-outline-secondary' }}">
    Finalizados
</button>
```

---

## Regras de Negócio

| Regra | Detalhe |
|-------|---------|
| Quem pode finalizar | Apenas trabalhos com `status = 'publicado'` |
| Quando os botões aparecem | `status = 'publicado'` E `todosLinksExpirados() = true` |
| Download inclui | Todas as fotos em `drive_arquivo_id` (original para completo, comprimido para prévia) |
| Trabalho finalizado | Não aparece no filtro padrão — apenas em "Finalizados" |
| Galeria pública | Links já expirados, cliente vê página de "expirado" normalmente |
| Ação destrutiva | Não — apenas muda status, não apaga dados |
| Reversível? | Sim, status pode ser alterado manualmente se necessário |

---

## Contexto Visual

```
DASHBOARD — card de trabalho com links expirados:

┌────────────────────────────────────────────────────────┐
│  Casamento João e Maria     [Completo] [Expirado]      │
│  12 fotos · 2 clientes · 15/03/2026                    │
│                                                        │
│  ⚠ Todos os links expiraram                           │
│                                                        │
│  [↓ Baixar para HD]  [✓ Finalizar]                    │
│                                       [Editar] [Lixo] │
└────────────────────────────────────────────────────────┘

Após finalizar:

┌────────────────────────────────────────────────────────┐
│  Casamento João e Maria     [Completo] [Finalizado]    │
│  12 fotos · 2 clientes · 15/03/2026                    │
│                                       [Editar] [Lixo] │
└────────────────────────────────────────────────────────┘
```
