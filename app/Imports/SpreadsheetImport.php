<?php

namespace App\Imports;

use App\Models\Pessoa;
use App\Models\Telefone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;

class SpreadsheetImport implements ToModel
{
    // public function model(array $row)
    // {
    //     try {
    //         // Log::info('Importando linha', ['row' => $row[14]]);

    //         // Converte o valor Excel serial para data, se necessário
    //         if (is_numeric($row[19])) {
    //             $dataNascimento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[19])->format('Y-m-d');
    //         } else {
    //             // Verifica se o valor é uma data válida no formato esperado
    //             $dataNascimento = \DateTime::createFromFormat('Y-m-d', $row[19]) ? $row[19] : null;
    //         }

    //         Log::info($row);

    //         $pessoa = Pessoa::create([
    //             'email' => $row[1], // beamunizdemoura@gmail.com
    //             'data_nascimento' => $dataNascimento, // 2007-12-06
    //             'sacramento' => $row[18], // Crisma
    //             'motivo' => $row[20], // Porque as pessoas ao meu redor fizeram...
    //             'genero' => $row[16] ?: 'Outro', // Feminino
    //             'nome' => $row[14], // Beatriz muniz de moura
    //             'tipo_pessoa_id' => 3, 
    //             'estado_civil' => $row[17], // Solteiro
    //             'is_problema_saude' => 0, 
    //             'religiao' => 'Católica'
    //         ]);

    //         $endereco = new \App\Models\Endereco([
    //             'cidade' => $row[15], // Brusque
    //             'pais' => 'Brasil',
    //             'estado' => 'SC',
    //             'bairro' => 'Não informado',
    //             'cep' => 'Não informado',
    //             'numero' => 'Não informado',
    //             'complemento' => 'Não informado',
    //             'logradouro' => 'Não informado',
    //         ]);

    //         $endereco->save();

    //         DB::table('enderecos_pessoas')->insert([
    //             'pessoa_id' => $pessoa->id,
    //             'endereco_id' => $endereco->id,
    //         ]);


    //         Telefone::create([
    //             'pessoa_id' => $pessoa->id,
    //             'tipo' => 'Celular',
    //             'nome_pessoa' => 'Whatsapp',
    //             'numero' => $row[21], 
    //             'is_principal' => true,
    //         ]);

    //         Telefone::create([
    //             'pessoa_id' => $pessoa->id,
    //             'tipo' => 'Celular',
    //             'nome_pessoa' => 'Alternativo',
    //             'numero' => $row[22], 
    //             'is_principal' => false,
    //         ]);

    //         return $pessoa;

    //     } catch (\Exception $e) {
    //         Log::error('Erro ao importar linha', [
    //             'mensagem' => $e->getMessage(),
    //             'linha' => $row,
    //         ]);
    //     }
    // }

    public function model(array $row)
    {
        try {
            // Log::info('Importando linha', ['row' => $row[14]]);

            // Converte o valor Excel serial para data, se necessário
            if (is_numeric($row[11])) {
                $dataNascimento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[11])->format('Y-m-d');
            } else {
                // Verifica se o valor é uma data válida no formato esperado
                $dataNascimento = \DateTime::createFromFormat('Y-m-d', $row[11]) ? $row[11] : null;
            }

            Log::info($row);

            $pessoa = Pessoa::create([
                'email' => $row[1], // beamunizdemoura@gmail.com
                'data_nascimento' => $dataNascimento, // 2007-12-06
                'sacramento' => $row[8], // Crisma
                'motivo' => $row[10], // Porque as pessoas ao meu redor fizeram...
                'genero' => $row[13] ?: 'Outro', // Feminino
                'nome' => $row[2], // Beatriz muniz de moura
                'tipo_pessoa_id' => 3, 
                'estado_civil' => $row[12], // Solteiro
                'is_problema_saude' => 0, 
                'religiao' => $row[9],
                'comunidade' => $row[7],
            ]);

            $endereco = new \App\Models\Endereco([
                'cidade' => $row[6], // Brusque
                'pais' => 'Brasil',
                'estado' => 'SC',
                'bairro' => 'Não informado',
                'cep' => 'Não informado',
                'numero' => 'Não informado',
                'complemento' => 'Não informado',
                'logradouro' => 'Não informado',
            ]);

            $endereco->save();

            DB::table('enderecos_pessoas')->insert([
                'pessoa_id' => $pessoa->id,
                'endereco_id' => $endereco->id,
            ]);


            Telefone::create([
                'pessoa_id' => $pessoa->id,
                'tipo' => 'Celular',
                'nome_pessoa' => 'Whatsapp',
                'numero' => $row[4], 
                'is_principal' => true,
            ]);

            // Telefone::create([
            //     'pessoa_id' => $pessoa->id,
            //     'tipo' => 'Celular',
            //     'nome_pessoa' => 'Alternativo',
            //     'numero' => $row[22], 
            //     'is_principal' => false,
            // ]);

            return $pessoa;

        } catch (\Exception $e) {
            Log::error('Erro ao importar linha', [
                'mensagem' => $e->getMessage(),
                'linha' => $row,
            ]);
        }
    }
}
