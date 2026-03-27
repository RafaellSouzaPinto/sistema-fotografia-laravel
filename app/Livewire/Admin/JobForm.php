<?php

namespace App\Livewire\Admin;

use App\Models\Trabalho;
use App\Services\GoogleDriveService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('layouts.admin')]
class JobForm extends Component
{
    public ?int $trabalhoId = null;
    public string $titulo = '';
    public string $data_trabalho = '';
    public string $tipo = 'previa';
    public bool $salvo = false;
    public string $statusAtual = 'rascunho';

    public function mount($id = null): void
    {
        if ($id) {
            $trabalho = Trabalho::findOrFail($id);
            $this->trabalhoId = $trabalho->id;
            $this->titulo = $trabalho->titulo;
            $this->data_trabalho = $trabalho->data_trabalho->format('Y-m-d');
            $this->tipo = $trabalho->tipo;
            $this->statusAtual = $trabalho->status;
            $this->salvo = true;
        }
    }

    protected function rules(): array
    {
        return [
            'titulo' => 'required|string|max:255',
            'data_trabalho' => 'required|date',
            'tipo' => 'required|in:previa,completo',
        ];
    }

    protected function messages(): array
    {
        return [
            'titulo.required' => 'O nome do trabalho é obrigatório.',
            'data_trabalho.required' => 'A data do trabalho é obrigatória.',
            'data_trabalho.date' => 'Data inválida.',
            'tipo.required' => 'Selecione o tipo do trabalho.',
        ];
    }

    public function salvar(): void
    {
        $this->validate();

        if ($this->trabalhoId) {
            $trabalho = Trabalho::findOrFail($this->trabalhoId);
            $trabalho->update([
                'titulo' => $this->titulo,
                'data_trabalho' => $this->data_trabalho,
                'tipo' => $this->tipo,
            ]);
        } else {
            $trabalho = Trabalho::create([
                'titulo' => $this->titulo,
                'data_trabalho' => $this->data_trabalho,
                'tipo' => $this->tipo,
                'status' => 'rascunho',
            ]);
            $this->trabalhoId = $trabalho->id;
        }

        // Criar pasta no Google Drive se não existir
        if (!$trabalho->drive_pasta_id) {
            try {
                $driveService = app(GoogleDriveService::class);
                $nomePasta = $trabalho->titulo . ' - ' . ($trabalho->tipo === 'previa' ? 'Prévia' : 'Completo');
                $pastaId = $driveService->criarPasta($nomePasta);
                $trabalho->update(['drive_pasta_id' => $pastaId]);
            } catch (\Exception $e) {
                // Continua mesmo se Drive falhar
            }
        }

        $this->salvo = true;
        $this->statusAtual = $trabalho->status;

        $this->dispatch('notify', message: 'Trabalho salvo com sucesso!');
    }

    #[On('trabalhoAtualizado')]
    public function refreshTrabalho(): void
    {
        // Re-renderiza com dados frescos após sub-componentes atualizarem
    }

    public function publicar(): void
    {
        $trabalho = Trabalho::findOrFail($this->trabalhoId);

        if ($trabalho->fotos()->count() === 0 || $trabalho->clientes()->count() === 0) {
            $this->dispatch('notify', message: 'Adicione pelo menos 1 foto e 1 cliente antes de publicar.', type: 'error');
            return;
        }

        $trabalho->update(['status' => 'publicado']);
        $this->statusAtual = 'publicado';
        $this->dispatch('notify', message: 'Trabalho publicado! Links liberados para os clientes.');
    }

    public function render()
    {
        $trabalho = $this->trabalhoId ? Trabalho::withCount(['fotos', 'clientes'])->find($this->trabalhoId) : null;

        return view('livewire.admin.job-form', compact('trabalho'))
            ->title($this->trabalhoId ? 'Editar Trabalho' : 'Novo Trabalho');
    }
}
