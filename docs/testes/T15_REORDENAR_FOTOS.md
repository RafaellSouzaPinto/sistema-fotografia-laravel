# T15 — Testes: Reordenar Fotos via Drag and Drop

**Spec de referência:** [M15_REORDENAR_FOTOS.md](../modulos/M15_REORDENAR_FOTOS.md)
**Arquivo de teste PHP:** `tests/Feature/ReordenarFotosTest.php`

---

## Loop de validação

```
ENQUANTO algum teste falhar OU alguma regra da spec não estiver coberta:
  1. php artisan test --filter=ReordenarFotosTest
  2. Para cada falha: identificar a causa no código
  3. Cruzar o erro com a regra correspondente na spec M15
  4. Corrigir o código (PhotoUploader::reordenar / view / SortableJS)
  5. Voltar ao passo 1
FIM — só encerra quando: 100% pass, 0 erros, 0 regras descobertas
```

---

## Casos de teste

### Grupo 1 — Método reordenar() funcional

#### T15.01 — Reordenar 3 fotos salva nova ordem no banco
```
DADO 3 fotos com ordem [1, 2, 3] e IDs [A, B, C]
QUANDO reordenar([C, A, B]) é chamado
ENTÃO foto C → ordem 1
E    foto A → ordem 2
E    foto B → ordem 3
```

```php
public function test_reordenar_salva_nova_ordem(): void
{
    $trabalho = Trabalho::factory()->create();
    $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);
    $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);
    $fotoC = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 3]);

    Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
        ->call('reordenar', [$fotoC->id, $fotoA->id, $fotoB->id]);

    $this->assertDatabaseHas('fotos', ['id' => $fotoC->id, 'ordem' => 1]);
    $this->assertDatabaseHas('fotos', ['id' => $fotoA->id, 'ordem' => 2]);
    $this->assertDatabaseHas('fotos', ['id' => $fotoB->id, 'ordem' => 3]);
}
```

#### T15.02 — Ordem começa em 1 (não em 0)
```
DADO 2 fotos [A, B]
QUANDO reordenar([A, B]) é chamado
ENTÃO foto A → ordem 1
E    foto B → ordem 2
(nunca ordem = 0)
```

```php
public function test_reordenar_ordem_comeca_em_1(): void
{
    $trabalho = Trabalho::factory()->create();
    $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);
    $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);

    Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
        ->call('reordenar', [$fotoA->id, $fotoB->id]);

    $this->assertDatabaseHas('fotos', ['id' => $fotoA->id, 'ordem' => 1]);
    $this->assertDatabaseHas('fotos', ['id' => $fotoB->id, 'ordem' => 2]);
    $this->assertDatabaseMissing('fotos', ['ordem' => 0]);
}
```

#### T15.03 — Notificação de sucesso é despachada após reordenar
```
QUANDO reordenar() é chamado com IDs válidos
ENTÃO evento 'notify' é despachado com tipo 'sucesso'
```

```php
public function test_reordenar_dispara_notificacao_sucesso(): void
{
    $trabalho = Trabalho::factory()->create();
    $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);
    $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);

    Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
        ->call('reordenar', [$fotoB->id, $fotoA->id])
        ->assertDispatched('notify');
}
```

#### T15.04 — Reordenar 1 foto única não dá erro
```
DADO 1 foto
QUANDO reordenar([A]) é chamado
ENTÃO foto A → ordem 1
E    não há erro
```

```php
public function test_reordenar_uma_foto_sem_erro(): void
{
    $trabalho = Trabalho::factory()->create();
    $foto = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);

    Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
        ->call('reordenar', [$foto->id])
        ->assertHasNoErrors();

    $this->assertDatabaseHas('fotos', ['id' => $foto->id, 'ordem' => 1]);
}
```

#### T15.05 — Ordem na galeria pública reflete nova ordem após reordenar
```
DADO 3 fotos com ordem [1, 2, 3] (nomes: foto1, foto2, foto3)
QUANDO reordenado para [foto3, foto1, foto2]
ENTÃO GET /galeria/{token} exibe as fotos na nova ordem
```

