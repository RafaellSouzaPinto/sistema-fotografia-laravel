# Livewire Components — Contexto para Claude Code

## Componentes existentes

### JobList (dashboard — /admin/dashboard)
- Lista trabalhos em cards com busca e filtro (todos/prévias/completos)
- withCount('fotos', 'clientes') para contadores
- Excluir com wire:confirm

### JobForm (novo/editar — /admin/jobs/create e /admin/jobs/{id}/edit)
- Campos: titulo, data_trabalho, tipo (radio)
- Após salvar: mostra seções ClientManager e PhotoUploader
- Botão publicar (verde) quando tem ≥1 foto e ≥1 cliente

### ClientManager (dentro do JobForm)
- Campo telefone com máscara (99) 99999-9999
- wire:model.blur no telefone → busca no banco
- Encontrou: preenche nome + borda verde + "Cliente encontrado!"
- Não encontrou: campo nome vazio + "Novo cliente"
- Gera token Str::random(64) ao vincular
- Exibe link copiável por cliente
- Botão copiar link com Alpine.js (clipboard)

### PhotoUploader (dentro do JobForm)
- Upload múltiplo com drag & drop
- Validação: mimes jpg,jpeg,png,psd,tif,tiff | max:204800 (200MB)
- Grid de thumbnails com botão X para remover
- Salva em storage local ou Google Drive

### ClientList (/admin/clients)
- Lista todos os clientes com busca
- Edição inline (nome + telefone)
- Contagem de trabalhos vinculados

## Padrões
- Todos usam ->layout('layouts.admin')
- Toast via $dispatch('notify', message: 'texto')
- Confirmação via wire:confirm="mensagem em português"
- Busca com wire:model.live.debounce.300ms
