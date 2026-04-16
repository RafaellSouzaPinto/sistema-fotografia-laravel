# T14 — Testes: Foto de Capa do Trabalho

**Spec de referência:** [M14_FOTO_CAPA_TRABALHO.md](../modulos/M14_FOTO_CAPA_TRABALHO.md)
**Arquivo de teste PHP:** `tests/Feature/FotoCapaTest.php`

---

## Loop de validação

```
ENQUANTO algum teste falhar OU alguma regra da spec não estiver coberta:
  1. php artisan test --filter=FotoCapaTest
  2. Para cada falha: identificar a causa no código
  3. Cruzar o erro com a regra correspondente na spec M14
  4. Corrigir o código (Model Trabalho::fotoCapa / view job-list / query eager load)
  5. Voltar ao passo 1
FIM — só encerra quando: 100% pass, 0 erros, 0 regras descobertas
```

---

## Casos de teste

### Grupo 1 — Método fotoCapa() no Model

#### T14.01 — Retorna URL do thumbnail local quando existe no disco
```
DADO uma foto com caminho_thumbnail = 'thumbnails/foto.jpg'
E   o arquivo existe no storage público
QUANDO fotoCapa() é chamado
ENTÃO retorna asset('storage/thumbnails/foto.jpg')
```

```php
public function test_foto_capa_retorna_thumbnail_local_quando_existe(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('thumbnails/foto.jpg', 'conteudo fake');

    $trabalho = Trabalho::factory()->create();
    Foto::factory()->create([
        'trabalho_id'       => $trabalho->id,
        'caminho_thumbnail' => 'thumbnails/foto.jpg',
        'drive_thumbnail'   => null,
        'ordem'             => 1,
    ]);

    $this->assertNotNull($trabalho->fotoCapa());
    $this->assertStringContainsString('thumbnails/foto.jpg', $trabalho->fotoCapa());
}
```

#### T14.02 — Fallback para drive_thumbnail quando arquivo local não existe
```
DADO uma foto com caminho_thumbnail = 'thumbnails/ausente.jpg' (arquivo NÃO existe no disco)
E   drive_thumbnail = 'https://drive.google.com/thumb/abc'
QUANDO fotoCapa() é chamado
ENTÃO retorna a URL do Drive
```

```php
public function test_foto_capa_usa_drive_thumbnail_como_fallback(): void
{
    Storage::fake('public'); // vazio — arquivo local não existe

    $trabalho = Trabalho::factory()->create();
    Foto::factory()->create([
        'trabalho_id'       => $trabalho->id,
        'caminho_thumbnail' => 'thumbnails/nao_existe.jpg',
        'drive_thumbnail'   => 'https://drive.google.com/thumb/abc123',
        'ordem'             => 1,
    ]);

    $this->assertEquals('https://drive.google.com/thumb/abc123', $trabalho->fotoCapa());
}
```

#### T14.03 — Retorna null quando trabalho não tem fotos
```
DADO um trabalho sem nenhuma foto
QUANDO fotoCapa() é chamado
ENTÃO retorna null
```

```php
public function test_foto_capa_retorna_null_sem_fotos(): void
{
    $trabalho = Trabalho::factory()->create();
    $this->assertNull($trabalho->fotoCapa());
}
```

#### T14.04 — Retorna null quando foto não tem thumbnail nenhum
```
DADO uma foto com caminho_thumbnail = null e drive_thumbnail = null
QUANDO fotoCapa() é chamado
ENTÃO retorna null
```

```php
public function test_foto_capa_retorna_null_sem_thumbnails(): void
{
    Storage::fake('public');

    $trabalho = Trabalho::factory()->create();
    Foto::factory()->create([
        'trabalho_id'       => $trabalho->id,
        'caminho_thumbnail' => null,
        'drive_thumbnail'   => null,
        'ordem'             => 1,
    ]);

    $this->assertNull($trabalho->fotoCapa());
}
```

#### T14.05 — Usa a foto de MENOR ordem (não a mais recente)
```
DADO foto A com ordem = 2, foto B com ordem = 1
QUANDO fotoCapa() é chamado
ENTÃO usa o thumbnail da foto B (ordem = 1)
```

```php
public function test_foto_capa_usa_menor_ordem(): void
{
    Storage::fake('public');
    Storage::disk('public')->put('thumbnails/primeira.jpg', 'fake');
    Storage::disk('public')->put('thumbnails/segunda.jpg', 'fake');

    $trabalho = Trabalho::factory()->create();

    Foto::factory()->create([
        'trabalho_id'       => $trabalho->id,
        'caminho_thumbnail' => 'thumbnails/segunda.jpg',
        'ordem'             => 2,
    ]);
    Foto::factory()->create([
        'trabalho_id'       => $trabalho->id,
        'caminho_thumbnail' => 'thumbnails/primeira.jpg',
        'ordem'             => 1,
    ]);

    $this->assertStringContainsString('primeira.jpg', $trabalho->fotoCapa());
}
```

