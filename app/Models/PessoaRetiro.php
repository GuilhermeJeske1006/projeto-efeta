<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PessoaRetiro extends Model
{
    protected $fillable = [
        'pessoa_id',
        'retiro_id',
        'equipe_id',
        'tipo_id',
        'status_chamado_id',
    ];

    protected $table = 'pessoas_retiros';

    public function pessoa()
    {
        return $this->belongsTo(Pessoa::class);
    }

    public function retiro()
    {
        return $this->belongsTo(Retiro::class);
    }

    public function tipoPessoa()
    {
        return $this->belongsTo(TipoPessoa::class, 'tipo_id');
    }

    public function equipe()
    {
        return $this->belongsTo(Equipe::class);
    }

    public function statusChamado()
    {
        return $this->belongsTo(StatusChamado::class);
    }

}
