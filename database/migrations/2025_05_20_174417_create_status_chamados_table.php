<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('status_chamados', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->timestamps();
        });

        // Populating the status_chamados table with default values
        DB::table('status_chamados')->insert([
            ['nome' => 'Não chamado'],
            ['nome' => 'Para chamar'],
            ['nome' => 'chamado'],
            ['nome' => 'Atendido'],
            ['nome' => 'Em espera por resposta'],
            ['nome' => 'Em espera por atendimento'],
            ['nome' => 'Aceito'],
            ['nome' => 'Recusado'],
            ['nome' => 'Recusado pela segunda vez'],
            ['nome' => 'Recusado pela terceira vez'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_chamados');
    }
};
