<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Retiro extends Model
{
    protected $fillable = [
        'nome',
        'descricao',
        'tema',
        'data_inicio',
        'data_fim',
        'musica_tema',
    ];

    protected $table = 'retiros';
}
