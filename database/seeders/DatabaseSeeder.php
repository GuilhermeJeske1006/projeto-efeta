<?php

namespace Database\Seeders;

use App\Models\Pessoa;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Pessoa::factory()->count(50)->create();
        // User::factory()->count(50)->create();

        \App\Models\User::factory()->create([
            'name' => 'Guilherme Jeske',
            'email' => 'guilhermeieski@gmail.com',
            'email_verified_at' => now(),
            'password' =>  Hash::make('password'),
            'remember_token'    => Str::random(10),
            'telefone' => '47999999999',
            'role_id' => 1,
        ]);


        // User::factory()->create([
        //     'name'  => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
