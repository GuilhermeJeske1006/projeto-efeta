<?php

use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, updated};
use function Livewire\Volt\{on};

// Define state properties
state([
    // Form data
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
    'logradouro' => '',
    'numero' => '',
    'complemento' => '',
    'bairro' => '',
    'cidade' => '',
    'estado' => '',
    'cep' => '',
    'pais' => 'Brasil',
    'telefones' => [],

    // Select options
    'tipos_pessoa' => [],
    'generos' => [],
    'estados_civis' => [],
    'tipos_telefone' => [],
    'estados' => [],
]);

// Define validation rules
rules([
    'nome' => ['required', 'string', 'max:255'],
    'cpf' => ['required', 'string', 'size:14', 'unique:pessoas,cpf'],
    'data_nascimento' => ['required', 'date', 'before:today'],
    'email' => ['required', 'email', 'max:255', 'unique:pessoas,email'],
    'is_problema_saude' => ['boolean'],
    'descricao' => ['nullable', 'string', 'max:1000'],
    'ja_trabalhou' => ['boolean'],
    'genero' => 'required|in:Masculino,Feminino,Outro',
    'estado_civil' => ['required', 'string'],

    'logradouro' => ['required', 'string', 'max:255'],
    'numero' => ['required', 'string', 'max:20'],
    'complemento' => ['nullable', 'string', 'max:100'],
    'bairro' => ['required', 'string', 'max:100'],
    'cidade' => ['required', 'string', 'max:100'],
    'cep' => ['required', 'string', 'size:9'],

    'telefones.*.numero' => ['required', 'string', 'min:14', 'max:15'],
    'telefones.*.tipo' => ['required', 'string'],
    'telefones.*.nome_pessoa' => ['nullable', 'string', 'max:100'],
    'telefones' => function ($attribute, $value, $fail) {
        if (!collect($value)->contains('is_principal', true)) {
            $fail('Pelo menos um telefone deve ser marcado como principal.');
        }
    },
])->messages([
    'nome.required' => 'O nome é obrigatório',
    'cpf.required' => 'O CPF é obrigatório',
    'cpf.size' => 'O CPF deve estar no formato 000.000.000-00',
    'cpf.unique' => 'Este CPF já está cadastrado',
    'data_nascimento.required' => 'A data de nascimento é obrigatória',
    'data_nascimento.before' => 'A data de nascimento deve ser anterior à data atual',
    'email.required' => 'O email é obrigatório',
    'email.email' => 'Informe um email válido',
    'email.unique' => 'Este email já está cadastrado',
    'genero.required' => 'Selecione o gênero',
    'estado_civil.required' => 'Selecione o estado civil',

    'logradouro.required' => 'O logradouro é obrigatório',
    'numero.required' => 'O número é obrigatório',
    'bairro.required' => 'O bairro é obrigatório',
    'cidade.required' => 'A cidade é obrigatória',
    'cep.required' => 'O CEP é obrigatório',
    'cep.size' => 'O CEP deve estar no formato 00000-000',

    'telefones.*.numero.required' => 'O número de telefone é obrigatório',
    'telefones.*.numero.min' => 'O telefone deve estar no formato correto',
    'telefones.*.tipo.required' => 'O tipo de telefone é obrigatório',
]);

// Mount component
mount(function () {
    // Initialize phone array if empty
    if (empty($this->telefones)) {
        $this->telefones = [['numero' => '', 'tipo' => 'Celular', 'nome_pessoa' => '', 'is_principal' => false]];
    }
});

// Add phone number
$adicionarTelefone = function () {
    $this->telefones[] = ['numero' => '', 'tipo' => 'Celular', 'nome_pessoa' => '', 'is_principal' => false];
};

// Remove phone number
$removerTelefone = function ($index) {
    if (count($this->telefones) > 1) {
        unset($this->telefones[$index]);
        $this->telefones = array_values($this->telefones);
    }
};

// Toggle health problem
$openProblema = function () {
    $this->is_problema_saude = !$this->is_problema_saude;
};

// Lookup address by zipcode
$buscarCep = function () {
    $cep = preg_replace('/[^0-9]/', '', $this->cep);

    if (strlen($cep) === 8) {
        try {
            $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");
            $endereco = json_decode($response);

            if (!isset($endereco->erro)) {
                $this->logradouro = $endereco->logradouro;
                $this->bairro = $endereco->bairro;
                $this->cidade = $endereco->localidade;
                $this->estado = $endereco->uf;
                $this->pais = 'Brasil';
            } else {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => 'CEP não encontrado',
                ]);
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Erro ao buscar CEP',
            ]);
        }
    }
};

