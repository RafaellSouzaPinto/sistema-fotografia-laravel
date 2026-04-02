<?php

namespace App\Livewire\Admin;

use App\Models\Cliente;
use App\Models\Foto;
use App\Models\Trabalho;
use App\Models\TrabalhoCliente;
use App\Services\GoogleDriveService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

#[Layout('layouts.admin')]
#[Title('Meus Trabalhos')]
class JobList extends Component
{
    public string $busca = '';
    public string $filtroTipo = 'todos';

    // Modal de renovação de links
    public int $renovarTrabalhoId = 0;
    public int $diasRenovacao = 30;

    public function abrirModalRenovar(int $trabalhoId): void
    {
        $this->renovarTrabalhoId = $trabalhoId;
        $this->diasRenovacao = 30;
        $this->dispatch('abrirModalRenovar');
    }

    public function renovarTodosExpirados(): void
    {
        $this->validate([
            'diasRenovacao' => 'required|integer|min:1|max:365',
        ], [
            'diasRenovacao.min' => 'Informe pelo menos 1 dia.',
            'diasRenovacao.max' => 'Máximo de 365 dias.',
        ]);

        $renovados = TrabalhoCliente::where('trabalho_id', $this->renovarTrabalhoId)
            ->where(function ($q) {
                $q->where('status_link', 'expirado')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('expira_em')
                         ->where('expira_em', '<', now());
                  });
            })
            ->update([
                'status_link' => 'disponivel',
                'expira_em'   => now()->addDays($this->diasRenovacao),
            ]);

        $this->renovarTrabalhoId = 0;
        $this->js("bootstrap.Modal.getInstance(document.getElementById('modal-renovar-links'))?.hide()");
        $this->dispatch('notify', message: "{$renovados} " . ($renovados === 1 ? 'link renovado' : 'links renovados') . " por {$this->diasRenovacao} dias!");
    }

    public function getVinculosExpiradosProperty()
    {
        if (!$this->renovarTrabalhoId) return collect();

        return TrabalhoCliente::with('cliente')
            ->where('trabalho_id', $this->renovarTrabalhoId)
            ->where(function ($q) {
                $q->where('status_link', 'expirado')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('expira_em')
                         ->where('expira_em', '<', now());
                  });
            })
            ->get();
    }

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

    public function excluir(int $id): void
    {
        $trabalho = Trabalho::findOrFail($id);

        if ($trabalho->drive_pasta_id) {
            try {
                $driveService = app(GoogleDriveService::class);
                $driveService->deletarPasta($trabalho->drive_pasta_id);
            } catch (\Exception $e) {
                // Continua mesmo se falhar no Drive
            }
        }

        $trabalho->fotos()->delete();
        $trabalho->clientes()->detach();
        $trabalho->delete();

        $this->dispatch('notify', message: 'Trabalho excluído com sucesso.');
    }

    public function liberarEspaco(int $id): void
    {
        $trabalho = Trabalho::with('fotos')->findOrFail($id);

        // Deletar pasta do Drive
        if ($trabalho->drive_pasta_id) {
            try {
                $driveService = app(GoogleDriveService::class);
                $driveService->deletarPasta($trabalho->drive_pasta_id);
            } catch (\Exception $e) {
                // Se Drive falhar, deletar local
            }
        }

        // Deletar fotos locais
        foreach ($trabalho->fotos as $foto) {
            \Storage::disk('public')->delete($foto->drive_arquivo_id);
        }

        // Deletar fotos do banco
        $trabalho->fotos()->delete();

        // Limpar referência do Drive
        $trabalho->update(['drive_pasta_id' => null]);

        $this->dispatch('notify', message: 'Espaço liberado! Fotos removidas do Drive.');
    }

    public function render()
    {
        $query = Trabalho::withCount(['fotos', 'clientes'])
            ->with([
                'clientes' => function ($q) {
                    $q->withPivot('status_link', 'expira_em');
                },
                'fotos' => fn($q) => $q->orderBy('ordem')->limit(1),
            ]);

        if ($this->busca) {
            $query->where('titulo', 'like', "%{$this->busca}%");
        }

        if ($this->filtroTipo === 'finalizados') {
            $query->where('status', 'finalizado');
        } elseif ($this->filtroTipo === 'expirados') {
            $query->whereIn('status', ['rascunho', 'publicado'])
                ->whereHas('clientes', function ($q) {
                    $q->where(function ($q2) {
                        $q2->where('trabalho_cliente.status_link', 'expirado')
                           ->orWhere(function ($q3) {
                               $q3->whereNotNull('trabalho_cliente.expira_em')
                                  ->where('trabalho_cliente.expira_em', '<', now());
                           });
                    });
                });
        } elseif ($this->filtroTipo === 'todos') {
            $query->whereIn('status', ['rascunho', 'publicado']);
        } else {
            // previa ou completo — exclui finalizados
            $query->where('tipo', $this->filtroTipo)
                  ->whereIn('status', ['rascunho', 'publicado']);
        }

        $trabalhos = $query->orderBy('created_at', 'desc')->get();

        $totalPublicados       = Trabalho::where('status', 'publicado')->count();
        $totalClientes         = Cliente::count();
        $totalFotos            = Foto::count();
        $linksExpirandoEmBreve = TrabalhoCliente::where('status_link', 'disponivel')
            ->whereNotNull('expira_em')
            ->where('expira_em', '<=', now()->addDays(7))
            ->where('expira_em', '>', now())
            ->count();

        return view('livewire.admin.job-list', compact(
            'trabalhos',
            'totalPublicados',
            'totalClientes',
            'totalFotos',
            'linksExpirandoEmBreve'
        ));
    }
}
