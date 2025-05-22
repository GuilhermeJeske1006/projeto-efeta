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
    'estado_civil' => null,
    'telefone' => null,
    'nome' => null,

]);

// Define the pessoas getter method
$getPessoas = function () {

    $result = Pessoa::query()
        ->leftJoin('telefones', function ($join) {
            $join->on('telefones.pessoa_id', '=', 'pessoas.id')
                 ->where('telefones.is_principal', true);
            })
        ->where('pessoas.tipo_pessoa_id', 1)
        ->select('pessoas.*', 'telefones.numero as telefone_principal')
        ->when($this->ja_trabalhou, function ($query) {
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
        ->orderBy('pessoas.nome')
        ->paginate($this->perPage);

    return $result;
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
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Servos</h1>
            <p class="">Gerencie os servos cadastrados no sistema.</p>
        </div>
        <a href="{{ route('servos.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Adicionar Servo
        </a>
    </div>
    <div class="w-full border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm p-4 mb-6">
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
                <label for="telefone" class="block text-sm font-medium whitespace-nowrap">Telefone</label>
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
                <label for="ja_trabalhou" class="block text-sm font-medium whitespace-nowrap">Já trabalhou?</label>
                <flux:select 
                wire:model.live.debounce.300ms="ja_trabalhou" 
                    id="ja_trabalhou"
                    class="w-full"
                >
                    <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                    <flux:select.option value="1">{{ __('Sim') }}</flux:select.option>
                    <flux:select.option value="0">{{ __('Não') }}</flux:select.option>
                </flux:select>
            </div>
    
            <!-- Filtro Gênero -->
            <div class="space-y-2">
                <label for="genero" class="block text-sm font-medium whitespace-nowrap">Gênero</label>
                <flux:select 
                wire:model.live.debounce.300ms="genero" 
                    id="genero"
                    class="w-full"
                >
                    <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                    @foreach ($generos as $item)
                        <flux:select.option value="{{ $item }}">
                            {{ $item }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>
        </div>
    
        <!-- Botões de Ação -->
        <div class="mt-4 flex justify-end space-x-3">
     
        </div>
    </div>

    <!-- Flash message -->
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    <!-- People table -->
    <div class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm">
        <table class="min-w-full  border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone Principal</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gênero</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado Civil</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">CPF</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($this->getPessoas() as $pessoa)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->telefone_principal ?? 'Sem telefone' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->genero }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->estado_civil }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->cpf }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{-- <a href="{{ route('pessoas.show', $pessoa) }}" class="text-blue-600 hover:text-blue-900 mr-3">Ver</a>
                            <a href="{{ route('pessoas.edit', $pessoa) }}" class="text-green-600 hover:text-green-900 mr-3">Editar</a> --}}
                            <button wire:click="delete({{ $pessoa->id }})" class="text-red-600 hover:text-red-900">Excluir</button>
                        </td>
                    </tr>
                @endforeach

                @if(count($this->getPessoas()) === 0)
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">Nenhum registro encontrado</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination links -->
    <div class="mt-4">
        {{ $this->getPessoas()->links() }}
    </div>
</div>