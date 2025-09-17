<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed, on};
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;



// Define state properties
state([
    // Pessoa data
    'formData' => [
        'nome' => '',
        'cpf' => '',
        'email' => '',
        'role_id' => '',
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
        $existingPessoa = User::where('email', $dados['email'])->first();

        if ($existingPessoa) {
            $this->showNotification('error', 'E-mail já cadastrado.');
            return;
        }

        // Save person
        $pessoa = User::create([
            'name' => $dados['nome'],
            'telefone' => $dados['telefone'] ?: null,
            'email' => $dados['email'] ?: null,
            'role_id' => $dados['role_id'] ?: null,
            'password' => Hash::make(Str::random(32)), // Senha temporária aleatória
        ]);

        Password::sendResetLink(['email' => $pessoa->email]);

        DB::commit();

        $this->showNotification('success', 'Usuario cadastrada com sucesso!');

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
            <h1 class="text-2xl font-bold">Cadastro de usuarios</h1>
            <p class="">Informe os dados abaixo e cadastre um novo usuarios</p>
        </div>
        <a href="{{ route('pessoas.index') }}"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full md:w-auto justify-center">
        Voltar a lista
        </a>
    </div>
    <div>
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <livewire:configuracoes.pessoas.form :dados="$formData" @dados-updated="$set('formData', $event)" />
        </div>
    </div>
</section>
