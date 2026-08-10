<?php

namespace App\Exports;

use App\Models\PessoaRetiro;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ServosExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $equipeId,
        protected int $retiroId,
        protected ?string $statusId = null,
    ) {}

    public function query()
    {
        return PessoaRetiro::query()
            ->where('equipe_id', $this->equipeId)
            ->where('retiro_id', $this->retiroId)
            ->when($this->statusId, fn ($q) => $q->where('status_id', $this->statusId))
            ->with(['pessoa.telefones', 'pessoa.enderecos'])
            ->join('status_chamados', 'pessoa_retiros.status_id', '=', 'status_chamados.id')
            ->join('equipes', 'pessoa_retiros.equipe_id', '=', 'equipes.id')
            ->select(
                'pessoa_retiros.*',
                'status_chamados.nome as status',
                'equipes.nome as equipe'
            )
            ->orderByDesc('pessoa_retiros.is_coordenador');
    }

    public function headings(): array
    {
        return [
            'Equipe',
            'Nome',
            'Genero',
            'Cidade',
            'Telefone Principal',
            'Outros Telefones',
            'Coordenador',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row->equipe,
            $row->pessoa->nome ?? 'N/A',
            $row->pessoa->genero ?? 'N/A',
            $row->pessoa->enderecos->first()->cidade ?? 'N/A',
            $row->pessoa->telefones->first()->numero ?? 'N/A',
            $row->pessoa->telefones->skip(1)->pluck('numero')->implode(' / ') ?: 'N/A',
            $row->is_coordenador ? 'Sim' : 'Não',
            $row->status,
        ];
    }
}
