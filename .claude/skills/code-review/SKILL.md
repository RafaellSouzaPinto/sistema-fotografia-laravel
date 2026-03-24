# Code Review

## Segurança
- credentials.json no .gitignore
- CSRF em todos os forms
- Validar token da galeria (pertence ao trabalho da foto?)
- Middleware auth em /admin/*
- Não expor URLs do Google Drive

## Qualidade
- Sem dd(), dump(), console.log()
- Sem código morto/comentado
- Mensagens em português
- wire:confirm em toda ação destrutiva

## Performance
- loading="lazy" em imagens
- withCount/with para evitar N+1
- Paginação em listagens grandes
- Select de usuários com Ajax (não carregar todos)

## UX (público: adultos/idosos 55+)
- Textos claros, sem jargão técnico
- Botões grandes e legíveis com ícone + texto
- Toast em toda ação
- Estado vazio com mensagem amigável
- Confirmação: "Tem certeza que deseja excluir este trabalho?"