// Format and validate CPF
$formatarEValidarCpf = function () {
    // Remove non-numeric characters
    $cpf = preg_replace('/[^0-9]/', '', $this->cpf);

    // Format CPF
    if (strlen($cpf) <= 11) {
        $formatted = '';

        if (strlen($cpf) > 3) {
            $formatted .= substr($cpf, 0, 3) . '.';

            if (strlen($cpf) > 6) {
                $formatted .= substr($cpf, 3, 3) . '.';

                if (strlen($cpf) > 9) {
                    $formatted .= substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                } else {
                    $formatted .= substr($cpf, 6);
                }
            } else {
                $formatted .= substr($cpf, 3);
            }
        } else {
            $formatted = $cpf;
        }

        $this->cpf = $formatted;
    }

    // Validate CPF if it has 11 digits
    if (strlen($cpf) === 11) {
        // Check for known invalid CPFs
        $invalidCpfs = ['00000000000', '11111111111', '22222222222', '33333333333', '44444444444', '55555555555', '66666666666', '77777777777', '88888888888', '99999999999'];

        if (in_array($cpf, $invalidCpfs)) {
            $this->addError('cpf', 'CPF inválido.');
            return;
        }

        // Validate first check digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        if (intval($cpf[9]) !== $digit1) {
            $this->addError('cpf', 'CPF inválido.');
            return;
        }

        // Validate second check digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        if (intval($cpf[10]) !== $digit2) {
            $this->addError('cpf', 'CPF inválido.');
            return;
        }

        // CPF is valid
        $this->resetErrorBag('cpf');
    } elseif (strlen($cpf) > 0) {
        // Don't validate incomplete CPF
        $this->resetErrorBag('cpf');
    }
};

// Format and validate phone
$formatarEValidarTelefone = function ($index) {
    // Remove non-numeric characters
    $telefone = preg_replace('/[^0-9]/', '', $this->telefones[$index]['numero']);

    // Format phone
    if (strlen($telefone) <= 11) {
        $formatted = '';

        if (strlen($telefone) > 2) {
            $formatted = '(' . substr($telefone, 0, 2) . ')';

            if (strlen($telefone) > 7) {
                // 9-digit format: (XX) XXXXX-XXXX
                $formatted .= ' ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
            } elseif (strlen($telefone) > 6) {
                // 8-digit format: (XX) XXXX-XXXX
                $formatted .= ' ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
            } else {
                $formatted .= ' ' . substr($telefone, 2);
            }
        } else {
            $formatted = $telefone;
        }

        $this->telefones[$index]['numero'] = $formatted;
    }

    // Validate phone
    if (strlen($telefone) < 10) {
        if (strlen($telefone) > 0) {
            $this->addError('telefones.' . $index . '.numero', 'Telefone deve ter pelo menos 10 dígitos.');
        }
    } else {
        $this->resetErrorBag('telefones.' . $index . '.numero');
    }
};

// Handle phone number updates
updated(['telefones.*.numero' => function ($value, $key) {
    // Extract index from key
    preg_match('/telefones\.(\d+)\.numero/', $key, $matches);
    
    if (isset($matches[1])) {
        $index = $matches[1];
        $this->formatarEValidarTelefone($index);
    }
}]);

