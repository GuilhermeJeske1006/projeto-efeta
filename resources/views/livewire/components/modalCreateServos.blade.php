<?php
use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use App\Models\TipoPessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed};

// Define state properties
state([
    // Modal control
    'showModal' => false,
    
    // Dados essenciais da pessoa
    'nome' => '',
    'cpf' => '',
    'email' => '',
    'telefone' => '',
    'data_nascimento' => '',
    'genero' => '',
    
    // Endereço básico
    'cep' => '',
    'logradouro' => '',
    'numero' => '',
    'bairro' => '',
    'cidade' => '',
    'estado' => 'SC',
    
    // Select options
    'generos' => ['Masculino', 'Feminino', 'Outro'],
    'estados' => ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'],
]);

// Define validation rules
rules([
    'nome' => ['required', 'string', 'max:255'],
    'cpf' => ['string', 'size:14', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
    'email' => ['email', 'max:255'],
    'telefone' => ['required', 'string', 'min:14', 'max:15'],
    'data_nascimento' => ['required', 'date', 'before:today'],
    'genero' => 'required|in:Masculino,Feminino,Outro',
    'cep' => ['string', 'size:9'],
    'logradouro' => ['string', 'max:255'],
    'numero' => ['string', 'max:20'],
    'bairro' => ['string', 'max:100'],
    'cidade' => ['required', 'string', 'max:100'],
    'estado' => ['string'],
])->messages([
    'nome.required' => 'O nome é obrigatório',
    'cpf.required' => 'O CPF é obrigatório',
    'cpf.size' => 'O CPF deve estar no formato 000.000.000-00',
    'email.required' => 'O email é obrigatório',
    'email.email' => 'Informe um email válido',
    'telefone.required' => 'O telefone é obrigatório',
    'telefone.min' => 'O telefone deve estar no formato correto',
    'data_nascimento.required' => 'A data de nascimento é obrigatória',
    'data_nascimento.before' => 'A data de nascimento deve ser anterior à data atual',
    'genero.required' => 'Selecione o gênero',
    'cep.required' => 'O CEP é obrigatório',
    'cep.size' => 'O CEP deve estar no formato 00000-000',
    'logradouro.required' => 'O logradouro é obrigatório',
    'numero.required' => 'O número é obrigatório',
    'bairro.required' => 'O bairro é obrigatório',
    'cidade.required' => 'A cidade é obrigatória',
    'estado.required' => 'Selecione o estado',
]);

// Mount component
mount(function () {
    $this->genero = $this->generos[0];
});

// Modal functions
$abrirModal = function () {
    $this->showModal = true;
};

$fecharModal = function () {
    $this->showModal = false;
    $this->reset([
        'nome', 'cpf', 'email', 'telefone', 'data_nascimento', 
        'cep', 'logradouro', 'numero', 'bairro', 'cidade'
    ]);
    $this->genero = $this->generos[0];
    $this->estado = 'SC';
};

// Format CPF
$formatarCpf = function () {
    $cpf = preg_replace('/[^0-9]/', '', $this->cpf);
    
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
};

// Format phone
$formatarTelefone = function () {
    $telefone = preg_replace('/[^0-9]/', '', $this->telefone);
    
    if (strlen($telefone) <= 11) {
        $formatted = '';
        
        if (strlen($telefone) > 2) {
            $formatted = '(' . substr($telefone, 0, 2) . ')';
            
            if (strlen($telefone) > 7) {
                $formatted .= ' ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
            } elseif (strlen($telefone) > 6) {
                $formatted .= ' ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
            } else {
                $formatted .= ' ' . substr($telefone, 2);
            }
        } else {
            $formatted = $telefone;
        }
        
        $this->telefone = $formatted;
    }
};

// Format CEP
$formatarCep = function () {
    $cep = preg_replace('/[^0-9]/', '', $this->cep);
    
    if (strlen($cep) <= 8) {
        if (strlen($cep) > 5) {
            $this->cep = substr($cep, 0, 5) . '-' . substr($cep, 5);
        } else {
            $this->cep = $cep;
        }
    }
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

// Save data
$salvar = function () {
    $this->validate();
    
    try {
        DB::beginTransaction();
        
        // Criar pessoa
        $pessoa = Pessoa::create([
            'nome' => $this->nome,
            'cpf' => $this->cpf,
            'email' => $this->email,
            'data_nascimento' => $this->data_nascimento,
            'genero' => $this->genero,
            'tipo_pessoa_id' => 1, 
        ]);
        
        // Criar endereço
        Endereco::create([
            'pessoa_id' => $pessoa->id,
            'logradouro' => $this->logradouro,
            'numero' => $this->numero,
            'bairro' => $this->bairro,
            'cidade' => $this->cidade,
            'estado' => $this->estado,
            'cep' => $this->cep,
            'pais' => 'Brasil',
        ]);
        
        // Criar telefone
        Telefone::create([
            'pessoa_id' => $pessoa->id,
            'numero' => $this->telefone,
            'tipo' => 'Celular',
            'is_principal' => true,
        ]);
        
        DB::commit();
        
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cadastro realizado com sucesso!',
        ]);
        
        $this->fecharModal();
        
        
        
        // Emitir evento para atualizar lista se necessário
        $this->dispatch('pessoa-cadastrada');
        
    } catch (\Exception $e) {
        DB::rollBack();
        
        $this->dispatch('notify', [
            'type' => 'error',
            'message' => 'Erro ao realizar cadastro. Tente novamente.',
        ]);
    }
};

