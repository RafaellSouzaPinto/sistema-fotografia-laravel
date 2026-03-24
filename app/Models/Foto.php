<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Foto extends Model
{
    use SoftDeletes;

    protected $table = 'fotos';

    protected $fillable = ['trabalho_id', 'nome_arquivo', 'drive_arquivo_id', 'drive_thumbnail', 'tamanho_bytes', 'ordem'];

    public function trabalho()
    {
        return $this->belongsTo(Trabalho::class);
    }
}
