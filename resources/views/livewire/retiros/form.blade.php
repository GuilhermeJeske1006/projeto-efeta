<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed};

// Define state properties
state([
    'nome' => '',
    'descricao' => '',
    'tema' => '',
    'data_inicio' => '',
    'data_fim' => '',
    'musica_tema' => '',

    'dados' => [],
]);

// Define validation rules
rules([
    'nome' => ['required', 'string', 'max:255'],
    'descricao' => ['nullable', 'string', 'max:1000'],
    'tema' => ['required', 'string', 'max:100'],
    'data_inicio' => ['required', 'date', 'after_or_equal:today'],
    'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
    'musica_tema' => ['nullable', 'string', 'max:255'],
])->messages([
    'nome.required' => 'O nome do evento é obrigatório',
    'nome.max' => 'O nome deve ter no máximo 255 caracteres',
    'descricao.max' => 'A descrição deve ter no máximo 1000 caracteres',
    'tema.required' => 'O tema é obrigatório',
    'tema.max' => 'O tema deve ter no máximo 100 caracteres',
    'data_inicio.required' => 'A data de início é obrigatória',
    'data_inicio.after_or_equal' => 'A data de início deve ser hoje ou uma data futura',
    'data_fim.required' => 'A data de fim é obrigatória',
    'data_fim.after_or_equal' => 'A data de fim deve ser igual ou posterior à data de início',
    'musica_tema.max' => 'O nome da música tema deve ter no máximo 255 caracteres',
]);

// Mount component
mount(function () {
    $this->nome = $this->dados['nome'] ?? '';
    $this->descricao = $this->dados['descricao'] ?? '';
    $this->tema = $this->dados['tema'] ?? '';
    $this->data_inicio = $this->dados['data_inicio'] ?? '';
    $this->data_fim = $this->dados['data_fim'] ?? '';
    $this->musica_tema = $this->dados['musica_tema'] ?? '';
});

$validarDatas = function () {
    if ($this->data_inicio && $this->data_fim) {
        if (strtotime($this->data_fim) < strtotime($this->data_inicio)) {
            $this->addError('data_fim', 'A data de fim deve ser igual ou posterior à data de início.');
        } else {
            $this->resetErrorBag('data_fim');
        }
    }
};

$updatedDataInicio = function () {
    $this->validarDatas();
};

$updatedDataFim = function () {
    $this->validarDatas();
};

$enviarDados = function () {
    $this->validate();
    
    // Emite um evento com todos os dados do formulário
    $this->dispatch('dados-formulario', [
        'nome' => $this->nome,
        'descricao' => $this->descricao,
        'tema' => $this->tema,
        'data_inicio' => $this->data_inicio,
        'data_fim' => $this->data_fim,
        'musica_tema' => $this->musica_tema,
    ]);
};

?>
<div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <!-- Nome -->
    <div >
        <flux:input wire:model="nome" :label="__('Nome do retiro')" type="text" required />
    </div>

    <!-- Tema -->
    <div class="col-span-2">
        <flux:input wire:model="tema" :label="__('Tema')" type="text" 
            placeholder="" required />
    </div>

    <!-- Data de Início -->
    <div>
        <flux:input wire:model="data_inicio" :label="__('Data de Início')" type="date"
            required wire:change="validarDatas" />
    </div>

    <!-- Data de Fim -->
    <div>
        <flux:input wire:model="data_fim" :label="__('Data de Fim')" type="date"
            required wire:change="validarDatas" />
    </div>

    <!-- Música Tema -->
    <div>
        <flux:input wire:model="musica_tema" :label="__('Música Tema (opcional)')" type="text" 
            placeholder="Nome da música ou artista" />
    </div>

    <!-- Descrição -->
    <div class="col-span-3">
        <flux:textarea wire:model="descricao" :label="__('Descrição do Evento (opcional)')"
            rows="4" placeholder="Descreva os detalhes do evento..." />
    </div>

</div>

<div class="flex justify-end space-x-2 pt-4 border-t">
    <flux:button type="button" variant="primary" wire:click="$refresh">
        {{ __('Cancelar') }}
    </flux:button>
    <flux:button type="button" wire:click="enviarDados" variant="primary">
        {{ __('Salvar') }}
    </flux:button>
    
</div>

</div>