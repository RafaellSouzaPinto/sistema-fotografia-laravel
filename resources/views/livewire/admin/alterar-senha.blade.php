<div class="container py-4" style="max-width:560px">

    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm mb-3">
            <i class="bi bi-arrow-left me-1"></i> Voltar
        </a>
        <h1 style="font-family:'Playfair Display',serif; color:#4a2c3d">
            <i class="bi bi-person-circle me-2" style="color:#c27a8e"></i>
            Meu Perfil
        </h1>
        <p class="text-secondary">{{ auth()->user()->nome }}</p>
    </div>

    {{-- Card do formulário --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px">
        <div class="card-body p-4">
            <h5 class="mb-4" style="color:#4a2c3d">Alterar Senha</h5>

            <div class="mb-3">
                <label class="form-label fw-semibold">Senha atual</label>
                <input type="password"
                       wire:model="senhaAtual"
                       class="form-control form-control-lg @error('senhaAtual') is-invalid @enderror"
                       placeholder="Digite sua senha atual">
                @error('senhaAtual')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Nova senha</label>
                <input type="password"
                       wire:model="novaSenha"
                       class="form-control form-control-lg @error('novaSenha') is-invalid @enderror"
                       placeholder="Mínimo 6 caracteres">
                @error('novaSenha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Confirmar nova senha</label>
                <input type="password"
                       wire:model="confirmacaoSenha"
                       class="form-control form-control-lg @error('confirmacaoSenha') is-invalid @enderror"
                       placeholder="Repita a nova senha">
                @error('confirmacaoSenha')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button wire:click="salvar"
                    wire:loading.attr="disabled"
                    class="btn btn-lg w-100"
                    style="background:#c27a8e; color:#fff; border:none; border-radius:8px; font-weight:600">
                <span wire:loading.remove>Salvar nova senha</span>
                <span wire:loading>Salvando...</span>
            </button>
        </div>
    </div>

    {{-- Informação extra --}}
    <p class="text-center text-secondary small mt-3">
        <i class="bi bi-shield-lock me-1"></i>
        A senha é armazenada de forma segura (criptografada)
    </p>
</div>
