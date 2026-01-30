<?php

use App\Models\Retiro;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\{state, computed, uses};

uses([WithPagination::class]);

// Define state properties
state([
    'search' => '',
    'perPage' => 15,

]);

// Reset pagination when filters change
$updatedSearch = function () { $this->resetPage(); };

// Define the pessoas getter method
$getRetiros = function () {

    $result = Retiro::query()
        ->where('nome', 'like', '%' . $this->search . '%')
        ->paginate($this->perPage);

    return $result;
};


// Delete method
$delete = function ($id) {
    $retiro = Retiro::findOrFail($id);
    if ($retiro) {
        $retiro->delete();
        session()->flash('message', 'Retiro excluída com sucesso!');
    }
};

?>

<div>
    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Retiros</h1>
            <p class="">Gerencie os retiros cadastrados no sistema.</p>
        </div>
        <a href="{{ route('retiros.create') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Adicionar
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
                        wire:model.live.debounce.300ms="search" 
                        type="text" 
                        id="search"
                        placeholder="Buscar retiros..." 
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    >
                </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Inicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Data Fim</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($this->getRetiros() as $retiro)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $retiro->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($retiro->data_inicio)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ \Carbon\Carbon::parse($retiro->data_fim)->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap flex items-center">
                            @if (auth()->user()->role_id === 1)

                            <a href="{{ route('servos-retiros.index', $retiro->id) }}"  class="text-blue-600 hover:text-blue-900 mr-3" title="Ver Servos">
                                <flux:icon.users/>   
                            </a>
                            <a href="{{ route('retirantes-retiros.index', $retiro->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver Retirantes">
                                <flux:icon.user-group/>   
                            </a>
                            <a href="{{ route('retiros.edit', $retiro) }}" class="text-green-600 hover:text-green-900 mr-3">
                                <flux:icon.pencil/>   

                            </a>
                            <button wire:click="delete({{ $retiro->id }})" class="text-red-600 hover:text-red-900">

                                <flux:icon.trash/>   

                            </button>
                            @elseif (auth()->user()->role_id === 2)
                                <a href="{{ route('retirantes-retiros.index', $retiro->id) }}" class="text-blue-600 hover:text-blue-900 mr-3" title="Ver Retirantes">
                                    <flux:icon.user-group/>   
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforeach

                @if(count($this->getRetiros()) === 0)
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center">Nenhum registro encontrado</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Pagination links -->
    <div class="mt-4">
        {{ $this->getRetiros()->links() }}
    </div>
</div>