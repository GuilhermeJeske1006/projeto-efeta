<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('equipes', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
        });

        Schema::table('pessoa_retiros', function(Blueprint $table) {
            $table->foreignId('equipe_id')->constrained('equipes')->onDelete('cascade');
        });

        DB::table('equipes')->insert([
            ['nome' => 'Coordenação'],
            ['nome' => 'Música'],
            ['nome' => 'Animação'],
            ['nome' => 'Cantinho'],
            ['nome' => 'Tropa de elite'],
            ['nome' => 'Teatro'],
            ['nome' => 'Servos'],
            ['nome' => 'Capela'],
            ['nome' => 'Externa'],
            ['nome' => 'Manutenção'],
            ['nome' => 'Farmacia'],
            ['nome' => 'Secretaria'],
            ['nome' => 'Cozinha'],
        ]);


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('equipes');
    }
};
