<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pessoas', function (Blueprint $table) {
            $table->string('sacramento')->nullable();
            $table->text('motivo')->nullable();
            $table->string('comunidade')->nullable();
            $table->string('religiao')->nullable();
            $table->text('gostaria_de_trabalhar')->nullable();
            $table->text('trabalha_onde_comunidade')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
