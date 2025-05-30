<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    protected $table = 'pessoas';

    protected $fillable = [
        'nome',
        'cpf',
        'data_nascimento',
        'email',
        'tipo_pessoa_id',
        'is_problema_saude',
        'descricao',
        'ja_trabalhou',
        'genero',
        'estado_civil',
    ];

    protected $casts = [
        'is_problema_saude' => 'boolean',
        'ja_trabalhou'      => 'boolean',
    ];

    public function telefones()
    {
        return $this->hasMany(Telefone::class);
    }

    public function telefonePrincipal()
    {
        return $this->hasOne(Telefone::class)->where('is_principal', true);
    }

    public function endereco()
    {
        return $this->hasOne(Endereco::class);
    }

    

    public function tipoPessoa()
    {
        return $this->belongsTo(TipoPessoa::class);
    }
}
