<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trabalho extends Model
{
    use SoftDeletes;

    protected $table = 'trabalhos';

    protected $fillable = ['titulo', 'data_trabalho', 'tipo', 'status', 'drive_pasta_id'];

    protected $casts = [
        'data_trabalho' => 'date',
    ];

    public function clientes()
    {
        return $this->belongsToMany(Cliente::class, 'trabalho_cliente', 'trabalho_id', 'cliente_id')
            ->withPivot('token', 'id', 'expira_em', 'status_link')
            ->withTimestamps();
    }

    public function todosLinksExpirados(): bool
    {
        if ($this->clientes()->count() === 0) return false;

        return $this->clientes()
            ->wherePivot('status_link', 'disponivel')
            ->wherePivot('expira_em', '>', now())
            ->count() === 0;
    }

    public function temLinksExpirados(): bool
    {
        if ($this->clientes()->wherePivot('status_link', 'expirado')->exists()) {
            return true;
        }

        return $this->clientes()
            ->wherePivotNotNull('expira_em')
            ->wherePivot('expira_em', '<', now())
            ->exists();
    }

    public function fotos()
    {
        return $this->hasMany(Foto::class, 'trabalho_id');
    }
}