?>

<div>
    <!-- Botão para abrir modal -->
    <flux:button wire:click="abrirModal" variant="primary">
        + Novo Cadastro Rápido
    </flux:button>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="fecharModal"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                <div class="absolute top-0 right-0 pt-4 pr-4">
                    <button type="button" class="bg-white dark:bg-gray-800 rounded-md text-gray-400 hover:text-gray-600 focus:outline-none" wire:click="fecharModal">
                        <span class="sr-only">Fechar</span>
                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-6" id="modal-title">
                            Cadastro Rápido
                        </h3>

                        <form wire:submit.prevent="salvar">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                
                                <!-- Dados Pessoais -->
                                <div class="sm:col-span-2">
                                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3 border-b pb-2">
                                        Dados Pessoais
                                    </h4>
                                </div>

                                <!-- Nome -->
                                <div class="sm:col-span-2">
                                    <flux:input wire:model="nome" label="Nome Completo" type="text" required />
                                </div>

                                <!-- CPF -->
                                <div>
                                    <flux:input wire:model="cpf" label="CPF" type="text" 
                                        placeholder="000.000.000-00"  
                                        wire:input="formatarCpf" />
                                </div>

                                <!-- Data Nascimento -->
                                <div>
                                    <flux:input wire:model="data_nascimento" label="Data de Nascimento" 
                                        type="date" required />
                                </div>

                                <!-- Email -->
                                <div>
                                    <flux:input wire:model="email" label="E-mail" type="email"  />
                                </div>

                                <!-- Gênero -->
                                <div>
                                    <flux:select wire:model="genero" label="Gênero" required>
                                        @foreach ($generos as $item)
                                            <flux:select.option value="{{ $item }}">
                                                {{ $item }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>

                                <!-- Telefone -->
                                <div class="sm:col-span-2">
                                    <flux:input wire:model="telefone" label="Telefone" type="text" 
                                        placeholder="(00) 00000-0000" required 
                                        wire:input="formatarTelefone" />
                                </div>

                                <!-- Endereço -->
                                <div class="sm:col-span-2">
                                    <h4 class="text-md font-medium text-gray-700 dark:text-gray-300 mb-3 border-b pb-2">
                                        Endereço
                                    </h4>
                                </div>

                                <!-- CEP -->
                                <div>
                                    <div class="flex space-x-2">
                                        <div class="flex-grow">
                                            <flux:input wire:model="cep" label="CEP" type="text" 
                                                placeholder="00000-000" required 
                                                wire:input="formatarCep" />
                                        </div>
                                        <div class="flex items-end">
                                            <flux:button type="button" wire:click="buscarCep" size="sm">
                                                Buscar
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div>
                                    <flux:select wire:model="estado" label="Estado" required>
                                        @foreach ($estados as $uf)
                                            <flux:select.option value="{{ $uf }}">{{ $uf }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </div>

                                <!-- Logradouro -->
                                <div class="sm:col-span-2">
                                    <flux:input wire:model="logradouro" label="Logradouro" type="text"  />
                                </div>

                                <!-- Número -->
                                <div>
                                    <flux:input wire:model="numero" label="Número" type="text"  />
                                </div>

                                <!-- Bairro -->
                                <div>
                                    <flux:input wire:model="bairro" label="Bairro" type="text"  />
                                </div>

                                <!-- Cidade -->
                                <div class="sm:col-span-2">
                                    <flux:input wire:model="cidade" label="Cidade" type="text" required />
                                </div>

                            </div>

                            <!-- Buttons -->
                            <div class="mt-6 flex justify-end space-x-3">
                                <flux:button type="button" variant="ghost" wire:click="fecharModal">
                                    Cancelar
                                </flux:button>
                                <flux:button type="submit" variant="primary">
                                    Salvar Cadastro
                                </flux:button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>