```php
public function test_galeria_reflete_nova_ordem(): void
{
    $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
    $cliente = Cliente::factory()->create();
    $token = Str::random(64);
    $trabalho->clientes()->attach($cliente->id, [
        'token' => $token, 'expira_em' => now()->addDays(30), 'status_link' => 'disponivel',
    ]);

    $fotoA = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'nome_arquivo' => 'foto_a.jpg', 'ordem' => 1]);
    $fotoB = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'nome_arquivo' => 'foto_b.jpg', 'ordem' => 2]);
    $fotoC = Foto::factory()->create(['trabalho_id' => $trabalho->id, 'nome_arquivo' => 'foto_c.jpg', 'ordem' => 3]);

    // Reordena: C primeiro
    Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id])
        ->call('reordenar', [$fotoC->id, $fotoA->id, $fotoB->id]);

    $fotoC->refresh();
    $fotoA->refresh();
    $fotoB->refresh();

    $this->assertEquals(1, $fotoC->ordem);
    $this->assertEquals(2, $fotoA->ordem);
    $this->assertEquals(3, $fotoB->ordem);
}
```

---

### Grupo 2 — Segurança

#### T15.06 — reordenar() ignora fotos de outro trabalho no array
```
DADO foto X pertencente ao trabalho Y (diferente do trabalho do componente)
QUANDO reordenar([X, ...]) é chamado no contexto do trabalho Z
ENTÃO foto X NÃO é alterada
```

```php
public function test_reordenar_ignora_fotos_de_outro_trabalho(): void
{
    $trabalhoA = Trabalho::factory()->create();
    $trabalhoB = Trabalho::factory()->create();

    $fotoA = Foto::factory()->create(['trabalho_id' => $trabalhoA->id, 'ordem' => 1]);
    $fotoBOutro = Foto::factory()->create(['trabalho_id' => $trabalhoB->id, 'ordem' => 5]);

    // Tenta incluir foto de outro trabalho na reordenação
    Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalhoA->id])
        ->call('reordenar', [$fotoBOutro->id, $fotoA->id]);

    // Foto do outro trabalho não deve ser alterada
    $fotoBOutro->refresh();
    $this->assertEquals(5, $fotoBOutro->ordem);
}
```

#### T15.07 — Não autenticado não consegue reordenar
```
QUANDO usuário não autenticado tenta acessar o painel admin
ENTÃO é redirecionado para /login
```

```php
public function test_nao_autenticado_redirecionado_para_login(): void
{
    $this->get('/admin/dashboard')->assertRedirect('/login');
}
```

---

### Grupo 3 — Computed property fotosDoTrabalho

#### T15.08 — fotosDoTrabalho retorna fotos em ordem ascendente
```
DADO 3 fotos com ordens [3, 1, 2]
QUANDO fotosDoTrabalho é acessado
ENTÃO retorna na ordem [1, 2, 3]
```

```php
public function test_fotos_do_trabalho_ordenadas_por_ordem(): void
{
    $trabalho = Trabalho::factory()->create();
    Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 3]);
    Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 1]);
    Foto::factory()->create(['trabalho_id' => $trabalho->id, 'ordem' => 2]);

    $component = Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id]);

    $fotos = $component->get('fotosDoTrabalho');
    $ordens = $fotos->pluck('ordem')->toArray();

    $this->assertEquals([1, 2, 3], $ordens);
}
```

#### T15.09 — fotosDoTrabalho retorna coleção vazia sem fotos
```
DADO trabalho sem fotos
QUANDO fotosDoTrabalho é acessado
ENTÃO retorna coleção vazia (count = 0)
```

```php
public function test_fotos_do_trabalho_vazia_sem_fotos(): void
{
    $trabalho = Trabalho::factory()->create();

    $component = Livewire::actingAs($this->usuario)
        ->test(PhotoUploader::class, ['trabalhoId' => $trabalho->id]);

    $this->assertCount(0, $component->get('fotosDoTrabalho'));
}
```

---

## Checklist de regras da spec M15

- [ ] **R1** — `reordenar(array $ids)` atualiza coluna `ordem` de cada foto
- [ ] **R2** — Ordem começa em `1` (não `0`) — `$posicao + 1`
- [ ] **R3** — Filtra por `trabalho_id` na query — fotos de outros trabalhos não são alteradas
- [ ] **R4** — Toast de sucesso é despachado após reordenar
- [ ] **R5** — SortableJS incluído via CDN no layout admin
- [ ] **R6** — Container do grid tem `wire:ignore` para evitar destruição pelo Livewire
- [ ] **R7** — `iniciarSortable()` é chamado no evento `livewire:initialized`
- [ ] **R8** — `iniciarSortable()` é chamado novamente no evento `loteProcessado`
- [ ] **R9** — `fotosDoTrabalho` é computed property ordenada por `ordem` ASC
- [ ] **R10** — Cada thumbnail exibe ícone de grip (≡) indicando que é arrastável

---

## Comando de execução

```bash
php artisan test --filter=ReordenarFotosTest --stop-on-failure
```

Repetir até:
```
Tests: 9 passed
```
