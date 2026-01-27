<?php

use App\Models\Pessoa;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\{state, computed};

with(WithPagination::class);

// Define state properties
state([
    'search' => '',
    'perPage' => 15,
    'generos' => ['Masculino', 'Feminino', 'Outro'],
    'ja_trabalhou' => null,
    'genero' => null,
    'data_nascimento_minima' => null,
    'data_nascimento_maxima' => null,
    'estado_civil' => null,
    'telefone' => null,
    'nome' => null,
    'cpf' => null,
    'confirmingDelete' => null,
    'orderBy' => 'created_at', // Adiciona o estado para ordenação
]);

// Define the pessoas getter method
$getPessoas = function () {
    $result = Pessoa::query()
        ->where('pessoas.tipo_pessoa_id', 3)
        ->when($this->data_nascimento_minima && $this->data_nascimento_maxima, function ($query) {
            return $query->whereBetween('pessoas.data_nascimento', [$this->data_nascimento_minima, $this->data_nascimento_maxima]);
        })
        ->when($this->data_nascimento_minima && !$this->data_nascimento_maxima, function ($query) {
            return $query->where('pessoas.data_nascimento', '>=', $this->data_nascimento_minima);
        })
        ->when(!$this->data_nascimento_minima && $this->data_nascimento_maxima, function ($query) {
            return $query->where('pessoas.data_nascimento', '<=', $this->data_nascimento_maxima);
        })
        ->when($this->genero, function ($query) {
            return $query->where('pessoas.genero', $this->genero);
        })
        ->when($this->cpf, function ($query) {
            return $query->where('pessoas.cpf', 'like', '%' . $this->cpf . '%');
        })
        ->when($this->telefone, function ($query) {
            return $query->whereHas('telefones', function ($q) {
                $q->where('numero', 'like', '%' . $this->telefone . '%');
            });
        })
        ->when($this->nome, function ($query) {
            return $query->where('pessoas.nome', 'like', '%' . $this->nome . '%');
        })
        ->orderBy(
            match ($this->orderBy) {
                'idade' => 'pessoas.data_nascimento',
                'updated_at' => 'pessoas.updated_at',
                default => 'pessoas.created_at',
            },
            'asc'
        )
        ->paginate($this->perPage);

    // // Map the result to include an array of telefones
    $result->getCollection()->transform(function ($pessoa) {
        $pessoa->telefones = $pessoa->telefones->pluck('numero')->toArray();
        return $pessoa;
    });

    return $result;
};

// Delete method
$delete = function ($id) {
    $pessoa = Pessoa::find($id);
    if ($pessoa) {
        $pessoa->delete();
        session()->flash('message', 'Retirante excluída com sucesso!');
    }
    $this->confirmingDelete = null;
};

$addChamamento = function ($pessoa) {
    $retiroProximo = \App\Models\Retiro::where('data_inicio', '>', now())->orderBy('data_inicio', 'asc')->first();
    if ($retiroProximo) {
        $exists = \DB::table('pessoa_retiros')
            ->where('retiro_id', $retiroProximo->id)
            ->where('equipe_id', 14)
            ->where('pessoa_id', $pessoa['id'])
            ->exists();
        
        if (!$exists) {
            \DB::table('pessoa_retiros')->insert([
                'retiro_id' => $retiroProximo->id,
                'pessoa_id' => $pessoa['id'],
                'equipe_id' => 14,
                'is_coordenador' => false,
                'status_id' => 1, // Não chamado
                'tipo_id' => 3,
            ]);
            session()->flash('message', $pessoa['nome'] . 'Adicionado ao chamamento com sucesso!');

        } else {
            session()->flash('message', 'Este retirante já está no chamamento do próximo retiro.');
        }
    } else {
        session()->flash('message', 'Nenhum retiro futuro encontrado para adicionar o chamamento.');
    }
};

?>

