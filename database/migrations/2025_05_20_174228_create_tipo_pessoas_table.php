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
        Schema::create('tipo_pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique();
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        // Populating the tipo_pessoas table with default values
        DB::table('tipo_pessoas')->insert([
            ['nome' => 'Servo', 'descricao' => 'Pessoa que presente na equipe de trabalho'],
            ['nome' => 'Retirante', 'descricao' => 'Pessoa que esta fazendo ou em breve fará o retiro'],
            ['nome' => 'Futuro Retirante', 'descricao' => 'Pessoa que esta na lista de espera para o retiro'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipo_pessoas');
    }
};
