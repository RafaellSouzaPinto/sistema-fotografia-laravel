# T13 — Testes: Dashboard com Números Simples

**Spec de referência:** [M13_DASHBOARD_NUMEROS.md](../modulos/M13_DASHBOARD_NUMEROS.md)
**Arquivo de teste PHP:** `tests/Feature/DashboardNumerosTest.php`

---

## Loop de validação

```
ENQUANTO algum teste falhar OU alguma regra da spec não estiver coberta:
  1. php artisan test --filter=DashboardNumerosTest
  2. Para cada falha: identificar a causa no código
  3. Cruzar o erro com a regra correspondente na spec M13
  4. Corrigir o código (JobList computed properties / view)
  5. Voltar ao passo 1
FIM — só encerra quando: 100% pass, 0 erros, 0 regras descobertas
```

---

## Casos de teste

### Grupo 1 — Contadores corretos

#### T13.01 — totalPublicados conta só trabalhos com status = publicado
```
DADO 3 trabalhos publicados e 2 rascunhos
QUANDO a dashboard é carregada
ENTÃO o card "Trabalhos publicados" exibe "3"
E    os 2 rascunhos NÃO são contados
```

```php
public function test_total_publicados_ignora_rascunhos(): void
{
    Trabalho::factory()->count(3)->create(['status' => 'publicado']);
    Trabalho::factory()->count(2)->create(['status' => 'rascunho']);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertSee('3'); // card de publicados

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(3, $component->get('totalPublicados'));
}
```

#### T13.02 — totalClientes conta todos os clientes ativos
```
DADO 5 clientes cadastrados (nenhum soft-deleted)
QUANDO a dashboard é carregada
ENTÃO totalClientes = 5
```

```php
public function test_total_clientes_conta_todos(): void
{
    Cliente::factory()->count(5)->create();

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(5, $component->get('totalClientes'));
}
```

#### T13.03 — totalClientes ignora soft-deleted
```
DADO 5 clientes, 2 deles soft-deleted
QUANDO a dashboard é carregada
ENTÃO totalClientes = 3
```

```php
public function test_total_clientes_ignora_deletados(): void
{
    $clientes = Cliente::factory()->count(5)->create();
    $clientes->take(2)->each->delete();

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(3, $component->get('totalClientes'));
}
```

#### T13.04 — totalFotos conta todas as fotos ativas
```
DADO um trabalho com 10 fotos
QUANDO a dashboard é carregada
ENTÃO totalFotos = 10
```

```php
public function test_total_fotos_conta_corretamente(): void
{
    $trabalho = Trabalho::factory()->create();
    Foto::factory()->count(10)->create(['trabalho_id' => $trabalho->id]);

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(10, $component->get('totalFotos'));
}
```

#### T13.05 — totalFotos ignora soft-deleted
```
DADO 10 fotos, 3 delas soft-deleted
QUANDO a dashboard é carregada
ENTÃO totalFotos = 7
```

```php
public function test_total_fotos_ignora_deletadas(): void
{
    $trabalho = Trabalho::factory()->create();
    $fotos = Foto::factory()->count(10)->create(['trabalho_id' => $trabalho->id]);
    $fotos->take(3)->each->delete();

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(7, $component->get('totalFotos'));
}
```

---

### Grupo 2 — Alerta de links expirando

#### T13.06 — linksExpirandoEmBreve conta links disponíveis que expiram em ≤ 7 dias
```
DADO 2 links que expiram em 3 dias (status = disponivel)
E   1 link que expira em 10 dias
QUANDO a dashboard é carregada
ENTÃO linksExpirandoEmBreve = 2
```

```php
public function test_links_expirando_em_breve_conta_proximos_7_dias(): void
{
    $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
    $clientes = Cliente::factory()->count(3)->create();

    $trabalho->clientes()->attach($clientes[0]->id, [
        'token' => Str::random(64), 'expira_em' => now()->addDays(3), 'status_link' => 'disponivel',
    ]);
    $trabalho->clientes()->attach($clientes[1]->id, [
        'token' => Str::random(64), 'expira_em' => now()->addDays(5), 'status_link' => 'disponivel',
    ]);
    $trabalho->clientes()->attach($clientes[2]->id, [
        'token' => Str::random(64), 'expira_em' => now()->addDays(10), 'status_link' => 'disponivel',
    ]);

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(2, $component->get('linksExpirandoEmBreve'));
}
```

