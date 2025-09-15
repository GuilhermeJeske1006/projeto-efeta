<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\YourModel; // Substitua pelo modelo correspondente
use App\Imports\SpreadsheetImport;
use Illuminate\Support\Facades\Log;

class SpreadsheetController extends Controller
{
    public function upload(Request $request)
    {
        try {
            Log::info('Recebendo requisição para upload', ['request' => $request->all()]);

            $request->validate([
                'file' => 'required|file|mimes:xlsx,csv',
            ]);

            Log::info('Arquivo recebido com sucesso', ['file' => $request->file('file')->getClientOriginalName()]);

            Excel::import(new SpreadsheetImport, $request->file('file'));

            return response()->json(['message' => 'Dados importados com sucesso!']);
        } catch (\Exception $th) {
            Log::error('Erro ao importar arquivo', ['error' => $th->getMessage()]);
            return response()->json(['error' => 'Erro ao importar arquivo'], 500);
        }
    }
}
