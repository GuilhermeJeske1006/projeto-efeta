<?php

use App\Models\Pessoa;
use App\Models\Retiro;
use App\Models\Equipe;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\{state, computed, mount};

with(WithPagination::class);

// Define state properties
state([
    'search' => '',
    'perPage' => 15,
    'generos' => ['Masculino', 'Feminino', 'Outro'],
    'ja_trabalhou' => null,
    'genero' => null,
    'estado_civil' => null,
    'telefone' => null,
    'nome' => null,
    
    // Novos campos para funcionalidade
    'showFilters' => false,
    'retiroId' => null, // ID do retiro vindo da URL
    'retiro' => null,   // Objeto do retiro
    'selectedEquipe' => null,
    'selectedServos' => [],
    'equipes' => [],
    'showSelectionMode' => false,
    'nomeEquipe' => '',
    'listSelectedEquipes' => []
]);

// Mount - buscar retiro pela URL
mount(function ($retiroId) {
    
    if(auth()->user()->role_id !== 1) {
        abort(403, 'Acesso não autorizado');
    }
    
    $this->retiroId = $retiroId;
    $this->retiro = Retiro::find($retiroId);
    
    if (!$this->retiro) {
        session()->flash('error', 'Retiro não encontrado.');
        return redirect()->route('retiros.index');
    }
});

// Computed para buscar equipes baseadas no retiro da URL
$getEquipes = computed(function () {
    if (!$this->retiroId) {
        return collect();
    }
    return Equipe::all();
});

// Computed para buscar servos da equipe selecionada
$servosEquipe = computed(function () {
    if (!$this->selectedEquipe) {
        return collect();
    }

    return \DB::table('pessoa_retiros')
        ->where('equipe_id', $this->selectedEquipe)
        ->where('retiro_id', $this->retiroId)
        ->join('pessoas', 'pessoa_retiros.pessoa_id', '=', 'pessoas.id')
        ->leftJoin('telefones', function ($join) {
            $join->on('telefones.pessoa_id', '=', 'pessoas.id')
                 ->where('telefones.is_principal', true);
        })
        ->select('pessoas.*', 'telefones.numero as telefone_principal', 'pessoa_retiros.is_coordenador')
        ->orderByDesc('pessoa_retiros.is_coordenador')
        ->orderBy('pessoas.nome')
        ->get();
});

// Define the pessoas getter method
$getPessoas = computed(function () {
    $result = Pessoa::query()
        ->leftJoin('telefones', function ($join) {
            $join->on('telefones.pessoa_id', '=', 'pessoas.id')
                 ->where('telefones.is_principal', true);
        })
        ->where('pessoas.tipo_pessoa_id', 1)
        ->select('pessoas.*', 'telefones.numero as telefone_principal')
        ->when($this->ja_trabalhou !== null, function ($query) {
            return $query->where('pessoas.ja_trabalhou', $this->ja_trabalhou);
        })
        ->when($this->genero, function ($query) {
            return $query->where('pessoas.genero', $this->genero);
        })
        ->when($this->telefone, function ($query) {
            return $query->whereHas('telefones', function ($q) {
                $q->where('numero', 'like', '%' . $this->telefone . '%');
            });
        })
        ->when($this->nome, function ($query) {
            return $query->where('pessoas.nome', 'like', '%' . $this->nome . '%');
        })
        ->when($this->retiroId, function ($query) {
            $query->whereNotIn('pessoas.id', function ($sub) {
                $sub->select('pessoa_id')
                    ->from('pessoa_retiros')
                    ->where('retiro_id', $this->retiroId);
            });
        })
        ->orderBy('pessoas.nome')
        ->paginate($this->perPage);

    return $result;
});

// Toggle filtros
$toggleFilters = function () {
    $this->showFilters = !$this->showFilters;
};

// Limpar filtros
$clearFilters = function () {
    $this->ja_trabalhou = null;
    $this->genero = null;
    $this->telefone = null;
    $this->nome = null;
};

// Ativar modo de seleção
$activateSelectionMode = function () {
    if ($this->selectedEquipe) {
        $this->showSelectionMode = true;
        $this->selectedServos = [];
    } else {
        session()->flash('error', 'Selecione a equipe de trabalho primeiro.');
    }
};

