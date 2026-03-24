<?php

namespace App\Livewire\Admin;

use App\Models\Trabalho;
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
            ->with(['clientes' => function ($q) {
                $q->withPivot('status_link', 'expira_em');
            }]);

        if ($this->busca) {
            $query->where('titulo', 'like', "%{$this->busca}%");
        }

        if ($this->filtroTipo === 'expirados') {
            $query->whereHas('clientes', function ($q) {
                $q->where(function ($q2) {
                    $q2->where('trabalho_cliente.status_link', 'expirado')
                       ->orWhere(function ($q3) {
                           $q3->whereNotNull('trabalho_cliente.expira_em')
                              ->where('trabalho_cliente.expira_em', '<', now());
                       });
                });
            });
        } elseif ($this->filtroTipo !== 'todos') {
            $query->where('tipo', $this->filtroTipo);
        }

        $trabalhos = $query->orderBy('created_at', 'desc')->get();

        return view('livewire.admin.job-list', compact('trabalhos'));
    }
}
