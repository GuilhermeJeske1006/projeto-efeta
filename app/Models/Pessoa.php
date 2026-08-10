<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pessoa extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;
    
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
        'sacramento',
        'motivo',
        'comunidade',
        'religiao',
        'gostaria_de_trabalhar',
        'trabalha_onde_comunidade',
        'ja_fez_retiro',
        'created_at',
    ];

    protected $casts = [
        'is_problema_saude' => 'boolean',
        'ja_trabalhou'      => 'boolean',
        'ja_fez_retiro'     => 'boolean',
    ];

    public function telefones()
    {
        return $this->hasMany(Telefone::class);
    }

    public function telefonePrincipal()
    {
        return $this->hasOne(Telefone::class)->where('is_principal', true);
    }

    public function enderecos()
    {
        return $this->belongsToMany(Endereco::class, 'enderecos_pessoas');
    }

    

    public function tipoPessoa()
    {
        return $this->belongsTo(TipoPessoa::class);
    }
}