// Toggle seleção de servo
$toggleServoSelection = function ($pessoaId) {
    if (in_array($pessoaId, $this->selectedServos)) {
        $this->selectedServos = array_filter($this->selectedServos, fn($id) => $id != $pessoaId);
    } else {
        $this->selectedServos[] = $pessoaId;
    }
};

// Método para selecionar ou desmarcar todos os usuários
$toggleSelectAllServos = function () {
    if (count($this->selectedServos) === $this->getPessoas->count()) {
        $this->selectedServos = []; // Desmarcar todos
    } else {
        $this->selectedServos = $this->getPessoas->pluck('id')->toArray(); // Selecionar todos
    }
};

// Adicionar servos selecionados à equipe
$addServosToEquipe = function () {
    if (empty($this->selectedServos)) {
        session()->flash('error', 'Selecione pelo menos um servo.');
        return;
    }

    $equipe = Equipe::find($this->selectedEquipe);
    if (!$equipe) {
        session()->flash('error', 'Equipe não encontrada.');
        return;
    }

    $adicionados = 0;
    foreach ($this->selectedServos as $pessoaId) {
        // Verificar se já não está na equipe
        $exists = \DB::table('pessoa_retiros')
            ->where('equipe_id', $this->selectedEquipe)
            ->where('retiro_id', $this->retiroId)
            ->where('pessoa_id', $pessoaId)
            ->exists();
        
        if (!$exists) {
            \DB::table('pessoa_retiros')->insert([
                'equipe_id' => $this->selectedEquipe,
                'retiro_id' => $this->retiroId,
                'pessoa_id' => $pessoaId,
                'is_coordenador' => false,
                'status_id' => 1, // Não chamado
                'tipo_id' => 1, // Servo
            ]);
            $adicionados++;
        }
    }

    if ($adicionados > 0) {
        session()->flash('message', "{$adicionados} servo(s) adicionado(s) à equipe com sucesso!");
        $this->listSelectedEquipes = $this->servosEquipe->toArray(); // Atualizar a tabela
    } else {
        session()->flash('info', 'Os servos selecionados já estão na equipe.');
    }

    $this->selectedServos = [];
    $this->showSelectionMode = false;
    
    // Disparar evento para atualização da tabela
    $this->dispatch('servos-equipe-updated');
};

// Remover servo da equipe
$removeServoFromEquipe = function ($pessoaId) {
    \DB::table('pessoa_retiros')
        ->where('equipe_id', $this->selectedEquipe)
        ->where('retiro_id', $this->retiroId)
        ->where('pessoa_id', $pessoaId)
        ->delete();
    
    session()->flash('message', 'Servo removido da equipe com sucesso!');
    
    // Disparar evento para atualização da tabela
    $this->dispatch('servos-equipe-updated');
};

// Adicionar coordenador à equipe
$setCoordenador = function ($pessoaId) {
    if (!$this->selectedEquipe || !$this->retiroId) {
        session()->flash('error', 'Selecione uma equipe e um retiro.');
        return;
    }


    try {
    // Remover o coordenador atual da equipe e retiro
        \DB::table('pessoa_retiros')
            ->where('equipe_id', $this->selectedEquipe)
            ->where('retiro_id', $this->retiroId)
            ->update(['is_coordenador' => false]);

        // Definir o novo coordenador
        \DB::table('pessoa_retiros')
            ->where('equipe_id', $this->selectedEquipe)
            ->where('retiro_id', $this->retiroId)
            ->where('pessoa_id', $pessoaId)
            ->update(['is_coordenador' => true]);

        session()->flash('message', 'Coordenador definido com sucesso!');
        $this->dispatch('servos-equipe-updated');
    } catch (\Exception $th) {
        session()->flash('error', 'Erro ao definir coordenador: ' . $th->getMessage());
    }

};

// Cancelar modo de seleção
$cancelSelection = function () {
    $this->showSelectionMode = false;
    $this->selectedServos = [];
};

// Atualizar lista de servos da equipe quando a equipe selecionada mudar
$updatedSelectedEquipe = function () {
    if ($this->selectedEquipe) {
        $this->nomeEquipe = Equipe::find($this->selectedEquipe)->nome ?? '';

        $this->listSelectedEquipes = $this->servosEquipe->toArray();
        $this->selectedServos = []; // Limpar a lista de servos selecionados
    }
};

