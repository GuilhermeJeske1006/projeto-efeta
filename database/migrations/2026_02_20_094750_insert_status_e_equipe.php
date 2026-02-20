<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    public function up(): void
    {
        DB::table('status_chamados')->insert([
            ['nome' => 'Não tem mais interesse', 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('equipes')->insert([
            ['nome' => 'Ligação', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        DB::table('status_chamados')->where('nome', 'Não tem mais interesse')->delete();
        DB::table('equipes')->where('nome', 'Ligação')->delete();
    }
};
