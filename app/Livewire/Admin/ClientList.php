<?php

namespace App\Livewire\Admin;

use App\Models\Cliente;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Meus Clientes')]
class ClientList extends Component
{
    public string $busca = '';
    public ?int $editandoId = null;
    public string $editNome = '';
    public string $editTelefone = '';

    public function editar(int $id): void
    {
        $cliente = Cliente::findOrFail($id);
        $this->editandoId = $id;
        $this->editNome = $cliente->nome;
        $this->editTelefone = $cliente->telefone;
    }

    public function cancelarEdicao(): void
    {
        $this->editandoId = null;
        $this->editNome = '';
        $this->editTelefone = '';
    }

    public function salvarEdicao(): void
    {
        $this->validate([
            'editNome' => 'required|string|max:255',
            'editTelefone' => 'required|min:10',
        ], [
            'editNome.required' => 'O nome é obrigatório.',
            'editTelefone.required' => 'O telefone é obrigatório.',
        ]);

        Cliente::findOrFail($this->editandoId)->update([
            'nome' => $this->editNome,
            'telefone' => $this->editTelefone,
        ]);

        $this->editandoId = null;
        $this->dispatch('notify', message: 'Cliente atualizado com sucesso!');
    }

    public function excluir(int $id): void
    {
        Cliente::findOrFail($id)->delete();
        $this->dispatch('notify', message: 'Cliente excluído.');
    }

    public function render()
    {
        $clientes = Cliente::withCount('trabalhos')
            ->when($this->busca, fn($q) => $q->where('nome', 'like', "%{$this->busca}%")
                ->orWhere('telefone', 'like', "%{$this->busca}%"))
            ->orderBy('nome')
            ->get();

        return view('livewire.admin.client-list', compact('clientes'));
    }
}
