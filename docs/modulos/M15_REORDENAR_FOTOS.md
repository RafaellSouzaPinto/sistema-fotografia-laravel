# M15 — Reordenar Fotos via Drag and Drop

## Propósito

Na tela de edição do trabalho, a Silvia pode arrastar as fotos para mudar a ordem em que aparecem na galeria do cliente. A ordem é salva na coluna `ordem` (int) da tabela `fotos`. O drag and drop usa a biblioteca **SortableJS** (CDN, sem npm).

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Livewire/Admin/PhotoUploader.php` | Método `reordenar()` |
| `resources/views/livewire/admin/photo-uploader.blade.php` | UI drag and drop com SortableJS |
| `resources/views/layouts/admin.blade.php` | Incluir SortableJS via CDN |

Nenhuma migration necessária — coluna `ordem` já existe na tabela `fotos`.

## Incluir SortableJS no layout admin

```blade
{{-- resources/views/layouts/admin.blade.php — antes do </body> --}}
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
```

## Método no PhotoUploader

```php
// app/Livewire/Admin/PhotoUploader.php

/**
 * Recebe array de IDs na nova ordem e atualiza a coluna `ordem`.
 * Chamado pelo JavaScript via $wire.reordenar(ids)
 *
 * @param array<int> $ids IDs das fotos na nova ordem
 */
public function reordenar(array $ids): void
{
    foreach ($ids as $posicao => $fotoId) {
        Foto::where('id', $fotoId)
            ->where('trabalho_id', $this->trabalhoId) // segurança: só fotos deste trabalho
            ->update(['ordem' => $posicao + 1]);
    }

    $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Ordem das fotos salva!');
}
```

## View — grid de fotos com drag and drop

```blade
{{-- resources/views/livewire/admin/photo-uploader.blade.php --}}

{{-- Grid de thumbnails com drag and drop --}}
<div id="fotos-sortable-{{ $trabalhoId }}"
     class="row g-2 mt-2"
     wire:ignore>
    @foreach($this->fotosDoTrabalho as $foto)
    <div class="col-4 col-md-3 col-lg-2 foto-item"
         data-id="{{ $foto->id }}"
         style="cursor: grab">
        <div class="position-relative rounded overflow-hidden border"
             style="background:#fce4ec">

            {{-- Thumbnail --}}
            @if($foto->caminho_thumbnail)
                <img src="{{ asset('storage/' . $foto->caminho_thumbnail) }}"
                     alt="{{ $foto->nome_arquivo }}"
                     loading="lazy"
                     style="width:100%; aspect-ratio:1/1; object-fit:cover; display:block">
            @elseif($foto->drive_thumbnail)
                <img src="{{ $foto->drive_thumbnail }}"
                     alt="{{ $foto->nome_arquivo }}"
                     loading="lazy"
                     style="width:100%; aspect-ratio:1/1; object-fit:cover; display:block">
            @else
                <div class="d-flex align-items-center justify-content-center"
                     style="width:100%; aspect-ratio:1/1; background:#fce4ec">
                    <i class="bi bi-image" style="color:#c27a8e; font-size:1.5rem"></i>
                </div>
            @endif

            {{-- Ícone de arrastar (canto superior esquerdo) --}}
            <div class="position-absolute top-0 start-0 p-1"
                 style="background:rgba(74,44,61,0.5); border-radius:0 0 6px 0; cursor:grab">
                <i class="bi bi-grip-vertical text-white" style="font-size:0.75rem"></i>
            </div>

            {{-- Botão remover (canto superior direito) --}}
            <button wire:click="removerFoto({{ $foto->id }})"
                wire:confirm="Remover a foto {{ $foto->nome_arquivo }}?"
                class="position-absolute top-0 end-0 m-1 btn btn-danger btn-sm p-0 d-flex align-items-center justify-content-center"
                style="width:24px; height:24px; border-radius:50%">
                <i class="bi bi-x" style="font-size:0.75rem"></i>
            </button>

            {{-- Nome do arquivo (tooltip no hover) --}}
            <div class="position-absolute bottom-0 start-0 end-0 px-1 py-1 text-truncate"
                 style="background:rgba(74,44,61,0.6); color:#fff; font-size:0.65rem">
                {{ $foto->nome_arquivo }}
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Script SortableJS --}}
<script>
document.addEventListener('livewire:initialized', () => {
    iniciarSortable();
});

document.addEventListener('loteProcessado', () => {
    // Reinicializa após upload de novas fotos
    setTimeout(iniciarSortable, 500);
});

function iniciarSortable() {
    const el = document.getElementById('fotos-sortable-{{ $trabalhoId }}');
    if (!el) return;

    new Sortable(el, {
        animation: 150,
        ghostClass: 'sortable-ghost',
        dragClass: 'sortable-drag',
        handle: '.foto-item',
        onEnd: function () {
            const ids = [...el.querySelectorAll('.foto-item[data-id]')]
                .map(item => parseInt(item.dataset.id));

            @this.reordenar(ids);
        }
    });
}
</script>

{{-- Estilos do drag and drop --}}
<style>
.sortable-ghost {
    opacity: 0.4;
    background: #fce4ec;
}
.sortable-drag {
    opacity: 0.9;
    transform: scale(1.05);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}
.foto-item[data-id]:active {
    cursor: grabbing;
}
</style>
```

## Computed property fotosDoTrabalho no PhotoUploader

```php
// app/Livewire/Admin/PhotoUploader.php

use Livewire\Attributes\Computed;

#[Computed]
public function fotosDoTrabalho()
{
    return Foto::where('trabalho_id', $this->trabalhoId)
        ->orderBy('ordem')
        ->get();
}
```

## Fluxo de interação

```
1. Silvia vê grid de thumbnails com ícone de arrastar (≡) em cada foto
2. Arrasta uma foto para nova posição
3. SortableJS reposiciona visualmente (sem reload)
4. onEnd → coleta IDs na nova ordem → chama $wire.reordenar(ids)
5. Livewire executa reordenar() → UPDATE em cada foto com nova posição
6. Toast "Ordem das fotos salva!" aparece
7. Na galeria pública, o cliente vê as fotos na nova ordem
```

## Segurança

- O método `reordenar()` filtra por `trabalho_id` além do `id` da foto — impede que uma requisição maliciosa reordene fotos de outro trabalho
- O array de IDs vem do DOM (Alpine/JS) — é validado implicitamente pela query com `where('trabalho_id', $this->trabalhoId)`

## Regras

- Drag and drop usa `wire:ignore` no container para evitar que Livewire destrua o Sortable ao re-renderizar
- `iniciarSortable()` é chamado novamente no evento `loteProcessado` (disparado após upload) para registrar as novas fotos no Sortable
- A ordem começa em `1` (não `0`) para consistência com o restante do sistema
- O método atualiza todas as fotos do array — não só as movidas — para garantir que a sequência fique sem gaps
