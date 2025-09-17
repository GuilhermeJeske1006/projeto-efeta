<?php

use App\Models\PermissaoUsuarioRetiro;
use App\Models\User;
use App\Models\Pessoa;

use Livewire\Volt\Component;
use Livewire\WithPagination;
use function Livewire\Volt\{state, computed, mount, on};

with(WithPagination::class);

state([
    'searches' => [], 
    'perPage' => 15,
    'retiro_id' => '',
    'equipe_id' => '',
    'user_id' => '',

    'retiros' => [],
    'equipes' => [],
    'usuarios' => [],
    'status_chamado' => [],
    'notification' => [
        'show' => false,
        'type' => '',
        'message' => '',
    ],
]);

mount(function () {
    $this->status_chamado = \App\Models\StatusChamado::all();
});

on([
    'notification-closed' => function () {
        $this->notification['show'] = false;
    },
]);

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

// Método para atualizar busca específica por equipe
$updateSearch = function ($equipeId, $retiroId, $searchTerm) {
    $key = $equipeId . '_' . $retiroId;
    $this->searches[$key] = $searchTerm;
};

// Define the pessoas getter method
$getServos = function () {
    $userId = auth()->user()->id;

    try {
        // Busca todas as permissões do usuário, agrupadas por equipe e retiro
        $permissoes = PermissaoUsuarioRetiro::query()
            ->where('user_id', $userId)
            ->get();

        $result = [];

        foreach ($permissoes as $permissao) {
            $equipeId = $permissao->equipe_id;
            $retiroId = $permissao->retiro_id;
            $searchKey = $equipeId . '_' . $retiroId;
            $searchTerm = $this->searches[$searchKey] ?? '';

            $retiro = \App\Models\Retiro::find($retiroId);
            $equipe = \App\Models\Equipe::find($equipeId);

            // Busca pessoas associadas à equipe nesse retiro, com paginação
            $pessoas = \App\Models\PessoaRetiro::query()
                ->where('equipe_id', $equipeId)
                ->where('retiro_id', $retiroId)
                ->join('pessoas', 'pessoa_retiros.pessoa_id', '=', 'pessoas.id')
                ->leftJoin('telefones', function ($join) {
                    $join->on('telefones.pessoa_id', '=', 'pessoas.id')
                         ->where('telefones.is_principal', true);
                })
                ->when($searchTerm, function ($q) use ($searchTerm) {
                    $q->where('pessoas.nome', 'like', '%' . $searchTerm . '%');
                })
                ->select('pessoa_retiros.*', 'pessoas.nome', 'pessoas.id', 'telefones.numero as telefone')
                ->orderByDesc('is_coordenador')
                ->paginate($this->perPage, ['*'], 'page_' . $equipeId . '_' . $retiroId);

            $result[] = [
                'equipe' => $equipe,
                'retiro' => $retiro,
                'pessoas' => $pessoas,
                'search_key' => $searchKey,
                'search_term' => $searchTerm,
            ];
        }

        return $result;
    } catch (\Exception $th) {
        $this->showNotification('error', 'Erro ao carregar os servos: ' . $th->getMessage());
        return [];
    }
};

$updateStatusChamado = function ($pessoaId, $statusId, $retiroId) {
    try {
        $pessoaRetiro = \App\Models\PessoaRetiro::where('pessoa_id', $pessoaId)
            ->where('retiro_id', $retiroId)
            ->first();
        
        if (!$pessoaRetiro) {
            throw new \Exception('Pessoa não encontrada no retiro especificado.');
        }

        $pessoaRetiro->status_id = $statusId;
        $pessoaRetiro->save();

        $this->showNotification('success', 'Status do chamado atualizado com sucesso');

    } catch (\Exception $e) {
        $this->showNotification('error', 'Erro ao atualizar o status do chamado: ' . $e->getMessage());
    }
};
?>

<div>
    <livewire:components.notification :notification="$notification" />

    @foreach ($this->getServos() as $item)
    <div class="mb-8">

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-2xl font-bold">Equipe - {{ $item['equipe']->nome }} do {{ $item['retiro']->nome }}</h1>
            </div>
        </div>

        <div class="w-full border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="space-y-2">
                    <label for="search_{{ $item['search_key'] }}" class="block text-sm font-medium whitespace-nowrap">Buscar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <input 
                            wire:model.live.debounce.300ms="searches.{{ $item['search_key'] }}"
                            type="text" 
                            id="search_{{ $item['search_key'] }}"
                            placeholder="Buscar servos da equipe {{ $item['equipe']->nome }}..." 
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                            value="{{ $item['search_term'] }}"
                        >
                    </div>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 rounded-lg shadow-sm">
            <table class="min-w-full border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Telefone</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($item['pessoas'] as $pessoa)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {{ $pessoa['nome'] }}
                                @if($pessoa['is_coordenador'])
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        Coordenador
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">{{ $pessoa['telefone'] ?? 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <select 
                                    class="border w-full rounded px-2 py-1 md:px-2 md:py-1 text-base md:text-sm"
                                    wire:change="updateStatusChamado({{ $pessoa['id'] }}, $event.target.value, {{ $item['retiro']->id }})"
                                    x-data
                                    :style="window.innerWidth < 768 ? 'font-size: 1.1rem; width: 160px;' : 'font-size: 1.1rem;'"
                                >
                                    @foreach ($this->status_chamado as $status)
                                        <option value="{{ $status->id }}" {{ ($pessoa['status_id'] ?? $pessoa['status_chamado_id']) == $status->id ? 'selected' : '' }}>
                                            {{ $status->nome }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap flex items-center">
                                <a href="{{ route('servos.show', $pessoa) }}"
                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                    <flux:icon.eye />
                                </a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($item['pessoas']->isEmpty())
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">
                                @if($item['search_term'])
                                    Nenhum registro encontrado para "{{ $item['search_term'] }}"
                                @else
                                    Nenhum servo cadastrado nesta equipe
                                @endif
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $item['pessoas']->links() }}
        </div>
                
    </div>
    @endforeach

    @if(empty($this->getServos()))
        <div class="text-center py-8">
            <div class="text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Nenhuma equipe encontrada</h3>
                <p class="mt-1 text-sm text-gray-500">Você não possui permissão para visualizar equipes ou não há equipes cadastradas.</p>
            </div>
        </div>
    @endif
</div>