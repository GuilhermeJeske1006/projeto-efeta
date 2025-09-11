<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermissaoUsuarioRetiro extends Model
{
    protected $table = 'permissao_usuario_retiros';

    protected $fillable = [
        'user_id',
        'retiro_id',
        'equipe_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function retiro()
    {
        return $this->belongsTo(Retiro::class);
    }

    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }
}
