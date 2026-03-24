# Controllers — Contexto para Claude Code

## Estrutura
- `Admin/` → Controllers protegidos por middleware auth
- `GalleryController.php` → Rota pública da galeria (sem auth)
- `Auth/LoginController.php` → Login manual (Hash::check + Auth::login)

## Rotas admin (middleware: auth, prefix: /admin)
| Método | Rota | Controller | Ação |
|--------|------|-----------|------|
| GET | /admin/dashboard | Livewire JobList | Listar trabalhos |
| GET | /admin/jobs/create | Livewire JobForm | Novo trabalho |
| GET | /admin/jobs/{id}/edit | Livewire JobForm | Editar trabalho |
| GET | /admin/clients | Livewire ClientList | Listar clientes |

## Rotas públicas (sem auth)
| Método | Rota | Controller | Ação |
|--------|------|-----------|------|
| GET | /galeria/{token} | GalleryController@show | Galeria do cliente |
| GET | /galeria/{token}/download | GalleryController@downloadTodas | ZIP com todas as fotos |
| GET | /galeria/{token}/foto/{foto} | GalleryController@downloadFoto | Download foto individual |

## Regras
- GalleryController SEMPRE valida que o token pertence ao trabalho da foto
- Download ZIP só permitido se trabalho.tipo = 'completo'
- Login usa Hash::check() manual (coluna 'senha', não 'password')
