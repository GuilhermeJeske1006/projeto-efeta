<?php

use App\Models\PermissaoUsuarioRetiro;
use App\Models\User;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\{state, computed, mount};

with(WithPagination::class);

// Define state properties
state([
    'search' => '',
    'perPage' => 15,
    'retiro_id' => '',
    'equipe_id' => '',
    'user_id' => '',

    'retiros' => [],
    'equipes' => [],
    'usuarios' => [],
]);

mount(function () {
    if(auth()->user()->role_id !== 1) {
        abort(403, 'Acesso não autorizado');
    }

    $this->retiros = \App\Models\Retiro::all();
    $this->equipes = \App\Models\Equipe::all();
    $this->usuarios = \App\Models\User::all();
});


// Define the pessoas getter method
$getPessoas = function () {

    try {
        $query = PermissaoUsuarioRetiro::query()
            ->when($this->retiro_id, function ($q) {
            $q->where('retiro_id', $this->retiro_id);
            })
            ->when($this->equipe_id, function ($q) {
            $q->where('equipe_id', $this->equipe_id);
            })
            ->when($this->user_id, function ($q) {
            $q->where('user_id', $this->user_id);
            })
            ->with('user', 'retiro', 'equipe')
            ->paginate($this->perPage);


        return $query;
    } catch (\Exception $th) {
       dd($th->getMessage());
    }


};


// Delete user method
$removerPermissao = function ($id) {
    try {
        $permissao = PermissaoUsuarioRetiro::findOrFail($id);
        $permissao->delete();

        session()->flash('message', 'Permissão removida com sucesso.');
    } catch (\Exception $e) {
        session()->flash('error', 'Erro ao remover permissão: ' . $e->getMessage());
    }
};

?>

<div>

    <div class="flex items-center justify-between mb-5">
        <div>
            <h1 class="text-2xl font-bold">Lista de permissões</h1>
            <p class="">Gerencie a permissão dos usuarios no sistema.</p>
        </div>
        <a href="{{ route('permissoes.create') }}"
            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Adicionar permissão 
        </a>
    </div>
    <div
        class="w-full border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm p-4 mb-6">
        <!-- Título do Filtro -->
        <h3 class="text-lg font-medium whitespace-nowrap mb-4">Filtrar</h3>

        <!-- Grid de Filtros -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="space-y-2">
                <flux:select wire:model.live.debounce.300ms="retiro_id" :label="__('Retiro')" required>
                    <flux:select.option value="" disabled>
                        {{ __('Selecione o retiro') }}
                    </flux:select.option>
                    @foreach ($this->retiros as $item)
                        <flux:select.option value="{{ $item->id }}">
                            {{ $item->nome }}
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            <div class="space-y-2">
                <flux:select wire:model.live.debounce.300ms="equipe_id" :label="__('Equipe')" required>
            <flux:select.option value="" disabled>
                {{ __('Selecione a equipe') }}
            </flux:select.option>
            @foreach ($this->equipes as $item)
                <flux:select.option value="{{ $item->id }}">
                    {{ $item->nome }}
                </flux:select.option>
            @endforeach
        </flux:select>
            </div>

            <div class="space-y-2">
                <flux:select wire:model.live.debounce.300ms="user_id" :label="__('Usuario')" required>
                    <flux:select.option value="" disabled>
                        {{ __('Selecione o usuario') }}
                    </flux:select.option>
                    @foreach ($this->usuarios as $item)
                        <flux:select.option value="{{ $item->id }}">
                            {{ $item->name }}
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
    <div
        class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm">
        <table class="min-w-full  border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Retiro</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipe</th>
 
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach ($this->getPessoas() as $item)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->user->name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->retiro->nome }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">{{ $item->equipe->nome}}</td>
                        <td class="px-6 py-4 whitespace-nowrap flex items-center">
                            @if (auth()->user()->role_id === 1)
                            
                                <button wire:click="removerPermissao({{ $item->id }})" class="text-red-600 hover:text-red-900">

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


