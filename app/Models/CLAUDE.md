# Models — Contexto para Claude Code

## Tabelas (TODAS com colunas em PT-BR e SoftDeletes)

### Usuario ($table = 'usuarios')
- Colunas: nome, email, senha
- Auth especial: getAuthPassword() retorna $this->senha
- Sem relacionamentos com outras tabelas
- Login fixo via seed (só Silvia)

### Cliente ($table = 'clientes')
- Colunas: nome, telefone
- Relacionamento: trabalhos() → belongsToMany via trabalho_cliente com pivot 'token'
- Reutilizável: mesmo cliente pode estar em vários trabalhos
- Busca por telefone: limpar máscara antes de comparar

### Trabalho ($table = 'trabalhos')
- Colunas: titulo, data_trabalho (date), tipo (enum: previa/completo), status (enum: rascunho/publicado), drive_pasta_id
- Relacionamentos: clientes() → belongsToMany com pivot 'token', fotos() → hasMany
- $casts: data_trabalho → date

### TrabalhoCliente ($table = 'trabalho_cliente')
- Pivot model com colunas próprias: trabalho_id, cliente_id, token (64 chars unique)
- Relacionamentos: trabalho() → belongsTo, cliente() → belongsTo
- Token gerado com Str::random(64) ao vincular cliente

### Foto ($table = 'fotos')
- Colunas: trabalho_id, nome_arquivo, drive_arquivo_id, drive_thumbnail, tamanho_bytes, ordem
- Relacionamento: trabalho() → belongsTo
- drive_arquivo_id pode ser path local (storage) ou ID do Google Drive
