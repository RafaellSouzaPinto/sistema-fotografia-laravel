# Mapa de Rotas Completo

Arquivo de referência: `routes/web.php`

## Rotas Públicas (sem autenticação)

| Método | URI | Handler | View |
|--------|-----|---------|------|
| GET | `/` | `HomeController@index` | `home.blade.php` |
| GET | `/login` | `LoginController@showLoginForm` | `auth/login.blade.php` |
| POST | `/login` | `LoginController@login` | redirect `/admin/dashboard` |
| POST | `/logout` | `LoginController@logout` | redirect `/login` |

## Rotas da Galeria Pública (sem autenticação)

| Método | URI | Handler | Descrição |
|--------|-----|---------|-----------|
| GET | `/galeria/{token}` | `GalleryController@show` | Exibe galeria do cliente |
| GET | `/galeria/{token}/download` | `GalleryController@downloadTodas` | ZIP com todas as fotos |
| GET | `/galeria/{token}/foto/{foto}` | `GalleryController@downloadFoto` | Download de foto individual |

> O parâmetro `{token}` é o campo `token` da tabela `trabalho_cliente` (varchar 64, único).
> O parâmetro `{foto}` é o ID da tabela `fotos`.

## Rotas Administrativas (middleware: `auth`, prefix: `/admin`)

| Método | URI | Handler | Descrição |
|--------|-----|---------|-----------|
| GET | `/admin/dashboard` | Livewire `Admin\JobList` | Lista de trabalhos |
| GET | `/admin/jobs/create` | Livewire `Admin\JobForm` | Criar novo trabalho |
| GET | `/admin/jobs/{id}/edit` | Livewire `Admin\JobForm` | Editar trabalho existente |
| GET | `/admin/clients` | Livewire `Admin\ClientList` | Lista de clientes |
| GET | `/admin/thumbnail/{foto}` | inline closure | Proxy de thumbnail local |

## Rota de Thumbnail (proxy)

```php
Route::get('/admin/thumbnail/{foto}', function (Foto $foto) {
    // Retorna thumbnail local de storage/app/public/
    // Fallback para drive_thumbnail (URL externa)
})->middleware('auth');
```

## Componentes Livewire por rota

| Rota | Componente principal | Componentes filhos |
|------|---------------------|-------------------|
| `/admin/dashboard` | `Admin\JobList` | — |
| `/admin/jobs/create` | `Admin\JobForm` | `Admin\ClientManager`, `Admin\PhotoUploader` |
| `/admin/jobs/{id}/edit` | `Admin\JobForm` | `Admin\ClientManager`, `Admin\PhotoUploader` |
| `/admin/clients` | `Admin\ClientList` | — |
| `/galeria/{token}` | — (controller puro) | — |

## Middleware

- `auth` — verifica autenticação via `app/Http/Middleware/` (padrão Laravel)
- Não há middleware customizado além do padrão

## Nomes de rotas (se precisar de `route()`)

As rotas administrativas devem usar nomes para evitar hardcode de URLs. Verificar `routes/web.php` para nomes definidos com `->name()`.
