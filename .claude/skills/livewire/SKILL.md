# Livewire 3

- Componentes admin em: app/Livewire/Admin/
- wire:model → binding
- wire:model.live.debounce.300ms → busca tempo real
- wire:model.blur → ação ao sair do campo (busca telefone)
- wire:click → botões
- wire:confirm="Mensagem em português" → ações destrutivas
- wire:navigate → links internos SPA-like
- wire:loading → feedback carregamento
- wire:loading.class="opacity-50" → visual loading
- $dispatch('notify', message: 'Texto') → toast
- Upload: wire:model="arquivos" + input file multiple
- Validação: $this->validate() com $rules
- Layout: ->layout('layouts.admin') no render()
- Máscara telefone: Alpine.js x-mask="(99) 99999-9999"
- NÃO usar wire:model.defer (deprecated Livewire 3)
- NÃO usar wire:model.lazy (usar wire:model.blur)
