<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Usuario;
use App\Models\Trabalho;
use App\Models\Cliente;
use App\Models\Foto;
use App\Services\GoogleDriveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use App\Livewire\Admin\JobList;

class ArquivarTrabalhoTest extends TestCase
{
    use RefreshDatabase;

    protected Usuario $silvia;
    protected Trabalho $trabalho;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        $this->silvia = Usuario::create([
            'nome'  => 'Silvia Souza',
            'email' => 'silviasouzafotografa@gmail.com',
            'senha' => bcrypt('123456'),
        ]);

        $this->trabalho = Trabalho::create([
            'titulo'         => 'Casamento Teste',
            'data_trabalho'  => '2026-03-01',
            'tipo'           => 'completo',
            'status'         => 'publicado',
            'drive_pasta_id' => null,
        ]);

        $this->cliente = Cliente::create([
            'nome'     => 'Maria Silva',
            'telefone' => '11999990000',
        ]);

        // Vinculo expirado
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token'       => Str::random(64),
            'expira_em'   => now()->subDays(5),
            'status_link' => 'expirado',
        ]);

        Storage::fake('public');
    }

    // ===================================================
    // DOWNLOAD ADMIN — rota autenticada
    // ===================================================

    public function test_usuario_nao_autenticado_nao_pode_baixar_fotos(): void
    {
        $response = $this->get(route('admin.jobs.download-fotos', $this->trabalho));

        $response->assertRedirect(route('login'));
    }

    public function test_download_gera_zip_com_fotos_locais(): void
    {
        // Cria arquivo falso no storage
        Storage::disk('public')->put('fotos/1/originais/foto1.jpg', 'conteudo-fake-foto-1');
        Storage::disk('public')->put('fotos/1/originais/foto2.jpg', 'conteudo-fake-foto-2');

        Foto::create([
            'trabalho_id'     => $this->trabalho->id,
            'nome_arquivo'    => 'foto1.jpg',
            'drive_arquivo_id'=> 'fotos/1/originais/foto1.jpg',
            'tamanho_bytes'   => 1024,
            'ordem'           => 0,
        ]);

        Foto::create([
            'trabalho_id'     => $this->trabalho->id,
            'nome_arquivo'    => 'foto2.jpg',
            'drive_arquivo_id'=> 'fotos/1/originais/foto2.jpg',
            'tamanho_bytes'   => 2048,
            'ordem'           => 1,
        ]);

        $response = $this->actingAs($this->silvia, 'web')
            ->get(route('admin.jobs.download-fotos', $this->trabalho));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    public function test_download_trabalho_sem_fotos_retorna_redirecionamento(): void
    {
        $response = $this->actingAs($this->silvia, 'web')
            ->get(route('admin.jobs.download-fotos', $this->trabalho));

        $response->assertRedirect();
        $response->assertSessionHas('erro');
    }

    public function test_download_trabalho_inexistente_retorna_404(): void
    {
        $response = $this->actingAs($this->silvia, 'web')
            ->get(route('admin.jobs.download-fotos', ['trabalho' => 99999]));

        $response->assertStatus(404);
    }

    public function test_download_aciona_google_drive_service_quando_foto_e_do_drive(): void
    {
        $driveMock = $this->mock(GoogleDriveService::class);

        // ID do Drive não começa com 'fotos/' (é um ID externo)
        $driveId = 'AbCdEfGhIjKlMnOp12345';

        $driveMock->shouldReceive('download')
            ->once()
            ->with($driveId)
            ->andReturn(new class {
                public function getContents(): string { return 'conteudo-drive'; }
            });

        Foto::create([
            'trabalho_id'     => $this->trabalho->id,
            'nome_arquivo'    => 'foto-drive.jpg',
            'drive_arquivo_id'=> $driveId,
            'tamanho_bytes'   => 5000,
            'ordem'           => 0,
        ]);

        $response = $this->actingAs($this->silvia, 'web')
            ->get(route('admin.jobs.download-fotos', $this->trabalho));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/zip');
    }

    public function test_nome_do_zip_e_baseado_no_titulo_do_trabalho(): void
    {
        Storage::disk('public')->put('fotos/1/originais/foto.jpg', 'fake');

        Foto::create([
            'trabalho_id'     => $this->trabalho->id,
            'nome_arquivo'    => 'foto.jpg',
            'drive_arquivo_id'=> 'fotos/1/originais/foto.jpg',
            'tamanho_bytes'   => 500,
            'ordem'           => 0,
        ]);

        $response = $this->actingAs($this->silvia, 'web')
            ->get(route('admin.jobs.download-fotos', $this->trabalho));

        $response->assertHeader('Content-Disposition');
        $this->assertStringContainsString(
            'casamento-teste.zip',
            $response->headers->get('Content-Disposition')
        );
    }

    // ===================================================
    // FINALIZAR TRABALHO — Livewire JobList
    // ===================================================

    public function test_finalizar_trabalho_publicado_altera_status_para_finalizado(): void
    {
        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->call('finalizarTrabalho', $this->trabalho->id);

        $this->assertDatabaseHas('trabalhos', [
            'id'     => $this->trabalho->id,
            'status' => 'finalizado',
        ]);
    }

    public function test_finalizar_trabalho_dispara_notify_de_sucesso(): void
    {
        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->call('finalizarTrabalho', $this->trabalho->id)
            ->assertDispatched('notify', fn($name, $params) =>
                ($params['tipo'] ?? null) === 'sucesso' && str_contains($params['mensagem'] ?? '', 'finalizado')
            );
    }

    public function test_trabalho_em_rascunho_nao_pode_ser_finalizado(): void
    {
        $rascunho = Trabalho::create([
            'titulo'        => 'Ensaio Rascunho',
            'data_trabalho' => '2026-03-10',
            'tipo'          => 'previa',
            'status'        => 'rascunho',
        ]);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->call('finalizarTrabalho', $rascunho->id)
            ->assertDispatched('notify', fn($name, $params) =>
                ($params['tipo'] ?? null) === 'erro'
            );

        $this->assertDatabaseHas('trabalhos', [
            'id'     => $rascunho->id,
            'status' => 'rascunho',
        ]);
    }

    public function test_trabalho_ja_finalizado_nao_muda_status_novamente(): void
    {
        $this->trabalho->update(['status' => 'finalizado']);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->call('finalizarTrabalho', $this->trabalho->id)
            ->assertDispatched('notify', fn($name, $params) =>
                ($params['tipo'] ?? null) === 'erro'
            );

        $this->assertDatabaseHas('trabalhos', [
            'id'     => $this->trabalho->id,
            'status' => 'finalizado', // não mudou
        ]);
    }

    // ===================================================
    // FILTROS E LISTAGEM — JobList
    // ===================================================

    public function test_filtro_finalizados_exibe_apenas_trabalhos_finalizados(): void
    {
        $this->trabalho->update(['status' => 'finalizado']);

        $ativo = Trabalho::create([
            'titulo'        => 'Trabalho Ativo',
            'data_trabalho' => '2026-04-01',
            'tipo'          => 'previa',
            'status'        => 'publicado',
        ]);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->set('filtroTipo', 'finalizados')
            ->assertSee('Casamento Teste')
            ->assertDontSee('Trabalho Ativo');
    }

    public function test_filtro_padrao_nao_exibe_trabalhos_finalizados(): void
    {
        $this->trabalho->update(['status' => 'finalizado']);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->set('filtroTipo', 'todos')
            ->assertDontSee('Casamento Teste');
    }

    public function test_badge_finalizado_aparece_na_view(): void
    {
        $this->trabalho->update(['status' => 'finalizado']);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->set('filtroTipo', 'finalizados')
            ->assertSee('Finalizado');
    }

    // ===================================================
    // BOTÕES DE ARQUIVAMENTO — visibilidade na UI
    // ===================================================

    public function test_botao_baixar_hd_aparece_quando_todos_links_expirados(): void
    {
        // Trabalho publicado com link expirado (já configurado no setUp)
        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->assertSee('Baixar para HD');
    }

    public function test_botao_baixar_hd_nao_aparece_quando_ha_link_ativo(): void
    {
        // Adiciona um link válido ao trabalho
        $clienteAtivo = Cliente::create([
            'nome'     => 'Pedro Ativo',
            'telefone' => '11888880000',
        ]);

        $this->trabalho->clientes()->attach($clienteAtivo->id, [
            'token'       => Str::random(64),
            'expira_em'   => now()->addDays(15),
            'status_link' => 'disponivel',
        ]);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->assertDontSee('Baixar para HD');
    }

    public function test_botoes_arquivamento_nao_aparecem_em_trabalhos_finalizados(): void
    {
        $this->trabalho->update(['status' => 'finalizado']);

        Livewire::actingAs($this->silvia)
            ->test(JobList::class)
            ->set('filtroTipo', 'finalizados')
            ->assertDontSee('Baixar para HD')
            ->assertDontSee('Finalizar');
    }
}
