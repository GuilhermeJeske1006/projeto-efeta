<?php

use App\Models\Role;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\{state, computed, mount, uses};

uses([WithPagination::class]);

// Define state properties
state([
    'search' => '',
    'perPage' => 15,
    'telefone' => null,
    'nome' => null,
    'email' => null,
    'roleId' => null,
    'isEdit' => false,
    'userId' => null,
]);

mount(function () {
    if(auth()->user()->role_id !== 1) {
        abort(403, 'Acesso não autorizado');
    }
});

// Reset pagination when filters change
$updatedNome = function () { $this->resetPage(); };
$updatedTelefone = function () { $this->resetPage(); };

// Define the pessoas getter method
$getPessoas = function () {
    return User::query()
        ->join('roles', 'roles.id', '=', 'users.role_id')
        ->when($this->search, function ($query) {
            return $query->where('name', 'like', '%' . $this->search . '%')
                         ->orWhere('email', 'like', '%' . $this->search . '%');
        })
        ->when($this->telefone, function ($query) {
            return $query->where('telefone', 'like', '%' . $this->telefone . '%');
        })
        ->when($this->nome, function ($query) {
            return $query->where('name', 'like', '%' . $this->nome . '%');
        })
        ->when($this->email, function ($query) {
            return $query->where('email', 'like', '%' . $this->email . '%');
        })
        ->select('users.*', 'roles.nome as role_nome')
        ->orderBy('name', 'asc')
        ->paginate($this->perPage);
};

// Create user method
$createUser = function () {
    $this->validate([
        'nome' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'telefone' => 'nullable|string|max:15',
        'roleId' => 'required|exists:roles,id',
    ]);

    User::create([
        'name' => $this->nome,
        'email' => $this->email,
        'telefone' => $this->telefone,
        'role_id' => $this->roleId,
    ]);

    session()->flash('message', 'Usuário criado com sucesso!');
    $this->reset(['nome', 'email', 'telefone', 'roleId']);
};

// Edit user method
$editUser = function ($id) {
    $user = User::findOrFail($id);
    $this->userId = $user->id;
    $this->nome = $user->name;
    $this->email = $user->email;
    $this->telefone = $user->telefone;
    $this->roleId = $user->role_id;
    $this->isEdit = true;
};


// Delete user method
$delete = function ($id) {
    $user = User::find($id);
    if ($user) {
        $user->delete();
        session()->flash('message', 'Usuário excluído com sucesso!');
    }
};

?>

<div>

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Lista de usuarios</h1>
            <p class="">Gerencie os usuarios cadastrados no sistema.</p>
        </div>
        <a href="{{ route('pessoas.create') }}"
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
                        placeholder="Buscar usuarios..."
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
    <div
        class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm">
        <table class="min-w-full  border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Função
 
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($this->getPessoas() as $pessoa)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->email ?? 'Sem email' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->telefone ?? 'Sem telefone' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa->role_nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap flex items-center">
                            @if (auth()->id() === $pessoa->id) 
                                <span class="text-gray-400 italic me-3">(Você)</span>
                                
                            @endif
                            @if (auth()->user()->role_id === 1)
                                <a href="{{ route('pessoas.edit', $pessoa) }}"
                                    class="text-green-600 hover:text-green-900 mr-3">
                                    <flux:icon.pencil />

                                </a>
                                <button wire:click="delete({{ $pessoa->id }})" class="text-red-600 hover:text-red-900">

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

    <!-- Pagination links -->
    <div class="mt-4">
        {{ $this->getPessoas()->links() }}
    </div>

</div>


