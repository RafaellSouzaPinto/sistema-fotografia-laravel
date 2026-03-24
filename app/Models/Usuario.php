<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class Usuario extends Authenticatable
{
    use SoftDeletes;

    protected $table = 'usuarios';

    protected $fillable = ['nome', 'email', 'senha'];

    protected $hidden = ['senha'];

    public function getAuthPassword()
    {
        return $this->senha;
    }
}
