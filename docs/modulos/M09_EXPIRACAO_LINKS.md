# M09 — Expiração de Links

## Propósito

Cada link de galeria (token) pode ter uma data de expiração. Após expirar, o cliente vê uma página amigável informando que o link não está disponível. A Silvia pode configurar o prazo ao vincular o cliente.

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `app/Models/TrabalhoCliente.php` | Lógica de expiração |
| `app/Http/Controllers/GalleryController.php` | Verificação no acesso |
| `app/Livewire/Admin/ClientManager.php` | Campo "Expira em X dias" |
| `resources/views/gallery/expirado.blade.php` | Tela de link expirado |
| `database/migrations/2026_03_19_000001_add_expiracao_to_trabalho_cliente_table.php` | Colunas |

## Colunas adicionadas à `trabalho_cliente`

```sql
expira_em     datetime, nullable          ← null = sem expiração
status_link   enum('disponivel','expirado') default: disponivel
```

## Lógica de expiração no Model

```php
// app/Models/TrabalhoCliente.php

public function estaExpirado(): bool
{
    // Expirado manualmente (status forçado)
    if ($this->status_link === 'expirado') {
        return true;
    }

    // Expirado por data
    if ($this->expira_em && $this->expira_em->isPast()) {
        return true;
    }

    return false;
}

public function marcarComoExpirado(): void
{
    $this->update(['status_link' => 'expirado']);
}

public function diasRestantes(): ?int
{
    if (!$this->expira_em) return null;        // sem expiração
    if ($this->estaExpirado()) return 0;        // já expirado
    return (int) now()->diffInDays($this->expira_em);
}

public function tempoRestanteFormatado(): string
{
    $dias = $this->diasRestantes();

    if ($dias === null)  return 'Sem expiração';
    if ($dias === 0)     return 'Expirado';
    if ($dias === 1)     return 'Expira amanhã';
    return "Expira em {$dias} dias";
}
```

## Verificação no GalleryController

```php
public function show(string $token): View
{
    $vinculo = TrabalhoCliente::where('token', $token)->firstOrFail();

    if ($vinculo->estaExpirado()) {
        $vinculo->marcarComoExpirado(); // Garante que status_link fica 'expirado'
        return view('gallery.expirado');
    }

    // ... continua exibição normal
}
```

## Configuração ao vincular cliente (ClientManager)

```php
// Campo diasExpiracao na UI
public int $diasExpiracao = 30; // padrão: 30 dias

// Ao criar vínculo:
TrabalhoCliente::create([
    // ...
    'expira_em'   => $this->diasExpiracao > 0
                        ? now()->addDays($this->diasExpiracao)
                        : null,
    'status_link' => 'disponivel',
]);
```

**Opções na UI**:
- 7 dias
- 15 dias
- 30 dias (padrão)
- 60 dias
- 90 dias
- Sem expiração (0)

## Exibição no ClientManager

Cada cliente vinculado mostra o status do link:

```
Ana Lima   (11) 99999-0001
🔗 https://site.com/galeria/abc123  [📋 Copiar]
⏱ Expira em 28 dias               [Remover]
```

Cores dos badges de expiração:
- Verde: mais de 7 dias restantes
- Amarelo: 1-7 dias restantes
- Vermelho: Expirado
- Cinza: Sem expiração

## Filtro "Com links expirados" no dashboard

No `JobList`, o filtro `expirados` usa o método do Model:

```php
// Trabalhos onde ALGUM link está expirado
->when($this->filtroTipo === 'expirados', function ($q) {
    $q->whereHas('clientes', function ($qc) {
        $qc->where(function ($inner) {
            $inner->where('trabalho_cliente.status_link', 'expirado')
                  ->orWhere('trabalho_cliente.expira_em', '<', now());
        });
    });
})
```

## View gallery/expirado.blade.php

Layout da tela de expiração:

```
┌──────────────────────────────────────┐
│  🌸 Silvia Souza Fotografa           │
│                                      │
│  Este link não está mais disponível  │
│                                      │
│  O prazo de acesso às suas fotos     │
│  expirou.                            │
│                                      │
│  Entre em contato com a fotógrafa    │
│  para renovar o acesso.              │
│                                      │
│  📱 (11) 99999-0000                  │
│  📷 @silviasouzafotografa            │
└──────────────────────────────────────┘
```

- Sem menu de admin
- Sem informações técnicas (não revelar token, ID, etc.)
- Contato da fotógrafa (configurável via .env ou hardcode)

## Casos de expiração

| Cenário | Resultado |
|---------|-----------|
| `expira_em = null`, `status_link = disponivel` | Nunca expira |
| `expira_em = futuro`, `status_link = disponivel` | Acesso normal, mostra dias restantes |
| `expira_em = passado`, `status_link = disponivel` | Expirado — marca como expirado |
| `expira_em = qualquer`, `status_link = expirado` | Expirado |
| Token soft-deleted | 404 (não chega na verificação de expiração) |
