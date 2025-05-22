<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusChamado extends Model
{
    protected $fillable = [
        'nome',
    ];

    protected $table = 'status_chamados';
}
