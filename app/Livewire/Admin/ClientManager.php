<?php

namespace App\Livewire\Admin;

use App\Models\Cliente;
use App\Models\Trabalho;
use App\Models\TrabalhoCliente;
use Illuminate\Support\Str;
use Livewire\Component;

class ClientManager extends Component
{
    public int $trabalhoId;
    public string $telefone = '';
    public string $nome = '';
    public ?int $clienteExistenteId = null;
    public bool $clienteEncontrado = false;
    public bool $telefoneBuscado = false;
    public int $diasExpiracao = 30;
    public int $renovandoVinculoId = 0;
    public int $diasRenovacao = 30;

    public function buscarPorTelefone(): void
    {
        $this->clienteEncontrado = false;
        $this->clienteExistenteId = null;
        $this->nome = '';
        $this->telefoneBuscado = false;

        if (strlen(preg_replace('/\D/', '', $this->telefone)) < 10) return;

        $telefoneLimpo = preg_replace('/\D/', '', $this->telefone);

        $cliente = Cliente::whereRaw(
            "REGEXP_REPLACE(telefone, '[^0-9]', '') = ?",
            [$telefoneLimpo]
        )->first();

        $this->telefoneBuscado = true;

        if ($cliente) {
            $this->nome = $cliente->nome;
            $this->clienteExistenteId = $cliente->id;
            $this->clienteEncontrado = true;
        }
    }

    public function adicionar(): void
    {
        $this->validate([
            'telefone' => 'required|min:10',
            'nome' => 'required|string|max:255',
        ], [
            'telefone.required' => 'O telefone é obrigatório.',
            'telefone.min' => 'Telefone inválido.',
            'nome.required' => 'O nome do cliente é obrigatório.',
        ]);

        if ($this->clienteExistenteId) {
            $cliente = Cliente::find($this->clienteExistenteId);
            $cliente->update(['nome' => $this->nome]);
        } else {
            $cliente = Cliente::create([
                'nome' => $this->nome,
                'telefone' => $this->telefone,
            ]);
        }

        $trabalho = Trabalho::findOrFail($this->trabalhoId);

        if ($trabalho->clientes()->where('cliente_id', $cliente->id)->exists()) {
            $this->dispatch('notify', message: 'Este cliente já está vinculado a este trabalho.', type: 'error');
            return;
        }

        $token = Str::random(64);
        $expiraEm = now()->addDays($this->diasExpiracao);

        $trabalho->clientes()->attach($cliente->id, [
            'token' => $token,
            'expira_em' => $expiraEm,
            'status_link' => 'disponivel',
        ]);

        $this->telefone = '';
        $this->nome = '';
        $this->clienteExistenteId = null;
        $this->clienteEncontrado = false;
        $this->telefoneBuscado = false;

        $this->dispatch('notify', message: 'Cliente adicionado com sucesso!');
        $this->dispatch('trabalhoAtualizado');
    }

    public function abrirRenovacao(int $vinculoId): void
    {
        $this->renovandoVinculoId = $vinculoId;
        $this->diasRenovacao = 30;
    }

    public function cancelarRenovacao(): void
    {
        $this->renovandoVinculoId = 0;
        $this->diasRenovacao = 30;
    }

    public function renovar(): void
    {
        $this->validate([
            'diasRenovacao' => 'required|integer|min:1|max:365',
        ], [
            'diasRenovacao.min' => 'Informe pelo menos 1 dia.',
            'diasRenovacao.max' => 'Máximo de 365 dias.',
        ]);

        $vinculo = TrabalhoCliente::findOrFail($this->renovandoVinculoId);

        $vinculo->update([
            'expira_em'   => now()->addDays($this->diasRenovacao),
            'status_link' => 'disponivel',
        ]);

        $diasRenovados = $this->diasRenovacao;
        $this->cancelarRenovacao();
        $this->dispatch('notify', tipo: 'sucesso', mensagem: "Link renovado por {$diasRenovados} dias!");
    }

    public function remover(int $clienteId): void
    {
        $trabalho = Trabalho::findOrFail($this->trabalhoId);
        $trabalho->clientes()->detach($clienteId);
        $this->dispatch('notify', message: 'Cliente removido do trabalho.');
        $this->dispatch('trabalhoAtualizado');
    }

    public function render()
    {
        $clientesVinculados = Trabalho::findOrFail($this->trabalhoId)
            ->clientes()
            ->withPivot('token', 'expira_em', 'status_link', 'id')
            ->get();

        // Mapeia para TrabalhoCliente para usar métodos como estaExpirado()
        $vinculos = $clientesVinculados->map(function ($cliente) {
            $pivot = $cliente->pivot;
            $vinculo = \App\Models\TrabalhoCliente::find($pivot->id);
            if ($vinculo) {
                $vinculo->setRelation('cliente', $cliente);
            }
            return $vinculo;
        })->filter();

        return view('livewire.admin.client-manager', [
            'clientesVinculados' => $clientesVinculados,
            'vinculos' => $vinculos,
        ]);
    }
}
