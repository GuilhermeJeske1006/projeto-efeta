<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PessoaEndereco extends Model
{
    protected $table = 'enderecos_pessoas';

    protected $fillable = [
        'pessoa_id',
        'endereco_id',
    ];

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function endereco()
    {
        return $this->belongsTo(Endereco::class);
    }
}
