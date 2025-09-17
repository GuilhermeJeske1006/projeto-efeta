<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed, on, updated};
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// Define state properties
state([
    // Pessoa data
    'formData' => [
        'user_id' => '',
        'retiro_id' => '',
        'equipe_id' => '',
    ],

    'notification' => [
        'show' => false,
        'type' => '',
        'message' => '',
    ],

    'dadosFormulario' => [],
    'permissoes' => [], // Adicionar estado para as permissões
    'usuarioSelecionado' => null, // ID do usuário selecionado
]);

// Computed property para carregar as permissões filtradas por usuário
$permissoesFiltradas = computed(function () {
    if (!$this->usuarioSelecionado) {
        return [];
    }
    
    return \App\Models\PermissaoUsuarioRetiro::with(['user', 'retiro', 'equipe'])
        ->where('user_id', $this->usuarioSelecionado)
        ->orderBy('created_at', 'desc')
        ->get();
});

// Mount function para carregar dados iniciais
mount(function () {
    $this->carregarPermissoes();
});

// Função para carregar permissões (quando não há usuário selecionado, retorna array vazio)
$carregarPermissoes = function () {
    if (!$this->usuarioSelecionado) {
        $this->permissoes = [];
        return;
    }
    
    $this->permissoes = \App\Models\PermissaoUsuarioRetiro::with(['user', 'retiro', 'equipe'])
        ->where('user_id', $this->usuarioSelecionado)
        ->orderBy('created_at', 'desc')
        ->get()
        ->toArray();
};

// Listener para quando o usuário for alterado no formulário
on([
    'dados-formulario' => function ($dados) {
        $this->dadosFormulario = $dados;
        $this->salvar();
    },
    'notification-closed' => function () {
        $this->notification['show'] = false;
    },
    'usuario-selecionado' => function ($userId) {
        $this->usuarioSelecionado = $userId;
        $this->carregarPermissoes();
    }
]);

// Watcher para quando formData.user_id mudar
updated(['formData.user_id' => function ($value) {
    $this->usuarioSelecionado = $value;
    $this->carregarPermissoes();
}]);

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

        // Verificar se a permissão já existe
        $permissaoExistente = \App\Models\PermissaoUsuarioRetiro::where([
            'user_id' => $dados['user_id'],
            'retiro_id' => $dados['retiro_id'],
            'equipe_id' => $dados['equipe_id'],
        ])->first();

        if ($permissaoExistente) {
            $this->showNotification('error', 'Esta permissão já existe para este usuário.');
            return;
        }

        // Save permission
        $permissao = \App\Models\PermissaoUsuarioRetiro::create([
            'user_id' => $dados['user_id'],
            'retiro_id' => $dados['retiro_id'],
            'equipe_id' => $dados['equipe_id'],
        ]);

        DB::commit();

        $this->showNotification('success', 'Permissão cadastrada com sucesso!');

        // Recarregar a lista de permissões
        $this->carregarPermissoes();

        // Limpar apenas os campos do formulário, mantendo o user_id selecionado
        $this->dadosFormulario = [];
        $this->formData['retiro_id'] = '';
        $this->formData['equipe_id'] = '';
        // NÃO limpar o user_id para manter o filtro
        $this->dispatch('reset-form-except-user');

    } catch (\Exception $e) {
        DB::rollBack();

        $this->showNotification('error', 'Erro ao cadastrar permissão: ' . $e->getMessage());
    }
};

// Função para remover permissão
$removerPermissao = function ($permissaoId) {
    try {
        $permissao = \App\Models\PermissaoUsuarioRetiro::find($permissaoId);
        
        if (!$permissao) {
            $this->showNotification('error', 'Permissão não encontrada.');
            return;
        }

        $permissao->delete();
        
        $this->showNotification('success', 'Permissão removida com sucesso!');
        
        // Recarregar a lista
        $this->carregarPermissoes();
        
    } catch (\Exception $e) {
        $this->showNotification('error', 'Erro ao remover permissão: ' . $e->getMessage());
    }
};

// Função para obter dados do usuário selecionado
$getUsuarioSelecionadoDados = function () {
    if (!$this->usuarioSelecionado) {
        return null;
    }
    
    return User::find($this->usuarioSelecionado);
};

?>

<section class="w-full">
    <livewire:components.notification :notification="$notification" />

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-5 gap-4">
        <div>
            <h1 class="text-2xl font-bold">Cadastrar de permissão</h1>
            <p class="">Informe os dados abaixo e cadastre a permissão a um usuario</p>
        </div>
        <a href="{{ route('permissoes.index') }}"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full md:w-auto justify-center">
            Voltar a lista
        </a>
    </div>
    
    <div>
        <div class="p-6 text-gray-900 dark:text-gray-100">
            <livewire:configuracoes.permissoes.form :dados="$formData" @dados-updated="$set('formData', $event)" />
        </div>
    </div>

</section>