# Laravel 11

- Tabelas e colunas em PT-BR (nome, telefone, titulo, data_trabalho, etc.)
- Models: $table explícito, $fillable, $casts, SoftDeletes em TODOS
- Form Requests para validação
- Eloquent relationships: belongsTo, hasMany, belongsToMany com pivot
- Rotas agrupadas: prefix('admin'), middleware('auth')
- Rotas públicas /galeria/* sem middleware
- Tokens: Str::random(64)
- Senhas: bcrypt()
- Datas PT-BR: translatedFormat('d \d\e F \d\e Y')
- CSRF em todos os forms
- Storage disk 'public' com storage:link
- Queue: sync (sem Redis)
- Cache: file
- AUTH ESPECIAL: tabela usa 'senha' não 'password'. Login manual com Hash::check() + Auth::login(). Sobrescrever getAuthPassword() no Model Usuario.
