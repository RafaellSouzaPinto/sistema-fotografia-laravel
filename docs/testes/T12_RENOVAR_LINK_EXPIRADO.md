# T12 — Testes: Renovar Link Expirado

**Spec de referência:** [M12_RENOVAR_LINK_EXPIRADO.md](../modulos/M12_RENOVAR_LINK_EXPIRADO.md)
**Arquivo de teste PHP:** `tests/Feature/RenovarLinkTest.php`

---

## Loop de validação

```
ENQUANTO algum teste falhar OU alguma regra da spec não estiver coberta:
  1. php artisan test --filter=RenovarLinkTest
  2. Para cada falha: identificar a causa no código
  3. Cruzar o erro com a regra correspondente na spec M12
  4. Corrigir o código (Model / Livewire / View)
  5. Voltar ao passo 1
FIM — só encerra quando: 100% pass, 0 erros, 0 regras descobertas
```

---

## Casos de teste

### Grupo 1 — Renovação funcional

#### T12.01 — Renovar link expirado por data
```
DADO um vínculo com expira_em = ontem e status_link = disponivel
QUANDO Silvia chama renovar() com diasRenovacao = 30
ENTÃO status_link → 'disponivel'
E    expira_em → agora + 30 dias (± 60s)
E    evento 'notify' é despachado com tipo 'sucesso'
```

```php
public function test_renovar_link_expirado_por_data(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'disponivel',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->set('diasRenovacao', 30)
        ->call('renovar')
        ->assertDispatched('notify');

    $vinculo->refresh();
    $this->assertEquals('disponivel', $vinculo->status_link);
    $this->assertTrue($vinculo->expira_em->isFuture());
    $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $vinculo->expira_em->timestamp, 60);
}
```

#### T12.02 — Renovar link com status_link = expirado
```
DADO um vínculo com status_link = 'expirado'
QUANDO Silvia chama renovar() com diasRenovacao = 15
ENTÃO status_link → 'disponivel'
E    estaExpirado() retorna false
```

```php
public function test_renovar_link_com_status_expirado(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(10), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->set('diasRenovacao', 15)
        ->call('renovar');

    $vinculo->refresh();
    $this->assertEquals('disponivel', $vinculo->status_link);
    $this->assertFalse($vinculo->estaExpirado());
}
```

#### T12.03 — Galeria volta a funcionar após renovação
```
DADO um link expirado
QUANDO Silvia renova com 30 dias
ENTÃO GET /galeria/{token} retorna 200
E    a galeria exibe o nome do cliente
```

```php
public function test_galeria_acessivel_apos_renovacao(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->set('diasRenovacao', 30)
        ->call('renovar');

    $this->get("/galeria/{$token}")
        ->assertStatus(200)
        ->assertSee($this->cliente->nome);
}
```

#### T12.04 — Todos os prazos possíveis (7, 15, 30, 60, 90 dias)
```
PARA CADA dias EM [7, 15, 30, 60, 90]:
  DADO um link expirado
  QUANDO Silvia renova com {dias} dias
  ENTÃO expira_em ≈ agora + {dias} dias
  E    status_link = 'disponivel'
```

```php
public function test_renovar_todos_os_prazos(): void
{
    foreach ([7, 15, 30, 60, 90] as $dias) {
        $token = Str::random(64);
        $this->trabalho->clientes()->attach($this->cliente->id, [
            'token' => $token, 'expira_em' => now()->subDays(1), 'status_link' => 'expirado',
        ]);
        $vinculo = TrabalhoCliente::where('token', $token)->first();

        Livewire::actingAs($this->usuario)
            ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
            ->call('abrirRenovacao', $vinculo->id)
            ->set('diasRenovacao', $dias)
            ->call('renovar');

        $vinculo->refresh();
        $this->assertEquals('disponivel', $vinculo->status_link, "Falhou para $dias dias");
        $this->assertEqualsWithDelta(now()->addDays($dias)->timestamp, $vinculo->expira_em->timestamp, 60);

        $vinculo->forceDelete();
    }
}
```

---

### Grupo 2 — Validação de entrada

