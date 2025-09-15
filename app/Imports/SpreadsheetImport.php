<?php

namespace App\Imports;

use App\Models\Pessoa;
use App\Models\Telefone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;

class SpreadsheetImport implements ToModel
{
    public function model(array $row)
    {
        try {

            // Converte o valor Excel serial para data, se necessário
            if (is_numeric($row[14])) {
                $dataNascimento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row[14])->format('Y-m-d');
            } else {
                $dataNascimento = $row[14];
            }
            
            $pessoa = Pessoa::create([
                'email' => $row[1], // beamunizdemoura@gmail.com
                'data_nascimento' => $dataNascimento, // 2007-12-06
                'sacramento' => $row[15], // Crisma
                'motivo' => $row[16], // Porque as pessoas ao meu redor fizeram...
                'genero' => $row[18] ?: 'Outro', // Feminino
                // 'cidade' => $row[19], // Brusque
                'nome' => $row[21], // Beatriz muniz de moura
                'tipo_pessoa_id' => 3, 
                'estado_civil' => $row[22], // Solteiro
                'is_problema_saude' => 0,
                'religiao' => $row[23], // Católica
            ]);

            $endereco = new \App\Models\Endereco([
                'cidade' => $row[19], // Brusque
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
                'numero' => $row[17], 
                'is_principal' => true,
            ]);

            Telefone::create([
                'pessoa_id' => $pessoa->id,
                'tipo' => 'Celular',
                'nome_pessoa' => 'Alternativo',
                'numero' => $row[20], 
                'is_principal' => false,
            ]);

            return $pessoa;



        } catch (\Exception $e) {
            Log::error('Error logging row: ' . $e->getMessage());
        }

    }
}
