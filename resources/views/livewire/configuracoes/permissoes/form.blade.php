<?php
use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use App\Models\TipoPessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed};
use Livewire\WithFileUploads;

// Define state properties
state([
    // Pessoa data
    'user_id' => '',
    'retiro_id' => '',
    'equipe_id' => '',

    'retiros' => [],
    'equipes' => [],
    'usuarios' => [],

    'dados' => [],

]);

// Define validation rules
rules([
    'user_id' => ['required', 'exists:users,id'],
    'retiro_id' => ['required', 'exists:retiros,id'],
    'equipe_id' => ['required', 'exists:equipes,id'],

])->messages([
    'user_id.required' => 'O campo usuário é obrigatório.',
    'user_id.exists' => 'O usuário selecionado não existe.',
    'retiro_id.required' => 'O campo retiro é obrigatório.',
    'retiro_id.exists' => 'O retiro selecionado não existe.',
    'equipe_id.required' => 'O campo equipe é obrigatório.',
    'equipe_id.exists' => 'A equipe selecionada não existe.',

]);

// Mount component
mount(function () {

    $this->user_id = $this->dados['user_id'] ?? '';
    $this->retiro_id = $this->dados['retiro_id'] ?? '';
    $this->equipe_id = $this->dados['equipe_id'] ?? '';


    $this->retiros = \App\Models\Retiro::all();
    $this->equipes = \App\Models\Equipe::all();
    $this->usuarios = \App\Models\User::all();

    if(auth()->user()->role_id !== 1) {
        abort(403, 'Acesso não autorizado');
    }
});


$enviarDados = function () {

    $this->validate();

    // Emite um evento com todos os dados do formulário
    $envio = $this->dispatch('dados-formulario', [
        'user_id' => $this->user_id,
        'retiro_id' => $this->retiro_id,
        'equipe_id' => $this->equipe_id,
    ]);

};

?>
<div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">


    <!-- Nome -->
    <div>
        <flux:select wire:model="retiro_id" :label="__('Retiro')" required>
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

    <div>
        <flux:select wire:model="equipe_id" :label="__('Equipe')" required>
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

    <!-- Gênero -->
    <div>
        <flux:select wire:model="user_id" :label="__('Usuario')" required>
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
<div class="flex justify-end space-x-2 pt-4 mt-3 ">
    <flux:button type="button" variant="primary" wire:click="$refresh">
        {{ __('Cancelar') }}
    </flux:button>
    <flux:button type="button" wire:click="enviarDados" variant="primary">
        {{ __('Salvar') }}
    </flux:button>
</div>
</div>

