# CORREÇÃO — Upload não aceita arquivos grandes (limite 200MB)

O upload está rejeitando arquivos acima de poucos KB. Precisa aceitar até 200MB por arquivo.

## O que corrigir (TODOS os pontos abaixo, senão não funciona):

### 1. php.ini do PHP rodando no servidor
Localizar o php.ini ativo (`php --ini`) e alterar:
```ini
upload_max_filesize = 200M
post_max_size = 250M
max_execution_time = 300
memory_limit = 512M
```
Após alterar, reiniciar o servidor (`php artisan serve` de novo).

### 2. Configuração do Livewire (`config/livewire.php`)
Se o arquivo não existir, rodar `php artisan livewire:publish --config`. Alterar:
```php
'temporary_file_upload' => [
    'disk' => 'local',
    'rules' => ['required', 'file', 'max:204800'], // 204800 KB = 200MB
    'directory' => 'livewire-tmp',
    'middleware' => null,
    'preview_mimes' => ['png', 'gif', 'bmp', 'svg', 'wav', 'mp4', 'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg', 'mpga', 'webp', 'wma'],
    'max_upload_time' => 300, // 5 minutos para upload grande
    'cleanup' => true,
],
```

### 3. Validação no componente PhotoUploader
No método que valida os arquivos, o `max` deve ser `204800` (em KB):
```php
$this->validate([
    'arquivos.*' => 'file|mimes:jpg,jpeg,png,psd,tif,tiff|max:204800',
]);
```

### 4. Input HTML no Blade do PhotoUploader
Garantir que o input NÃO tem atributo `maxlength` ou `size` limitando:
```html
<input type="file" wire:model="arquivos" multiple accept=".jpg,.jpeg,.png,.psd,.tif,.tiff" hidden>
```

### 5. Nginx (se estiver usando Nginx em vez de `php artisan serve`)
No bloco `server` ou `http` do nginx.conf:
```nginx
client_max_body_size 250M;
```
Reiniciar nginx após alterar.

## Ordem de execução
1. Alterar php.ini
2. Publicar e alterar config/livewire.php
3. Alterar validação no PhotoUploader
4. Reiniciar o servidor
5. Testar upload de arquivo de ~50MB para confirmar