<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Lista de espera</h1>
            <p class="">Gerencie os retirantes cadastrados no sistema.</p>
        </div>
        <a href="{{ route('retirantes.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Adicionar
        </a>
    </div>
    <div
        class="w-full border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm p-4 mb-6">
        <!-- Título do Filtro -->
        <h3 class="text-lg font-medium whitespace-nowrap mb-4">Filtrar</h3>

        <!-- Grid de Filtros -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Campo de Busca -->
            <div class="space-y-2">
                <label for="search" class="block text-sm font-medium whitespace-nowrap">Buscar</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <!-- Ícone de Busca (opcional) -->
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                            fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="nome" type="text" id="search"
                        placeholder="Buscar retirantes..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Campo de Telefone -->
            <div class="space-y-2">
                <label for="telefone" class="block text-sm font-medium whitespace-nowrap">Telefone</label>
                <input wire:model.live.debounce.300ms="telefone" type="text" id="telefone"
                    placeholder="(00) 00000-0000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div class="space-y-2">
                <label for="telefone" class="block text-sm font-medium whitespace-nowrap">Data nasc Minima</label>
                <input wire:model.live.debounce.300ms="data_nascimento_minima" type="date" id="telefone"
                    placeholder="(00) 00000-0000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>
            <div class="space-y-2">
                <label for="data_nascimento_maxima" class="block text-sm font-medium whitespace-nowrap">Data Nac
                    Maxim</label>
                <input wire:model.live.debounce.300ms="data_nascimento_maxima" type="date"
                    id="data_nascimento_maxima" placeholder="(00) 00000-0000"
                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Filtro Gênero -->
            <div class="space-y-2">
                <label for="genero" class="block text-sm font-medium whitespace-nowrap">Gênero</label>
                <flux:select wire:model.live.debounce.300ms="genero" id="genero" class="w-full">
                    <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                    @foreach ($generos as $item)
                        <flux:select.option value="{{ $item }}">
                            {{ $item }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
            <div class="space-y-2">
                <label for="cpf" class="block text-sm font-medium whitespace-nowrap">CPF</label>
                <input 
                    wire:model.live.debounce.300ms="cpf" 
                    type="text" 
                    id="cpf"
                    placeholder="000.000.000-00" 
                    class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>
           
        </div>

        <!-- Botões de Ação -->
        <div class="mt-4 flex justify-end space-x-3">

        </div>
    </div>

    <div class="flex items-center justify-between mb-4" style="align-items: flex-end;">
        <div>
            <span class="text-xl font-bold tracking-wider text-gray-500 dark:text-white">
                Total: {{ $this->getPessoas()->total() }}
            </span>
        </div>
        <div class="space-y-2">
            <label for="orderBy" class="block text-sm font-medium whitespace-nowrap">Ordenar por</label>
            <flux:select wire:model.live.debounce.300ms="orderBy" id="orderBy" class="w-full">
                <flux:select.option value="created_at">Data de Criação</flux:select.option>
                <flux:select.option value="updated_at">Data de Atualização</flux:select.option>
                <flux:select.option value="idade">Idade</flux:select.option>
            </flux:select>
        </div>
    </div>


    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- People table -->
    <div
        class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm">
        <table class="min-w-full  border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefones</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase  ">Gênero
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data
                        nascimento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($this->getPessoas() as $pessoa)
               
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(!empty($pessoa->telefones))
                            {{ $pessoa->telefones[0] }} <span class="text-sm text-gray-500">(Principal)</span>
                            @if(count($pessoa->telefones) > 1)
                                / {{ implode(' / ', array_slice($pessoa->telefones, 1)) }}
                            @endif
                        @else
                            -
                        @endif

                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->genero }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($pessoa->data_nascimento)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->cpf }}</td>
                        <td class="px-6 py-4 whitespace-nowrap flex items-center">
                            
                            <button wire:click="addChamamento({{ $pessoa }})" class=" mr-3">
                                <flux:icon.arrow-up-left/>
                            </button>
                            <a href="{{ route('servos.show', $pessoa) }}"
                                class="text-blue-600 hover:text-blue-900 mr-3">
                                <flux:icon.eye />
                            </a>
                            <a href="{{ route('retirantes.edit', $pessoa) }}"
                                class="text-green-600 hover:text-green-900 mr-3">
                                <flux:icon.pencil />
                            </a>
                            @if (auth()->user()->role_id === 1)
                            <button wire:click="$set('confirmingDelete', {{ $pessoa->id }})" class="text-red-600 hover:text-red-900">
                                <flux:icon.trash />
                            </button>
                            @endif
                           
                        </td>
                    </tr>
                @endforeach

                @if (count($this->getPessoas()) === 0)
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">Nenhum registro encontrado</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Modal de Confirmação -->
    @if ($confirmingDelete)
        <div class="fixed z-10 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4">
                <div class="fixed inset-0 transition-opacity">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg leading-6 font-medium  text-gray-700 dark:text-gray-300">Confirmar Exclusão</h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tem certeza de que deseja excluir este retirante? Esta ação não pode ser desfeita.</p>
                        </div>
                    </div>
                    <div class="px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="delete({{ $confirmingDelete }})" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Confirmar
                        </button>
                        <button wire:click="$set('confirmingDelete', null)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Pagination links -->
    <div class="mt-4">
        {{ $this->getPessoas()->links() }}
    </div>
</div>
