# Índice Geral — Documentação por Módulos

Sistema de gestão de entregas fotográficas para Silvia Souza Fotografa.

## Arquivos de referência geral

| Arquivo | Conteúdo |
|---------|----------|
| [00_MAPA_ROTAS_COMPLETO.md](00_MAPA_ROTAS_COMPLETO.md) | Todas as rotas do sistema com método, URI, handler e middleware |
| [00_VISAO_GERAL_ARQUITETURA.md](00_VISAO_GERAL_ARQUITETURA.md) | Stack, banco de dados, fluxos principais, estrutura de pastas |

## Módulos do sistema

| Arquivo | Módulo | Resumo |
|---------|--------|--------|
| [M01_AUTENTICACAO.md](M01_AUTENTICACAO.md) | Autenticação | Login fixo da Silvia; Hash::check + Auth::login; sem registro |
| [M02_TRABALHOS.md](M02_TRABALHOS.md) | Trabalhos | CRUD de trabalhos; tipos prévia/completo; status rascunho/publicado |
| [M03_CLIENTES.md](M03_CLIENTES.md) | Clientes | Cadastro, busca por telefone, edição inline, reutilização entre trabalhos |
| [M04_VINCULOS_TOKENS.md](M04_VINCULOS_TOKENS.md) | Vínculos e Tokens | Associar cliente a trabalho; gerar token único; copiar link; remover |
| [M05_UPLOAD_FOTOS.md](M05_UPLOAD_FOTOS.md) | Upload de Fotos | Drag & drop; lote; compressão automática; fallback local/Drive |
| [M06_GOOGLE_DRIVE.md](M06_GOOGLE_DRIVE.md) | Google Drive API | Service Account; criar pasta; upload; download; thumbnail |
| [M07_GALERIA_PUBLICA.md](M07_GALERIA_PUBLICA.md) | Galeria Pública | Acesso via token; grid responsivo; lightbox; download individual |
| [M08_DOWNLOAD_ZIP.md](M08_DOWNLOAD_ZIP.md) | Download ZIP | Baixar todas as fotos em ZIP; streaming; suporte Drive e local |
| [M09_EXPIRACAO_LINKS.md](M09_EXPIRACAO_LINKS.md) | Expiração de Links | diasExpiracao; expirado → view expirado.blade; marcarComoExpirado |
| [M10_COMPRESSAO_IMAGENS.md](M10_COMPRESSAO_IMAGENS.md) | Compressão de Imagens | ImageCompressorService; prévia vs completo; thumbnail 600px |
| [M11_FRONTEND_UI.md](M11_FRONTEND_UI.md) | Frontend e UI | Paleta de cores; tipografia; componentes Bootstrap; layout por tela |
| [M12_RENOVAR_LINK_EXPIRADO.md](M12_RENOVAR_LINK_EXPIRADO.md) | Renovar Link Expirado | Botão Renovar no ClientManager; escolhe dias; mantém token; sem migration |
| [M13_DASHBOARD_NUMEROS.md](M13_DASHBOARD_NUMEROS.md) | Dashboard Números | 3 cards: publicados, clientes, fotos; alerta links expirando em 7 dias |
| [M14_FOTO_CAPA_TRABALHO.md](M14_FOTO_CAPA_TRABALHO.md) | Foto de Capa | Thumbnail da 1ª foto no card do dashboard; fallback placeholder rosa |
| [M15_REORDENAR_FOTOS.md](M15_REORDENAR_FOTOS.md) | Reordenar Fotos | Drag & drop com SortableJS; método reordenar(); ordem salva no banco |
| [M16_ALTERAR_SENHA.md](M16_ALTERAR_SENHA.md) | Alterar Senha | Página /admin/perfil; Hash::check; campos limpos após salvar |

## Testes por módulo (loop de validação)

| Arquivo | Testa | Comando |
|---------|-------|---------|
| [../testes/T12_RENOVAR_LINK_EXPIRADO.md](../testes/T12_RENOVAR_LINK_EXPIRADO.md) | M12 | `--filter=RenovarLinkTest` |
| [../testes/T13_DASHBOARD_NUMEROS.md](../testes/T13_DASHBOARD_NUMEROS.md) | M13 | `--filter=DashboardNumerosTest` |
| [../testes/T14_FOTO_CAPA_TRABALHO.md](../testes/T14_FOTO_CAPA_TRABALHO.md) | M14 | `--filter=FotoCapaTest` |
| [../testes/T15_REORDENAR_FOTOS.md](../testes/T15_REORDENAR_FOTOS.md) | M15 | `--filter=ReordenarFotosTest` |
| [../testes/T16_ALTERAR_SENHA.md](../testes/T16_ALTERAR_SENHA.md) | M16 | `--filter=AlterarSenhaTest` |

## Regras críticas (ler antes de qualquer mudança)

1. **Autenticação**: NUNCA usar `Auth::attempt()`. Coluna é `senha`, não `password`.
2. **Migrations**: NUNCA alterar migrations já rodadas. Criar nova migration.
3. **Credenciais**: NUNCA subir `credentials.json` no git.
4. **Idioma**: Colunas PT-BR, mensagens PT-BR, confirmações PT-BR.
5. **Público-alvo**: Adultos/idosos — botões grandes, textos claros, sem jargão.
6. **Ações destrutivas**: Sempre `wire:confirm` antes de excluir.
7. **Feedback**: Toda ação bem-sucedida exibe toast de notificação.
8. **Upload**: Aceita até 200MB por arquivo (php.ini customizado).