#### T12.05 — diasRenovacao = 0 deve falhar
```
DADO um link expirado
QUANDO diasRenovacao = 0
ENTÃO erro de validação em 'diasRenovacao'
E    banco não é alterado
```

```php
public function test_renovar_rejeita_zero_dias(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->set('diasRenovacao', 0)
        ->call('renovar')
        ->assertHasErrors(['diasRenovacao']);

    $vinculo->refresh();
    $this->assertEquals('expirado', $vinculo->status_link); // não alterou
}
```

#### T12.06 — diasRenovacao = 366 deve falhar (máx 365)
```
QUANDO diasRenovacao = 366
ENTÃO erro de validação em 'diasRenovacao'
```

```php
public function test_renovar_rejeita_mais_de_365_dias(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->set('diasRenovacao', 366)
        ->call('renovar')
        ->assertHasErrors(['diasRenovacao']);
}
```

---

### Grupo 3 — Estado do componente

#### T12.07 — abrirRenovacao define renovandoVinculoId
```
QUANDO abrirRenovacao($id) é chamado
ENTÃO renovandoVinculoId = $id
E    diasRenovacao = 30 (padrão)
```

```php
public function test_abrir_renovacao_define_estado(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->assertSet('renovandoVinculoId', $vinculo->id)
        ->assertSet('diasRenovacao', 30);
}
```

#### T12.08 — cancelarRenovacao zera estado
```
QUANDO cancelarRenovacao() é chamado após abrirRenovacao
ENTÃO renovandoVinculoId = 0
E    diasRenovacao = 30
```

```php
public function test_cancelar_renovacao_zera_estado(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->call('cancelarRenovacao')
        ->assertSet('renovandoVinculoId', 0)
        ->assertSet('diasRenovacao', 30);
}
```

#### T12.09 — renovar bem-sucedido zera estado
```
APÓS renovar() com sucesso
ENTÃO renovandoVinculoId = 0
```

```php
public function test_renovar_zera_estado_apos_sucesso(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->subDays(5), 'status_link' => 'expirado',
    ]);
    $vinculo = TrabalhoCliente::where('token', $token)->first();

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->call('abrirRenovacao', $vinculo->id)
        ->set('diasRenovacao', 30)
        ->call('renovar')
        ->assertSet('renovandoVinculoId', 0);
}
```

---

### Grupo 4 — Segurança

#### T12.10 — Link não expirado NÃO deve exibir botão "Renovar"
```
DADO um link com status_link = disponivel e expira_em = +20 dias
QUANDO a view é renderizada
ENTÃO o botão "Renovar" NÃO está visível para este cliente
```

```php
public function test_botao_renovar_nao_aparece_para_link_valido(): void
{
    $token = Str::random(64);
    $this->trabalho->clientes()->attach($this->cliente->id, [
        'token' => $token, 'expira_em' => now()->addDays(20), 'status_link' => 'disponivel',
    ]);

    Livewire::actingAs($this->usuario)
        ->test(ClientManager::class, ['trabalhoId' => $this->trabalho->id])
        ->assertDontSee('wire:click="abrirRenovacao');
}
```

---

## Checklist de regras da spec M12

Antes de declarar 100%, confirmar CADA item:

- [ ] **R1** — Botão "Renovar" aparece APENAS quando `estaExpirado() === true`
- [ ] **R2** — `renovar()` atualiza `expira_em = now() + diasRenovacao`
- [ ] **R3** — `renovar()` atualiza `status_link = 'disponivel'`
- [ ] **R4** — O mesmo token é mantido (cliente não recebe link novo)
- [ ] **R5** — `diasRenovacao = 0` é rejeitado (validação min:1)
- [ ] **R6** — `diasRenovacao > 365` é rejeitado (validação max:365)
- [ ] **R7** — Após renovar, estado `renovandoVinculoId` volta a 0
- [ ] **R8** — `cancelarRenovacao()` zera estado sem alterar banco
- [ ] **R9** — Toast de sucesso é disparado após renovar
- [ ] **R10** — Galeria `/galeria/{token}` retorna 200 após renovação

---

## Comando de execução

```bash
php artisan test --filter=RenovarLinkTest --stop-on-failure
```

Repetir até:
```
Tests: 10 passed
```
