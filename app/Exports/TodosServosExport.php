<?php

namespace App\Exports;

use App\Models\PermissaoUsuarioRetiro;
use App\Models\PessoaRetiro;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TodosServosExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        protected int $userId,
        protected array $searches = [],
    ) {}

    public function query()
    {
        $permissoes = PermissaoUsuarioRetiro::where('user_id', $this->userId)->get();

        $conditions = $permissoes->map(fn ($p) => [
            'equipe_id' => $p->equipe_id,
            'retiro_id' => $p->retiro_id,
            'status_id' => $this->searches['status_' . $p->equipe_id . '_' . $p->retiro_id] ?? null,
        ]);

        return PessoaRetiro::query()
            ->where(function ($query) use ($conditions) {
                foreach ($conditions as $condition) {
                    $query->orWhere(function ($q) use ($condition) {
                        $q->where('pessoa_retiros.equipe_id', $condition['equipe_id'])
                          ->where('pessoa_retiros.retiro_id', $condition['retiro_id'])
                          ->when($condition['status_id'], fn ($q) => $q->where('pessoa_retiros.status_id', $condition['status_id']));
                    });
                }
            })
            ->with(['pessoa.telefones'])
            ->join('status_chamados', 'pessoa_retiros.status_id', '=', 'status_chamados.id')
            ->join('equipes', 'pessoa_retiros.equipe_id', '=', 'equipes.id')
            ->join('retiros', 'pessoa_retiros.retiro_id', '=', 'retiros.id')
            ->select(
                'pessoa_retiros.*',
                'status_chamados.nome as status',
                'equipes.nome as equipe',
                'retiros.nome as retiro'
            )
            ->orderBy('retiros.nome')
            ->orderBy('equipes.nome')
            ->orderByDesc('pessoa_retiros.is_coordenador');
    }

    public function headings(): array
    {
        return [
            'Equipe',
            'Nome',
            'Genero',
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
            $row->pessoa->telefones->first()->numero ?? 'N/A',
            $row->pessoa->telefones->skip(1)->pluck('numero')->implode(' / ') ?: 'N/A',
            $row->is_coordenador ? 'Sim' : 'Não',
            $row->status,
        ];
    }
}
