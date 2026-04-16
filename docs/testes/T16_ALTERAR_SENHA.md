# T16 — Testes: Alterar Senha no Painel Admin

**Spec de referência:** [M16_ALTERAR_SENHA.md](../modulos/M16_ALTERAR_SENHA.md)
**Arquivo de teste PHP:** `tests/Feature/AlterarSenhaTest.php`

---

## Loop de validação

```
ENQUANTO algum teste falhar OU alguma regra da spec não estiver coberta:
  1. php artisan test --filter=AlterarSenhaTest
  2. Para cada falha: identificar a causa no código
  3. Cruzar o erro com a regra correspondente na spec M16
  4. Corrigir o código (AlterarSenha component / view / rota)
  5. Voltar ao passo 1
FIM — só encerra quando: 100% pass, 0 erros, 0 regras descobertas
```

---

## Casos de teste

### Grupo 1 — Troca de senha funcional

#### T16.01 — Senha alterada com sucesso quando dados estão corretos
```
DADO Silvia autenticada com senha '123456'
QUANDO preenche: senhaAtual='123456', novaSenha='nova123', confirmacaoSenha='nova123'
E    chama salvar()
ENTÃO a coluna `senha` no banco é atualizada (hash bcrypt de 'nova123')
E    evento 'notify' é despachado com tipo 'sucesso'
```

```php
public function test_alterar_senha_com_dados_corretos(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'nova123')
        ->set('confirmacaoSenha', 'nova123')
        ->call('salvar')
        ->assertHasNoErrors()
        ->assertDispatched('notify');

    $this->usuario->refresh();
    $this->assertTrue(Hash::check('nova123', $this->usuario->senha));
}
```

#### T16.02 — Senha antiga funciona após trocar (hash válido)
```
DADO senha alterada para 'nova_senha_456'
QUANDO Hash::check('nova_senha_456', $usuario->senha) é chamado
ENTÃO retorna true
E    Hash::check('123456', $usuario->senha) retorna false
```

```php
public function test_senha_antiga_nao_funciona_apos_troca(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'nova_senha_456')
        ->set('confirmacaoSenha', 'nova_senha_456')
        ->call('salvar');

    $this->usuario->refresh();
    $this->assertFalse(Hash::check('123456', $this->usuario->senha));
    $this->assertTrue(Hash::check('nova_senha_456', $this->usuario->senha));
}
```

#### T16.03 — Campos são limpos após salvar com sucesso
```
APÓS salvar() bem-sucedido
ENTÃO senhaAtual = ''
E    novaSenha = ''
E    confirmacaoSenha = ''
```

```php
public function test_campos_limpos_apos_salvar(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'nova123')
        ->set('confirmacaoSenha', 'nova123')
        ->call('salvar')
        ->assertSet('senhaAtual', '')
        ->assertSet('novaSenha', '')
        ->assertSet('confirmacaoSenha', '');
}
```

---

### Grupo 2 — Validação de entrada

#### T16.04 — Senha atual incorreta gera erro no campo senhaAtual
```
DADO Silvia com senha '123456'
QUANDO senhaAtual = 'errada999'
ENTÃO erro em campo 'senhaAtual': "Senha atual incorreta."
E    banco NÃO é alterado
```

```php
public function test_senha_atual_incorreta_gera_erro(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', 'errada999')
        ->set('novaSenha', 'nova123')
        ->set('confirmacaoSenha', 'nova123')
        ->call('salvar')
        ->assertHasErrors(['senhaAtual']);

    $this->usuario->refresh();
    $this->assertTrue(Hash::check('123456', $this->usuario->senha)); // não alterou
}
```

#### T16.05 — Nova senha com menos de 6 caracteres gera erro
```
QUANDO novaSenha = 'abc' (3 chars)
ENTÃO erro de validação em 'novaSenha'
```

```php
public function test_nova_senha_muito_curta_gera_erro(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'abc')
        ->set('confirmacaoSenha', 'abc')
        ->call('salvar')
        ->assertHasErrors(['novaSenha']);
}
```

#### T16.06 — Confirmação diferente da nova senha gera erro
```
QUANDO novaSenha = 'nova123' e confirmacaoSenha = 'diferente'
ENTÃO erro de validação em 'confirmacaoSenha': "As senhas não coincidem."
```

```php
public function test_confirmacao_diferente_gera_erro(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'nova123')
        ->set('confirmacaoSenha', 'diferente')
        ->call('salvar')
        ->assertHasErrors(['confirmacaoSenha']);
}
```

