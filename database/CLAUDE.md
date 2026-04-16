# Database — Contexto para Claude Code

## Regras críticas
1. Colunas em PT-BR (nome, telefone, titulo, data_trabalho, etc.)
2. TODAS as tabelas têm SoftDeletes (deleted_at)
3. NUNCA alterar migration já rodada — criar nova migration para mudanças
4. Timestamps: created_at, updated_at, deleted_at (em inglês)

## Tabelas (ordem de criação)
1. usuarios (nome, email, senha)
2. clientes (nome, telefone)
3. trabalhos (titulo, data_trabalho, tipo, status, drive_pasta_id)
4. trabalho_cliente (trabalho_id FK, cliente_id FK, token unique)
5. fotos (trabalho_id FK, nome_arquivo, drive_arquivo_id, drive_thumbnail, tamanho_bytes, ordem)

## Seeders
- UsuarioSeeder: cria Silvia (email: silviasouzafotografa@gmail.com, senha: 123456)
- Pode ter seeder de dados fake para teste (trabalhos, clientes, fotos)

## Relacionamentos
- trabalhos ←→ clientes: N:N via trabalho_cliente (com token)
- trabalhos → fotos: 1:N
- usuarios: isolado (só login)