// Atualizar tabela automaticamente quando listSelectedEquipes for alterado
$updatedListSelectedEquipes = function () {
    $this->dispatch('servos-equipe-updated'); // Disparar evento para atualizar a tabela
};

// Delete method
$delete = function ($id) {
    $pessoa = Pessoa::find($id);
    if ($pessoa) {
        $pessoa->delete();
        session()->flash('message', 'Pessoa excluída com sucesso!');
    }
};

?>

<div>
    <!-- Cabeçalho -->
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Servos - {{ $retiro->nome ?? 'Retiro' }}</h1>
            <p class="">Gerencie os servos para o {{ $retiro->nome ?? '' }}.</p>
        </div>
        <div class="flex gap-3">
            <button 
                wire:click="toggleFilters"
                class="inline-flex items-center px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                </svg>
                Filtros
            </button>
            <a href="{{ route('servos.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                Adicionar Servo
            </a>
        </div>
    </div>

    <!-- Seleção de Equipe -->
    <div class="bg-white border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm p-4 mb-6">
        <h3 class="text-lg font-medium mb-4">Gerenciar Equipes de Trabalho</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Seleção de Equipe -->
            <div class="space-y-2">
                <label for="selectedEquipe" class="block text-sm font-medium">Equipe de Trabalho</label>
                <flux:select wire:model.live="selectedEquipe" id="selectedEquipe" class="w-full">
                    <flux:select.option value="">Selecione uma equipe</flux:select.option>
                    @foreach ($this->getEquipes as $equipe)
                        <flux:select.option value="{{ $equipe->id }}">{{ $equipe->nome }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <!-- Botão para ativar seleção -->
            <div class="flex items-end">
                @if($selectedEquipe)
                    @if(!$showSelectionMode)
                        <button 
                            wire:click="activateSelectionMode"
                            class="w-full px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
                        >
                            Adicionar Servos
                        </button>
                    @else
                        <div class="flex gap-2 w-full">
                            <button 
                                wire:click="addServosToEquipe"
                                class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm"
                            >
                                Confirmar ({{ count($selectedServos) }})
                            </button>
                            <button 
                                wire:click="cancelSelection"
                                class="flex-1 px-3 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 text-sm"
                            >
                                Cancelar
                            </button>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

    <!-- Painel de Filtros (Colapsível) -->
    @if($showFilters)
    <div class="bg-zinc-50 border border-zinc-200 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm p-4 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium">Filtros</h3>
            <button wire:click="clearFilters" class="text-sm text-gray-500 hover:text-gray-700">
                Limpar Filtros
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Campo de Busca -->
            <div class="space-y-2">
                <label for="search" class="block text-sm font-medium">Buscar por Nome</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input 
                        wire:model.live.debounce.300ms="nome" 
                        type="text" 
                        id="search"
                        placeholder="Buscar servos..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                </div>
            </div>

            <!-- Campo de Telefone -->
            <div class="space-y-2">
                <label for="telefone" class="block text-sm font-medium">Telefone</label>
                <input 
                    wire:model.live.debounce.300ms="telefone" 
                    type="text" 
                    id="telefone"
                    placeholder="(00) 00000-0000" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <!-- Filtro Já Trabalhou -->
            <div class="space-y-2">
                <label for="ja_trabalhou" class="block text-sm font-medium">Já trabalhou?</label>
                <flux:select wire:model.live.debounce.300ms="ja_trabalhou" id="ja_trabalhou" class="w-full">
                    <flux:select.option value="">Todos</flux:select.option>
                    <flux:select.option value="1">Sim</flux:select.option>
                    <flux:select.option value="0">Não</flux:select.option>
                </flux:select>
            </div>

            <!-- Filtro Gênero -->
            <div class="space-y-2">
                <label for="genero" class="block text-sm font-medium">Gênero</label>
                <flux:select wire:model.live.debounce.300ms="genero" id="genero" class="w-full">
                    <flux:select.option value="">Todos</flux:select.option>
                    @foreach ($generos as $item)
                        <flux:select.option value="{{ $item }}">{{ $item }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    </div>
    @endif

    <!-- Flash messages -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    @if (session()->has('info'))
        <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('info') }}</span>
        </div>
    @endif

    <!-- Indicador de modo de seleção -->
    @if($showSelectionMode)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <p class="text-yellow-800">
                <strong>Modo de seleção ativo:</strong> Selecione os servos na tabela abaixo para adicionar à equipe.
                <span class="font-semibold">{{ count($selectedServos) }} servo(s) selecionado(s)</span>
            </p>
        </div>
    </div>
    @endif

    <!-- Tabela de Servos -->
    <div class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm">
        <table class="min-w-full">
            <thead class="bg-gray-100">
                <tr>
                    @if($showSelectionMode)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                        <input 
                            type="checkbox" 
                            class="rounded"
                            wire:click="toggleSelectAllServos"
                            {{ count($selectedServos) === $this->getPessoas->count() && $this->getPessoas->count() > 0 ? 'checked' : '' }}
                            {{ $this->getPessoas->count() === 0 ? 'disabled' : '' }}
                        >
                    </th>
                    @endif
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone Principal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gênero</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Civil</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($this->getPessoas as $pessoa)
                    <tr class="{{ in_array($pessoa->id, $selectedServos) ? 'bg-blue-600' : '' }}" wire:key="pessoa-{{ $pessoa->id }}">
                        @if($showSelectionMode)
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input 
                                type="checkbox" 
                                wire:click="toggleServoSelection({{ $pessoa->id }})"
                                {{ in_array($pessoa->id, $selectedServos) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                            >
                        </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->telefone_principal ?? 'Sem telefone' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->genero }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->estado_civil }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->cpf }}</td>
                    </tr>
                @endforeach

                @if($this->getPessoas->count() === 0)
                    <tr>
                        <td colspan="{{ $showSelectionMode ? '7' : '6' }}" class="px-6 py-4 text-center">Nenhum registro encontrado</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination links -->
    <div class="mt-4">
        {{ $this->getPessoas->links() }}
    </div>

    <!-- Tabela da Equipe Selecionada -->
    @if($selectedEquipe)
    <div class="mt-8">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h2 class="text-xl font-bold">
                    Servos na Equipe: {{ $nomeEquipe }}
                </h2>
            </div>
        </div>

        <!-- Indicador de carregamento -->
        <div wire:loading.flex wire:target="selectedEquipe" class="items-center justify-center py-8 text-gray-500">
            <svg class="animate-spin h-5 w-5 mr-3" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Carregando servos da equipe...
        </div>

        <div class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm" wire:loading.class="opacity-50" wire:target="selectedEquipe">
            <table class="min-w-full" id="servosEquipeTable">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone Principal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>

                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">

                    @foreach($this->listSelectedEquipes as $pessoa)
                        <tr wire:key="servo-equipe-{{ $pessoa->id }}">
                            <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->nome }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->telefone_principal ?? 'Sem telefone' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($pessoa->is_coordenador)
                                    <span class="inline-flex items-center px-2 py-1 bg-green-100 text-green-800 text-xs font-medium rounded-full">
                                        Coordenador
                                    </span>
                                @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button 
                                    wire:click="setCoordenador({{ $pessoa->id }})" 
                                    class="text-yellow-600 hover:text-yellow-900"
                                    wire:loading.attr="disabled"
                                    wire:target="setCoordenador"
                                >
                                    <flux:icon.star />

                                </button>
                                <button 
                                    wire:click="removeServoFromEquipe({{ $pessoa->id }})" 
                                    class="text-red-600 hover:text-red-900"
                                    onclick="return confirm('Tem certeza que deseja remover este servo da equipe?')"
                                    wire:loading.attr="disabled"
                                    wire:target="removeServoFromEquipe"
                                >
                                    <flux:icon.trash/>    
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    @if($this->servosEquipe->count() === 0)
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center">Nenhum servo nesta equipe</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        Livewire.on('servos-equipe-updated', () => {
            const table = document.querySelector('#servosEquipeTable');
            if (table) {
                table.style.opacity = '0.5'; // Adicionar efeito visual de carregamento
                setTimeout(() => {
                    table.style.opacity = '1'; // Restaurar opacidade após atualização
                }, 300);
            }
        });
    });
</script>