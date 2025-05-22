<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telefone extends Model
{
    protected $table = 'telefones';

    protected $fillable = [
        'numero',
        'tipo',
        'nome_pessoa',
        'pessoa_id',
        'is_principal',
    ];

    protected $casts = [
        'is_principal' => 'boolean',
    ];



    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }
}
