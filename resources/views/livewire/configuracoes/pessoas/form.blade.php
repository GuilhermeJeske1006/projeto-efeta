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
    'nome' => '',
    'email' => '',
    'role_id' => '',
    'telefone' => '',

    'roles' => [],

    'dados' => [],

]);

// Define validation rules
rules([
    'nome' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'max:255'],
    'role_id' => ['required', 'exists:roles,id'],
    'telefone' => ['nullable', 'string', 'min:10', 'max:15']

])->messages([
    'nome.required' => 'O nome é obrigatório',
    'email.required' => 'O e-mail é obrigatório',
    'email.email' => 'O e-mail deve ser um endereço válido',
    'role_id.required' => 'A função é obrigatória',
    'role_id.exists' => 'A função selecionada é inválida',
    'telefone.min' => 'O telefone deve ter pelo menos 10 dígitos',
    'telefone.max' => 'O telefone deve ter no máximo 15 dígitos',

]);

// Mount component
mount(function () {

    $this->nome = $this->dados['nome'] ?? '';
    $this->email = $this->dados['email'] ?? '';
    $this->telefone = $this->dados['telefone'] ?? '';
    $this->role_id = $this->dados['role_id'] ?? '';


    $this->roles = \App\Models\Role::all();

    if(auth()->user()->role_id !== 1) {
        abort(403, 'Acesso não autorizado');
    }

});




$formatarEValidarTelefone = function ($index) {
    // Remove caracteres não numéricos
    $telefone = preg_replace('/[^0-9]/', '', $this->telefones[$index]['numero']);

    // Formata o telefone
    if (strlen($telefone) > 2) {
        $formatted = '(' . substr($telefone, 0, 2) . ')';

        if (strlen($telefone) > 7) {
            // Formato com 9 dígitos: (XX) XXXXX-XXXX
            $formatted .= ' ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
        } else {
            // Formato com 8 dígitos: (XX) XXXX-XXXX
            $formatted .= ' ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
        }
    } else {
        $formatted = $telefone;
    }

    $this->telefones[$index]['numero'] = $formatted;

    // Valida o telefone
    if (strlen($telefone) < 10) {
        if (strlen($telefone) > 0) {
            $this->addError('telefones.' . $index . '.numero', 'Telefone deve ter pelo menos 10 dígitos.');
        }
    } else {
        $this->resetErrorBag('telefones.' . $index . '.numero');
    }
};


$updatedTelefones = function ($value, $key) {
    if (str_contains($key, 'numero')) {
        // Extrair o índice do array de telefones
        preg_match('/telefones\.(\d+)\.numero/', $key, $matches);

        if (isset($matches[1])) {
            $index = $matches[1];
            $this->formatarEValidarTelefone($index);
        }
    }
};

 


$enviarDados = function () {

    $this->validate();

    // Emite um evento com todos os dados do formulário
    $teste = $this->dispatch('dados-formulario', [
        // Dados da pessoa
        'nome' => $this->nome,
        'email' => $this->email,
        'role_id' => $this->role_id,
        'telefone' => $this->telefone,
        
        // Dados dos telefones
        'telefone' => $this->telefone,
    ]);

};

?>
<div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="col-span-3">
        <h3 class="text-md font-medium mb-2 border-b pb-2">Dados Pessoais</h3>
    </div>

    <!-- Nome -->
    <div>
        <flux:input wire:model="nome" :label="__('Nome Completo')" type="text" required />
    </div>
 
    <!-- Email -->
    <div>
        <flux:input wire:model="email" :label="__('E-mail')" type="email" required />
    </div>

    <!-- Data de Nascimento -->
    <div>
        <flux:input wire:model="telefone" :label="__('Telefone')" type="text" placeholder="(00) 00000-0000"
            required oninput="this.value = this.value.replace(/[^0-9]/g, '').replace(/(\d{2})(\d{5})?(\d{4})?/, function(_, ddd, prefix, suffix) { return '(' + ddd + ') ' + (prefix || '') + (suffix ? '-' + suffix : ''); })" />
    </div>

    <!-- Gênero -->
    <div>
        <flux:select wire:model="role_id" :label="__('Função')" required>
            <flux:select.option value="" disabled>
                {{ __('Selecione a função') }}
            </flux:select.option>
            @foreach ($this->roles as $role)
                <flux:select.option value="{{ $role->id }}">
                    {{ $role->nome }}
                </flux:select.option>
            @endforeach
        </flux:select>

    </div>

    

</div>
<div class="flex justify-end space-x-2 pt-4 ">
    <flux:button type="button" variant="primary" wire:click="$refresh">
        {{ __('Cancelar') }}
    </flux:button>
    <flux:button type="button" wire:click="enviarDados" variant="primary">
        {{ __('Enviar Dados') }}
    </flux:button>
</div>
</div>