// Listen for validation from parent
on(['validate-form' => function () {
    $this->validate();

    return dd('Formulário validado com sucesso!');
    // Envia os dados validados para o pai
    $this->dispatch('form-validated', [
        'data' => [
            'nome' => $this->nome,
            'cpf' => $this->cpf,
            'data_nascimento' => $this->data_nascimento,
            'email' => $this->email,
            'tipo_pessoa_id' => $this->tipo_pessoa_id,
            'is_problema_saude' => $this->is_problema_saude,
            'descricao' => $this->descricao,
            'ja_trabalhou' => $this->ja_trabalhou,
            'genero' => $this->genero,
            'estado_civil' => $this->estado_civil,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'complemento' => $this->complemento,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'cep' => $this->cep,
            'pais' => $this->pais,
            'telefones' => $this->telefones,
        ]
    ]);
}]);
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="col-span-3">
        <h3 class="text-md font-medium mb-2 border-b pb-2">Dados Pessoais</h3>
    </div>

    <!-- Nome -->
    <div>
        <flux:input wire:model="nome" :label="__('Nome Completo')" type="text" required />
    </div>

    <!-- CPF -->
    <div>
        <flux:input wire:model="cpf" :label="__('CPF')" type="text" placeholder="000.000.000-00"
            required wire:change="formatarEValidarCpf" />
    </div>

    <!-- Email -->
    <div>
        <flux:input wire:model="email" :label="__('E-mail')" type="email" required />
    </div>

    <!-- Data de Nascimento -->
    <div>
        <flux:input wire:model="data_nascimento" :label="__('Data de Nascimento')" type="date"
            required />
    </div>

    <!-- Gênero -->
    <div>
        <flux:select wire:model="genero" :label="__('Gênero')">
            <flux:select.option value="" disabled>
                {{ __('Selecione o gênero') }}
            </flux:select.option>
            @foreach ($generos as $item)
                <flux:select.option value="{{ $item }}">
                    {{ $item }}
                </flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <!-- Estado Civil -->
    <div>
        <flux:select wire:model="estado_civil" :label="__('Estado Civil')">
            <flux:select.option value="" disabled>
                {{ __('Selecione o estado civil') }}
            </flux:select.option>
            @foreach ($estados_civis as $item)
                <flux:select.option value="{{ $item }}">{{ $item }}</flux:select.option>
            @endforeach
        </flux:select>
    </div>

    <!-- Já Trabalhou -->
    <div class="flex items-end">
        <div class="flex items-center h-5">
            <input wire:model="ja_trabalhou" type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
        </div>
        <div class="ml-3 text-sm">
            <label class="font-medium text-gray-700 dark:text-gray-300">
                {{ __('Já trabalhou anteriormente?') }}
            </label>
        </div>
    </div>

    <!-- Problemas de Saúde -->
    <div class="flex items-end">
        <div class="flex items-center h-5">
            <input wire:model="is_problema_saude" wire:click="openProblema" type="checkbox"
                class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
        </div>
        <div class="ml-3 text-sm">
            <label class="font-medium text-gray-700 dark:text-gray-300">
                {{ __('Possui problema de saúde?') }}
            </label>
        </div>
    </div>

    <!-- Descrição de Saúde (condicionalmente exibido) -->
    @if ($is_problema_saude)
        <div class="col-span-3">
            <flux:textarea wire:model="descricao" :label="__('Descreva o problema de saúde')"
                rows="3" />
        </div>
    @endif

    <!-- Seção de Endereço -->
    <div class="col-span-3 mt-4">
        <h3 class="text-md font-medium mb-2 border-b pb-2">Endereço</h3>
    </div>

    <!-- CEP -->
    <div>
        <div class="flex space-x-2">
            <div class="flex-grow">
                <flux:input wire:model="cep" :label="__('CEP')" type="text"
                    placeholder="00000-000" required />
            </div>
            <div class="flex items-end">
                <flux:button type="button" wire:click="buscarCep">
                    {{ __('Buscar') }}
                </flux:button>
            </div>
        </div>
        @error('cep')
            <span class="text-red-500 text-sm">{{ $message }}</span>
        @enderror
    </div>

    <!-- Logradouro -->
    <div class="col-span-2">
        <flux:input wire:model="logradouro" :label="__('Logradouro')" type="text" required />
    </div>

    <!-- Complemento -->
    <div>
        <flux:input wire:model="complemento" :label="__('Complemento')" type="text" />
    </div>

    <!-- Bairro -->
    <div>
        <flux:input wire:model="bairro" :label="__('Bairro')" type="text" required />
    </div>

    <!-- Cidade -->
    <div>
        <flux:input wire:model="cidade" :label="__('Cidade')" type="text" required />
    </div>
    
    <!-- Número -->
    <div>
        <flux:input wire:model="numero" :label="__('Número')" type="text" required />
    </div>

    <!-- Seção de Telefones -->
    <div class="col-span-3 mt-4">
        <h3 class="text-md font-medium mb-2 border-b pb-2">Telefones</h3>
    </div>

    <!-- Lista de Telefones -->
    <div class="col-span-3">
        @foreach ($telefones as $index => $telefone)
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 border-b pb-4">
                <!-- Número -->
                <flux:input wire:model="telefones.{{ $index }}.numero"
                    wire:change="formatarEValidarTelefone({{ $index }})" :label="__('Número')"
                    type="text" placeholder="(00) 00000-0000" required />

                <!-- Tipo -->
                <div>
                    <flux:select wire:model="telefones.{{ $index }}.tipo"
                        :label="__('Tipo')" required>
                        @foreach ($tipos_telefone as $item)
                            <flux:select.option value="{{ $item }}">{{ $item }}
                            </flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <!-- Nome da Pessoa -->
                <div class="flex items-end space-x-2">
                    <div class="flex-grow">
                        <flux:input wire:model="telefones.{{ $index }}.nome_pessoa"
                            :label="__('Nome de Contato (opcional)')" type="text" />
                    </div>
                    <div class="pb-1">
                        @if (count($telefones) > 1)
                            <flux:button type="button" variant="danger"
                                wire:click="removerTelefone({{ $index }})" class="px-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </flux:button>
                        @endif
                    </div>
                </div>
                
                <!-- Telefone Principal -->
                <div class="flex items-end">
                    <div class="flex items-center h-5">
                        <input wire:model="telefones.{{ $index }}.is_principal" type="checkbox"
                            class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                    </div>
                    <div class="ml-3 text-sm">
                        <label class="font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Telefone principal?') }}
                        </label>
                    </div>
                </div>
            </div>
        @endforeach

        <!-- Botão Adicionar Telefone -->
        <div class="flex justify-end">
            <flux:button type="button" wire:click="adicionarTelefone">
                {{ __('Adicionar Telefone') }}
            </flux:button>
        </div>
    </div>
</div>