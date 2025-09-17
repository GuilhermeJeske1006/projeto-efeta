<?php

use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use App\Models\TipoPessoa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use function Livewire\Volt\{state, rules, mount, computed, on};
use Livewire\WithFileUploads;

// Define state properties
state([
    'pessoaId' => null,

    // Pessoa data
    'formData' => [
        'id' => null,
        'nome' => '',
        'cpf' => '',
        'data_nascimento' => '',
        'email' => '',
        'tipo_pessoa_id' => '',
        'is_problema_saude' => false,
        'descricao' => '',
        'ja_trabalhou' => false,
        'genero' => '',
        'estado_civil' => '',

        // Endereço data
        'logradouro' => '',
        'numero' => '',
        'complemento' => '',
        'bairro' => '',
        'cidade' => '',
        'estado' => '',
        'cep' => '',
        'pais' => 'Brasil',

        // Telefones data
        'telefones' => [],

        // Select lists
        'tiposPessoa' => [],
    ],

    'notification' => [
        'show' => false,
        'type' => '',
        'message' => '',
    ],

    'dadosFormulario' => [],
    'pessoa' => null,
]);

// Mount function to load existing data
mount(function ($id = null) {
    if (!$id) {
        abort(404, 'ID da pessoa não informado');
    }

    $this->pessoaId = $id;
    $this->carregarDadosPessoa();
});

// Load person data
$carregarDadosPessoa = function () {
    try {
        $this->pessoa = DB::table('pessoas')
                ->leftJoin('enderecos_pessoas', 'enderecos_pessoas.pessoa_id', '=', 'pessoas.id')
                ->leftJoin('enderecos', 'enderecos.id', '=', 'enderecos_pessoas.endereco_id')
                ->where('pessoas.id', $this->pessoaId)
                ->select('pessoas.*', 'enderecos.*')
                ->limit(1)
                ->first();
                
        $this->telefones = DB::table('telefones')->where('pessoa_id', $this->pessoaId)->get()->toArray();

        // Carregar dados básicos da pessoa
        $this->formData['id'] = $this->pessoa->id;
        $this->formData['nome'] = $this->pessoa->nome;
        $this->formData['cpf'] = $this->pessoa->cpf;
        $this->formData['data_nascimento'] = $this->pessoa->data_nascimento;
        $this->formData['email'] = $this->pessoa->email ?? '';
        $this->formData['tipo_pessoa_id'] = $this->pessoa->tipo_pessoa_id;
        $this->formData['is_problema_saude'] = $this->pessoa->is_problema_saude;
        $this->formData['descricao'] = $this->pessoa->descricao ?? '';
        $this->formData['ja_trabalhou'] = $this->pessoa->ja_trabalhou;
        $this->formData['genero'] = $this->pessoa->genero ?? '';
        $this->formData['estado_civil'] = $this->pessoa->estado_civil ?? '';

        // Carregar dados do endereço (primeiro endereço se existir)
        $this->formData['logradouro'] = $this->pessoa->logradouro ?? '';
        $this->formData['numero'] = $this->pessoa->numero ?? '';
        $this->formData['complemento'] = $this->pessoa->complemento ?? '';
        $this->formData['bairro'] = $this->pessoa->bairro ?? '';
        $this->formData['cidade'] = $this->pessoa->cidade ?? '';
        $this->formData['estado'] = $this->pessoa->estado ?? 'SC';
        $this->formData['cep'] = $this->pessoa->cep ?? '';
        $this->formData['pais'] = $this->pessoa->pais ?? 'Brasil';

        // // Carregar telefones
        $this->formData['telefones'] = array_map(function ($telefone) {
            return [
                'id' => $telefone->id,
                'numero' => $telefone->numero,
                'tipo' => $telefone->tipo,
                'nome_pessoa' => $telefone->nome_pessoa,
                'is_principal' => $telefone->is_principal,
            ];
        }, $this->telefones);
    } catch (\Exception $e) {
        $this->showNotification('error', 'Erro ao carregar dados da pessoa: ' . $e->getMessage());
        return redirect()->route('servos.index');
    }
};

