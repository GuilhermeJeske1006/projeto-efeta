<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPessoa extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
    ];

    public function pessoas()
    {
        return $this->hasMany(Pessoa::class);
    }
}