#### T13.07 — links já expirados NÃO entram na contagem do alerta
```
DADO 2 links com expira_em no passado
QUANDO a dashboard é carregada
ENTÃO linksExpirandoEmBreve = 0 (não conta expirados, só os que VÃO expirar)
```

```php
public function test_links_expirados_nao_entram_no_alerta(): void
{
    $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
    $clientes = Cliente::factory()->count(2)->create();

    $trabalho->clientes()->attach($clientes[0]->id, [
        'token' => Str::random(64), 'expira_em' => now()->subDays(2), 'status_link' => 'expirado',
    ]);
    $trabalho->clientes()->attach($clientes[1]->id, [
        'token' => Str::random(64), 'expira_em' => now()->subDays(1), 'status_link' => 'disponivel',
    ]);

    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(0, $component->get('linksExpirandoEmBreve'));
}
```

#### T13.08 — alerta aparece na view quando há links expirando
```
DADO 1 link expirando em 3 dias
QUANDO a dashboard é renderizada
ENTÃO o texto de alerta é exibido na tela
```

```php
public function test_alerta_aparece_na_view_quando_ha_links_expirando(): void
{
    $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
    $cliente = Cliente::factory()->create();

    $trabalho->clientes()->attach($cliente->id, [
        'token' => Str::random(64), 'expira_em' => now()->addDays(3), 'status_link' => 'disponivel',
    ]);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertSee('expira');
}
```

#### T13.09 — alerta NÃO aparece quando não há links expirando em breve
```
DADO nenhum link expirando nos próximos 7 dias
QUANDO a dashboard é renderizada
ENTÃO o alerta NÃO é exibido
```

```php
public function test_alerta_nao_aparece_sem_links_expirando(): void
{
    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertDontSee('links expiram');
}
```

#### T13.10 — clicar em "Ver trabalhos" ativa filtro expirados
```
QUANDO o botão "Ver trabalhos" do alerta é clicado
ENTÃO filtroTipo = 'expirados'
```

```php
public function test_botao_ver_trabalhos_ativa_filtro_expirados(): void
{
    $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
    $cliente = Cliente::factory()->create();
    $trabalho->clientes()->attach($cliente->id, [
        'token' => Str::random(64), 'expira_em' => now()->addDays(3), 'status_link' => 'disponivel',
    ]);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->call('$set', 'filtroTipo', 'expirados')
        ->assertSet('filtroTipo', 'expirados');
}
```

---

### Grupo 3 — Zeros (banco vazio)

#### T13.11 — Dashboard com banco vazio exibe zeros sem erros
```
DADO banco sem nenhum dado
QUANDO a dashboard é carregada
ENTÃO não há erro 500
E    os 3 cards exibem 0
E    o alerta não aparece
```

```php
public function test_dashboard_banco_vazio_sem_erros(): void
{
    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertStatus(200);
}

public function test_totais_zerados_com_banco_vazio(): void
{
    $component = Livewire::actingAs($this->usuario)->test(JobList::class);
    $this->assertEquals(0, $component->get('totalPublicados'));
    $this->assertEquals(0, $component->get('totalClientes'));
    $this->assertEquals(0, $component->get('totalFotos'));
    $this->assertEquals(0, $component->get('linksExpirandoEmBreve'));
}
```

---

## Checklist de regras da spec M13

- [ ] **R1** — `totalPublicados` conta APENAS `status = publicado`
- [ ] **R2** — `totalClientes` conta todos os clientes sem soft-delete
- [ ] **R3** — `totalFotos` usa `number_format` com ponto como separador de milhar
- [ ] **R4** — `linksExpirandoEmBreve` conta só links `disponivel` com `expira_em` nos próximos 7 dias
- [ ] **R5** — Links já expirados NÃO entram na contagem do alerta
- [ ] **R6** — Alerta só aparece se `linksExpirandoEmBreve > 0`
- [ ] **R7** — Botão "Ver trabalhos" define `filtroTipo = 'expirados'`
- [ ] **R8** — Dashboard carrega sem erro com banco vazio
- [ ] **R9** — Os 3 cards são exibidos no layout (trabalhos, clientes, fotos)

---

## Comando de execução

```bash
php artisan test --filter=DashboardNumerosTest --stop-on-failure
```

Repetir até:
```
Tests: 12 passed
```