#### T16.07 — Campos obrigatórios em branco geram erros
```
QUANDO todos os campos estão em branco
ENTÃO erros em 'senhaAtual', 'novaSenha', 'confirmacaoSenha'
```

```php
public function test_campos_em_branco_geram_erros(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->call('salvar')
        ->assertHasErrors(['senhaAtual', 'novaSenha', 'confirmacaoSenha']);
}
```

#### T16.08 — Nova senha exatamente 6 caracteres é aceita (limite mínimo)
```
QUANDO novaSenha = 'abc123' (6 chars exatos)
ENTÃO não há erro de tamanho
```

```php
public function test_nova_senha_com_exatamente_6_caracteres_aceita(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'abc123')
        ->set('confirmacaoSenha', 'abc123')
        ->call('salvar')
        ->assertHasNoErrors();
}
```

---

### Grupo 3 — Proteção da rota

#### T16.09 — Não autenticado é redirecionado para /login
```
QUANDO usuário não autenticado acessa GET /admin/perfil
ENTÃO é redirecionado para /login
```

```php
public function test_nao_autenticado_redirecionado_do_perfil(): void
{
    $this->get('/admin/perfil')->assertRedirect('/login');
}
```

#### T16.10 — Autenticado acessa /admin/perfil com sucesso
```
QUANDO Silvia autenticada acessa GET /admin/perfil
ENTÃO retorna status 200
E    a página exibe "Alterar Senha"
```

```php
public function test_autenticado_acessa_perfil(): void
{
    $this->actingAs($this->usuario)
        ->get('/admin/perfil')
        ->assertStatus(200)
        ->assertSee('Alterar Senha');
}
```

---

### Grupo 4 — Crítico: nunca usar Auth::attempt()

#### T16.11 — Verificação usa Hash::check, não Auth::attempt
```
Este teste documenta e garante que a implementação usa Hash::check().
Verificação via inspeção do código + teste funcional com coluna 'senha'.
```

```php
public function test_verificacao_usa_hash_check_nao_auth_attempt(): void
{
    // Verifica indiretamente: se usasse Auth::attempt(), falharia
    // porque Auth::attempt() espera coluna 'password', não 'senha'
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'nova123')
        ->set('confirmacaoSenha', 'nova123')
        ->call('salvar')
        ->assertHasNoErrors(); // se usasse Auth::attempt() quebraria aqui

    $this->usuario->refresh();
    $this->assertTrue(Hash::check('nova123', $this->usuario->senha));
}
```

---

### Grupo 5 — Segurança da senha

#### T16.12 — Senha nova é armazenada como bcrypt (não texto plano)
```
APÓS alterar senha para 'nova123'
ENTÃO o valor armazenado em `senha` começa com '$2y$' (hash bcrypt)
E    NÃO é igual à string 'nova123'
```

```php
public function test_senha_armazenada_como_hash_bcrypt(): void
{
    Livewire::actingAs($this->usuario)
        ->test(AlterarSenha::class)
        ->set('senhaAtual', '123456')
        ->set('novaSenha', 'nova123')
        ->set('confirmacaoSenha', 'nova123')
        ->call('salvar');

    $this->usuario->refresh();
    $this->assertNotEquals('nova123', $this->usuario->senha);
    $this->assertStringStartsWith('$2y$', $this->usuario->senha);
}
```

---

## Checklist de regras da spec M16

- [ ] **R1** — **NUNCA usar `Auth::attempt()`** — usa `Hash::check($senhaAtual, $usuario->senha)`
- [ ] **R2** — Senha atual incorreta gera erro no campo `senhaAtual` com mensagem clara
- [ ] **R3** — Nova senha mínimo 6 caracteres
- [ ] **R4** — `confirmacaoSenha` usa `same:novaSenha`
- [ ] **R5** — Após salvar, os 3 campos são resetados (`reset(['senhaAtual', 'novaSenha', 'confirmacaoSenha'])`)
- [ ] **R6** — Rota `/admin/perfil` protegida pelo middleware `auth`
- [ ] **R7** — Senha nova armazenada com `Hash::make()` (bcrypt), nunca texto plano
- [ ] **R8** — Toast de sucesso despachado após alterar
- [ ] **R9** — Link para `/admin/perfil` acessível no menu admin (navbar/dropdown)
- [ ] **R10** — Campos do formulário são `type="password"` (nunca `type="text"`)

---

## Comando de execução

```bash
php artisan test --filter=AlterarSenhaTest --stop-on-failure
```

Repetir até:
```
Tests: 12 passed
```
