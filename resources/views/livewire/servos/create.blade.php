<?php

use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use App\Models\TipoPessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed, on};
use Livewire\WithFileUploads;

// Define state properties
state([
    // Pessoa data
    'formData' => [
        'nome' => '',
        'cpf' => '',
        'data_nascimento' => '',
        'email' => '',
        'tipo_pessoa_id' => '',
        'is_problema_saude' => false,
        'descricao' => '',
        'ja_trabalhou' => false,
        'genero' => '',
        'estado_civil' => '',

        // Endereço data
        'logradouro' => '',
        'numero' => '',
        'complemento' => '',
        'bairro' => '',
        'cidade' => '',
        'estado' => '',
        'cep' => '',
        'pais' => 'Brasil',

        // Telefones data
        'telefones' => [],

        // Select lists
        'tiposPessoa' => [],
    ],

    'notification' => [
        'show' => false,
        'type' => '',
        'message' => '',
    ],

    'dadosFormulario' => [],
]);

on([
    'dados-formulario' => function ($dados) {
        $this->dadosFormulario = $dados;
        $this->salvar();
    },
    'notification-closed' => function () {
        $this->notification['show'] = false;
    },
]);

$showNotification = function ($type, $message) {
    $this->notification = [
        'show' => true,
        'type' => $type,
        'message' => $message,
    ];

    // Dispatch para o componente de notificação
    $this->dispatch('notify', [
        'type' => $type,
        'message' => $message,
    ]);
};

// Save the person record
$salvar = function () {
    if (empty($this->dadosFormulario)) {
        $this->showNotification('error', 'Nenhum dado recebido do formulário.');
        return;
    }

    try {
        DB::beginTransaction();

        $dados = $this->dadosFormulario;

        // Check if email or CPF already exists
        $existingPessoa = Pessoa::where('cpf', $dados['cpf'])->first();

        if ($existingPessoa) {
            $this->showNotification('error', 'Essa pessoa já existe na nossa base.');
            return;
        }

        // Save person
        $pessoa = Pessoa::create([
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'],
            'data_nascimento' => $dados['data_nascimento'] ?: null,
            'email' => $dados['email'] ?: null,
            'tipo_pessoa_id' => 1,
            'is_problema_saude' => $dados['is_problema_saude'] ?? false,
            'descricao' => $dados['descricao'] ?: null,
            'ja_trabalhou' => $dados['ja_trabalhou'] ?? false,
            'genero' => $dados['genero'] ?: null,
            'estado_civil' => $dados['estado_civil'] ?: null,
        ]);

        // Save address if provided
        if (!empty($dados['logradouro']) || !empty($dados['cep'])) {
            $endereco = Endereco::create([
                'logradouro' => $dados['logradouro'] ?: '',
                'numero' => $dados['numero'] ?: '',
                'complemento' => $dados['complemento'] ?: '',
                'bairro' => $dados['bairro'] ?: '',
                'cidade' => $dados['cidade'] ?: '',
                'estado' => $dados['estado'] ?: 'SC',
                'cep' => $dados['cep'] ?: '',
                'pais' => $dados['pais'] ?: 'Brasil',
            ]);

            // Relate person and address
            DB::table('enderecos_pessoas')->insert([
                'pessoa_id' => $pessoa->id,
                'endereco_id' => $endereco->id,
            ]);
        }

        // Save phone numbers if provided
        if (!empty($dados['telefones']) && is_array($dados['telefones'])) {
            foreach ($dados['telefones'] as $telefone) {
                if (!empty($telefone['numero'])) {
                    Telefone::create([
                        'numero' => $telefone['numero'],
                        'tipo' => $telefone['tipo'] ?? 'celular',
                        'nome_pessoa' => $telefone['nome_pessoa'] ?: $dados['nome'],
                        'pessoa_id' => $pessoa->id,
                        'is_principal' => $telefone['is_principal'] ?? false,
                    ]);
                }
            }
        }

        DB::commit();

        $this->showNotification('success', 'Pessoa cadastrada com sucesso!');

        // Limpar dados e resetar formulário filho
        $this->dadosFormulario = [];
        $this->dispatch('reset-form');
    } catch (\Exception $e) {
        DB::rollBack();

        $this->showNotification('error', 'Erro ao cadastrar pessoa: ' . $e->getMessage());
    }
};

?>

<section class="w-full">
    <livewire:components.notification :notification="$notification" />

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-5 gap-4">
        <div>
            <h1 class="text-2xl font-bold">Cadastro de servos</h1>
            <p class="">Informe os dados abaixo e cadastre um novo servo</p>
        </div>
        <a href="{{ route('servos.index') }}"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full md:w-auto justify-center">
            Voltar a lista
        </a>
    </div>
    <div>
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <livewire:servos.form :dados="$formData" @dados-updated="$set('formData', $event)" />
        </div>
    </div>
</section>