on([
    'dados-formulario' => function ($dados) {
        $this->dadosFormulario = $dados;
        $this->atualizar();
    },
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

// Update the person record
$atualizar = function () {
    if (empty($this->dadosFormulario)) {
        $this->showNotification('error', 'Nenhum dado recebido do formulário.');
        return;
    }

    try {
        DB::beginTransaction();

        $dados = $this->dadosFormulario;

        $pessoa = Pessoa::find($this->pessoaId)->with('telefones')->first();

        if (!$pessoa) {
            $this->showNotification('error', 'Pessoa não encontrada.');
            return;
        }

        // Update person
        $pessoa->update([
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'],
            'data_nascimento' => $dados['data_nascimento'] ?: null,
            'email' => $dados['email'] ?: null,
            'tipo_pessoa_id' => $dados['tipo_pessoa_id'] ?? 1,
            'is_problema_saude' => $dados['is_problema_saude'] ?? false,
            'descricao' => $dados['descricao'] ?: null,
            'ja_trabalhou' => $dados['ja_trabalhou'] ?? false,
            'genero' => $dados['genero'] ?: null,
            'estado_civil' => $dados['estado_civil'] ?: null,
        ]);

        // Update or create address
        if (!empty($dados['logradouro']) || !empty($dados['cep'])) {
            $endereco = DB::table('pessoas')->join('enderecos_pessoas', 'enderecos_pessoas.pessoa_id', '=', 'pessoas.id')->join('enderecos', 'enderecos.id', '=', 'enderecos_pessoas.endereco_id')->where('pessoas.id', $this->pessoaId)->select('enderecos.*')->limit(1)->first();

            $endereco = Endereco::find($endereco->id);
            // Update existing address
            $endereco->update([
                'logradouro' => $dados['logradouro'] ?: '',
                'numero' => $dados['numero'] ?: '',
                'complemento' => $dados['complemento'] ?: '',
                'bairro' => $dados['bairro'] ?: '',
                'cidade' => $dados['cidade'] ?: '',
                'estado' => $dados['estado'] ?: 'SC',
                'cep' => $dados['cep'] ?: '',
                'pais' => $dados['pais'] ?: 'Brasil',
            ]);
        }

        // Update phone numbers
        if (!empty($dados['telefones']) && is_array($dados['telefones'])) {
            // Get existing phone IDs
            $existingPhoneIds = $pessoa->telefones->pluck('id')->toArray();

            $updatedPhoneIds = [];

            foreach ($dados['telefones'] as $telefone) {
                if (!empty($telefone['numero'])) {
                    if (isset($telefone['id']) && in_array($telefone['id'], $existingPhoneIds)) {
                        // Update existing phone
                        Telefone::where('id', $telefone['id'])->update([
                            'numero' => $telefone['numero'],
                            'tipo' => $telefone['tipo'] ?? 'celular',
                            'nome_pessoa' => $telefone['nome_pessoa'] ?: $dados['nome'],
                            'is_principal' => $telefone['is_principal'] ?? false,
                        ]);
                        $updatedPhoneIds[] = $telefone['id'];
                    } else {
                        // Create new phone
                        $novoTelefone = Telefone::create([
                            'numero' => $telefone['numero'],
                            'tipo' => $telefone['tipo'] ?? 'celular',
                            'nome_pessoa' => $telefone['nome_pessoa'] ?: $dados['nome'],
                            'pessoa_id' => $this->pessoa->id,
                            'is_principal' => $telefone['is_principal'] ?? false,
                        ]);
                        $updatedPhoneIds[] = $novoTelefone->id;
                    }
                }
            }

            // Delete phones that were removed
            $phonesToDelete = array_diff($existingPhoneIds, $updatedPhoneIds);
            if (!empty($phonesToDelete)) {
                Telefone::whereIn('id', $phonesToDelete)->delete();
            }
        } else {
            // If no phones provided, delete all existing phones
            $this->pessoa->telefones()->delete();
        }

        DB::commit();

        $this->showNotification('success', 'Pessoa atualizada com sucesso!');

        // Recarregar dados atualizados
        $this->carregarDadosPessoa();
    } catch (\Exception $e) {
        dd($e);
        DB::rollBack();

        $this->showNotification('error', 'Erro ao atualizar pessoa: ' . $e->getMessage());
    }
};

?>

<section class="w-full">
    <livewire:components.notification :notification="$notification" />

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-5 gap-4">
        <div>
            <h1 class="text-2xl font-bold">Editar servo</h1>
            <p class="">Informe os dados abaixo e edite o servo</p>
        </div>
        <a href="{{ route('servos.index') }}"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 w-full md:w-auto justify-center">
            Voltar a lista
        </a>
    </div>

    @if ($pessoa)
        <div>
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <livewire:servos.form :dados="$formData" @dados-updated="$set('formData', $event)" />
            </div>
        </div>
    @else
        <div class="p-6 text-center">
            <p class="text-gray-500">Carregando dados da pessoa...</p>
        </div>
    @endif
</section>
