<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Pessoa>
 */
class PessoaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nome' => $this->faker->name,
            'cpf' => $this->faker->unique()->numerify('###.###.###-##'),
            'data_nascimento' => $this->faker->date,
            'email' => $this->faker->unique()->safeEmail,
            'tipo_pessoa_id' => 3,
            'is_problema_saude' => $this->faker->boolean,
            'descricao' => $this->faker->optional()->sentence,
            'ja_trabalhou' => $this->faker->boolean,
            'genero' => $this->faker->randomElement(['masculino', 'feminino', 'outro']),
            'estado_civil' => $this->faker->randomElement(['solteiro', 'casado', 'divorciado', 'viúvo']),
            
        ];
    }
}
