<?php

namespace App\Console\Commands;

use App\Models\Pessoa;
use App\Models\PessoaRetiro;
use Illuminate\Console\Command;

class AtualizarTipoPessoaRetiro extends Command
{
    protected $signature = 'pessoas:atualizar-tipo-retiro {retiro_id : ID do retiro}';

    protected $description = 'Atualiza o tipo_pessoa_id para 1 (Servo) de todas as pessoas atreladas a um retiro especificado';

    public function handle(): int
    {
        $retiroId = $this->argument('retiro_id');

        $pessoaIds = PessoaRetiro::where('retiro_id', $retiroId)->where('status_id', 7)->pluck('pessoa_id')->unique();

        if ($pessoaIds->isEmpty()) {
            $this->warn("Nenhuma pessoa encontrada para o retiro de ID {$retiroId}.");
            return Command::SUCCESS;
        }

        $total = Pessoa::whereIn('id', $pessoaIds)->update(['tipo_pessoa_id' => 1]);

        $this->info("Total de pessoas atualizadas: {$total}");

        return Command::SUCCESS;
    }
}
