<?php

namespace App\Livewire\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class AlterarSenha extends Component
{
    public string $senhaAtual = '';
    public string $novaSenha = '';
    public string $confirmacaoSenha = '';

    protected function rules(): array
    {
        return [
            'senhaAtual'       => 'required',
            'novaSenha'        => 'required|min:6',
            'confirmacaoSenha' => 'required|same:novaSenha',
        ];
    }

    protected function messages(): array
    {
        return [
            'senhaAtual.required'       => 'Informe a senha atual.',
            'novaSenha.required'        => 'Informe a nova senha.',
            'novaSenha.min'             => 'A nova senha deve ter pelo menos 6 caracteres.',
            'confirmacaoSenha.required' => 'Confirme a nova senha.',
            'confirmacaoSenha.same'     => 'As senhas não coincidem.',
        ];
    }

    public function salvar(): void
    {
        $this->validate();

        $usuario = Auth::user();

        // Verifica se a senha atual está correta
        // NUNCA usar Auth::attempt() — coluna é 'senha', não 'password'
        if (!Hash::check($this->senhaAtual, $usuario->senha)) {
            $this->addError('senhaAtual', 'Senha atual incorreta.');
            return;
        }

        $usuario->update([
            'senha' => Hash::make($this->novaSenha),
        ]);

        // Limpa os campos
        $this->reset(['senhaAtual', 'novaSenha', 'confirmacaoSenha']);

        $this->dispatch('notify', tipo: 'sucesso', mensagem: 'Senha alterada com sucesso!');
    }

    public function render()
    {
        return view('livewire.admin.alterar-senha')
            ->layout('layouts.admin', ['titulo' => 'Meu Perfil']);
    }
}
