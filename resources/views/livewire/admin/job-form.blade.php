<div>
    <!-- Link de retorno -->
    <a href="{{ route('admin.dashboard') }}" style="display: inline-flex; align-items: center; gap: 6px; color: #c27a8e; text-decoration: none; font-size: 14px; margin-bottom: 16px;">
        <i class="bi bi-arrow-left"></i> Voltar para Meus Trabalhos
    </a>

    <!-- Título da página -->
    <h1 style="font-family: 'Playfair Display', serif; font-weight: 700; font-size: 28px; color: #4a2c3d; margin-bottom: 24px;">
        {{ $this->trabalhoId ? 'Editar Trabalho' : 'Novo Trabalho' }}
    </h1>

    <!-- Seção 1: Informações do Trabalho -->
    <div class="card-rosa mb-4">
        <h2 style="font-family: 'Playfair Display', serif; font-style: italic; font-weight: 600; font-size: 20px; color: #4a2c3d; margin-bottom: 20px;">Informações do Trabalho</h2>

        <!-- Nome -->
        <div class="mb-3">
            <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 6px;">Nome do trabalho</label>
            <input type="text" wire:model="titulo" class="input-rosa" placeholder="Ex: Casamento Ana e João">
            @error('titulo') <span style="color: #c0392b; font-size: 13px;">{{ $message }}</span> @enderror
        </div>

        <!-- Data -->
        <div class="mb-3">
            <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 6px;">Data do trabalho</label>
            <input type="date" wire:model="data_trabalho" class="input-rosa">
            @error('data_trabalho') <span style="color: #c0392b; font-size: 13px;">{{ $message }}</span> @enderror
        </div>

        <!-- Tipo -->
        <div class="mb-4">
            <label style="font-family: 'Inter', sans-serif; font-weight: 500; font-size: 14px; color: #4a2c3d; display: block; margin-bottom: 10px;">Tipo</label>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <label class="radio-tipo-card {{ $tipo === 'previa' ? 'selected' : '' }}">
                    <input type="radio" wire:model="tipo" value="previa" style="accent-color: #c27a8e;">
                    <div>
                        <div style="font-weight: 500; font-size: 15px; color: #4a2c3d;">Prévia</div>
                        <div style="font-size: 13px; color: #8c6b7d;">Amostra de fotos</div>
                    </div>
                </label>
                <label class="radio-tipo-card {{ $tipo === 'completo' ? 'selected' : '' }}">
                    <input type="radio" wire:model="tipo" value="completo" style="accent-color: #c27a8e;">
                    <div>
                        <div style="font-weight: 500; font-size: 15px; color: #4a2c3d;">Trabalho Completo</div>
                        <div style="font-size: 13px; color: #8c6b7d;">Entrega final</div>
                    </div>
                </label>
            </div>
        </div>

        <button wire:click="salvar" class="btn-rosa" wire:loading.attr="disabled" wire:loading.class="opacity-75">
            <span wire:loading.remove>Salvar alterações</span>
            <span wire:loading>Salvando...</span>
        </button>
    </div>

    @if($salvo && $trabalhoId)
        <!-- Seção 2: Clientes -->
        @livewire('admin.client-manager', ['trabalhoId' => $trabalhoId], key('client-manager-'.$trabalhoId))

        <!-- Seção 3: Fotos -->
        @livewire('admin.photo-uploader', ['trabalhoId' => $trabalhoId], key('photo-uploader-'.$trabalhoId))

        <!-- Seção 4: Publicar -->
        @if($trabalho && $trabalho->fotos_count > 0 && $trabalho->clientes_count > 0)
            <div class="card-rosa mt-4">
                @if($statusAtual === 'publicado')
                    <button class="btn-publicar btn-publicado" disabled>
                        ✓ Trabalho publicado
                    </button>
                @else
                    <button wire:click="publicar" class="btn-publicar">
                        Publicar trabalho e liberar links
                    </button>
                @endif
                <p style="text-align: center; font-size: 14px; color: #8c6b7d; margin-top: 8px; margin-bottom: 0;">
                    Após publicar, os clientes poderão acessar as fotos pelos links gerados
                </p>
            </div>
        @endif
    @endif
</div>
