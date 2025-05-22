<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('email')->unique();
            $table->foreignId('tipo_pessoa_id')->constrained('tipo_pessoas')->onDelete('cascade');
            $table->boolean('is_problema_saude')->default(false);
            $table->text('descricao')->nullable();
            $table->string('genero')->default('masculino');
            $table->string('estado_civil')->default('solteiro');
            $table->boolean('ja_trabalhou')->default(false);
            $table->string('cpf')->unique();
            $table->date('data_nascimento')->nullable();
            $table->timestamps();
        });

        Schema::create('enderecos_pessoas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pessoa_id')->constrained('pessoas')->onDelete('cascade');
            $table->foreignId('endereco_id')->constrained('enderecos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pessoas');
    }
};
