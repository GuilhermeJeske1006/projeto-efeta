<?php

use App\Models\Retiro;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed, on};
use Livewire\WithFileUploads;

// Define state properties
state([
    // Pessoa data
    'formData' => [
        'nome' => '',
        'descricao' => '',
        'tema' => '',
        'data_inicio' => '',
        'data_fim' => '',
        'musica_tema' => '',
    ],

    'notification' => [
        'show' => false,
        'type' => '',
        'message' => '',
    ],

    'dadosFormulario' => [],
]);

mount(function () {
    if(auth()->user()->role_id !== 1) {
        abort(403, 'Acesso não autorizado');
    }
});

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

        // Save person
        $retiro = Retiro::create([
            'nome' => $dados['nome'],
            'descricao' => $dados['descricao'] ?: null,
            'tema' => $dados['tema'] ?: null,
            'data_inicio' => $dados['data_inicio'] ?: null,
            'data_fim' => $dados['data_fim'] ?: null,
            'musica_tema' => $dados['musica_tema'] ?: null,
        ]);

        DB::commit();

        $this->showNotification('success', 'Retiro cadastrado com sucesso!');

        // Limpar dados e resetar formulário filho
        $this->dadosFormulario = [];
        $this->dispatch('reset-form');
    } catch (\Exception $e) {
        DB::rollBack();

        $this->showNotification('error', 'Erro ao cadastrar retiro: ' . $e->getMessage());
    }
};

?>

<section class="w-full">
    <livewire:components.notification :notification="$notification" />

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Cadastro de retiro</h1>
            <p class="">Informe os dados abaixo e cadastre um novo retiro</p>
        </div>
        <a href="{{ route('servos.index') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Voltar a lista
        </a>
    </div>
    <div>
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <livewire:retiros.form :dados="$formData" @dados-updated="$set('formData', $event)" />
        </div>
    </div>
</section>