---

### Grupo 2 — Dashboard exibe a capa

#### T14.06 — Dashboard exibe a imagem de capa quando trabalho tem foto
```
DADO um trabalho publicado com uma foto com drive_thumbnail preenchido
QUANDO a dashboard é renderizada
ENTÃO a URL do thumbnail está presente no HTML
```

```php
public function test_dashboard_exibe_imagem_quando_trabalho_tem_foto(): void
{
    Storage::fake('public');

    $trabalho = Trabalho::factory()->create(['status' => 'publicado', 'titulo' => 'Casamento Teste']);
    Foto::factory()->create([
        'trabalho_id'     => $trabalho->id,
        'drive_thumbnail' => 'https://drive.google.com/thumb/xyz',
        'ordem'           => 1,
    ]);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertSee('https://drive.google.com/thumb/xyz');
}
```

#### T14.07 — Dashboard exibe placeholder quando trabalho não tem fotos
```
DADO um trabalho sem fotos
QUANDO a dashboard é renderizada
ENTÃO o placeholder (ícone câmera / "Sem fotos") está visível
```

```php
public function test_dashboard_exibe_placeholder_sem_fotos(): void
{
    Trabalho::factory()->create(['status' => 'publicado', 'titulo' => 'Trabalho Sem Fotos']);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertSee('Sem fotos');
}
```

#### T14.08 — N+1 não ocorre: query usa eager loading
```
DADO 5 trabalhos com fotos
QUANDO a dashboard é renderizada
ENTÃO apenas 1 query para fotos é executada (eager load, não N queries)
```

```php
public function test_sem_n_mais_1_na_listagem(): void
{
    $trabalhos = Trabalho::factory()->count(5)->create(['status' => 'publicado']);
    foreach ($trabalhos as $t) {
        Foto::factory()->count(3)->create(['trabalho_id' => $t->id]);
    }

    // Conta queries — deve ser constante, não crescer com número de trabalhos
    $queryCount = 0;
    \DB::listen(function () use (&$queryCount) { $queryCount++; });

    Livewire::actingAs($this->usuario)->test(JobList::class);

    // Com eager load, não deve executar 1 query por trabalho para fotos
    $this->assertLessThan(10, $queryCount, "Possível N+1 detectado: {$queryCount} queries");
}
```

---

### Grupo 3 — Responsividade e layout

#### T14.09 — Dashboard renderiza sem erro com trabalhos sem capa
```
DADO 3 trabalhos, nenhum com fotos
QUANDO a dashboard é renderizada
ENTÃO não há erro 500 e a página carrega normalmente
```

```php
public function test_dashboard_renderiza_sem_erros_sem_capas(): void
{
    Trabalho::factory()->count(3)->create(['status' => 'publicado']);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertStatus(200);
}
```

#### T14.10 — Card exibe altura fixa de 180px na imagem de capa
```
QUANDO a view é renderizada com um trabalho com foto
ENTÃO o HTML contém style com height:180px para a imagem
```

```php
public function test_imagem_capa_tem_altura_definida(): void
{
    Storage::fake('public');
    $trabalho = Trabalho::factory()->create(['status' => 'publicado']);
    Foto::factory()->create([
        'trabalho_id'     => $trabalho->id,
        'drive_thumbnail' => 'https://drive.google.com/thumb/test',
        'ordem'           => 1,
    ]);

    Livewire::actingAs($this->usuario)
        ->test(JobList::class)
        ->assertSee('height:180px');
}
```

---

## Checklist de regras da spec M14

- [ ] **R1** — `fotoCapa()` retorna thumbnail local se o arquivo existe no disco
- [ ] **R2** — `fotoCapa()` usa `drive_thumbnail` como fallback quando arquivo local ausente
- [ ] **R3** — `fotoCapa()` retorna `null` quando trabalho não tem fotos
- [ ] **R4** — `fotoCapa()` retorna `null` quando foto não tem nenhum thumbnail
- [ ] **R5** — Usa a foto com MENOR `ordem` (não a mais recente por `id`)
- [ ] **R6** — Trabalhos sem foto exibem placeholder rosa com ícone câmera e texto "Sem fotos"
- [ ] **R7** — A query usa `with('fotos', fn($q) => $q->orderBy('ordem')->limit(1))` (sem N+1)
- [ ] **R8** — Imagem de capa tem altura fixa de 180px com `object-fit: cover`
- [ ] **R9** — Dashboard carrega sem erro mesmo sem nenhuma foto cadastrada

---

## Comando de execução

```bash
php artisan test --filter=FotoCapaTest --stop-on-failure
```

Repetir até:
```
Tests: 10 passed
```
