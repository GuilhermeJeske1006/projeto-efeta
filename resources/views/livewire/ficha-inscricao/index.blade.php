<?php

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use function Livewire\Volt\{rules, mount, computed};

new #[Layout('components.layouts.auth-ficha')] class extends Component {
    // DADOS PESSOAIS
    public $nome = '';
    public $cpf = '';
    public $rg = '';
    public $data_nascimento = '';
    public $email = '';
    public $telefone = '';
    public $genero = '';
    public $estado_civil = '';
    public $nacionalidade = 'Brasileira';
    public $profissao = '';
    public $religiao = 'Católica';
    public $sacramento = 'Batismo, Eucaristia';
    public $comunidade = '';

    // RESPONSÁVEIS ADICIONAIS
    public $responsaveis = [['nome_pessoa' => '', 'numero' => '']];
    public $nome_pessoa = '';
    public $telefone_pessoa = '';

    // ENDEREÇO
    public $cep = '';
    public $logradouro = '';
    public $numero = '';
    public $complemento = '';
    public $bairro = '';
    public $cidade = '';
    public $estado = 'SC';
    public $pais = 'Brasil';

    // SAÚDE E EMERGÊNCIA
    public $is_problema_saude = false;
    public $descricao = '';

    // MOTIVO
    public $motivo = '';

    // TERMOS E CONDIÇÕES
    public $aceita_termos = false;
    public $aceita_imagem = false;
    public $aceita_comunicacao = false;

    // ARRAYS DE OPÇÕES
    public $generos = ['Masculino', 'Feminino', 'Outro', 'Prefiro não informar'];
    public $estados_civis = ['Solteiro(a)', 'Casado(a)', 'Divorciado(a)', 'Viúvo(a)', 'União Estável'];
    public $estados = ['AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'];
    public $parentescos = ['Pai', 'Mãe', 'Avô', 'Avó', 'Tio', 'Tia', 'Irmão', 'Irmã', 'Outro'];

    // CONTROLES
    public $menor_idade = false;

    public $notification = [
        'show' => false,
        'type' => '',
        'message' => '',
    ];

    // Define validation rules como método estático
    public static function rules()
    {
        return [
            'nome' => ['required', 'string', 'max:255'],
            'cpf' => ['required', 'string', 'size:14', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
            'rg' => ['nullable', 'string', 'max:20'],
            'data_nascimento' => ['required', 'date', 'before:today'],
            'email' => ['required', 'email', 'max:255'],
            'telefone' => ['required', 'string', 'min:14', 'max:15'],
            'genero' => ['required', 'string'],
            'estado_civil' => ['required', 'string'],
            'profissao' => ['nullable', 'string', 'max:100'],
            'religiao' => ['required', 'string', 'max:100'],
            'sacramento' => ['nullable', 'string', 'max:255'],
            'comunidade' => ['nullable', 'string', 'max:255'],

            // Endereço
            'cep' => ['required', 'string', 'size:9'],
            'logradouro' => ['required', 'string', 'max:255'],
            'numero' => ['required', 'string', 'max:20'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['required', 'string', 'max:100'],
            'cidade' => ['required', 'string', 'max:100'],
            'estado' => ['required', 'string', 'size:2'],
            'pais' => ['required', 'string', 'max:50'],

            // Saúde
            'descricao' => ['nullable', 'string', 'max:1000'],

            // Motivo
            'motivo' => ['required', 'string', 'max:1000'],

            // Termos
            'aceita_termos' => ['accepted'],

            // Responsáveis adicionais
            'responsaveis' => ['array'],
            'responsaveis.*.nome_pessoa' => ['required', 'string', 'max:255'],
            'responsaveis.*.numero' => ['required', 'string', 'min:14', 'max:15'],
        ];
    }

    // Define mensagens de validação como método estático
    public static function messages()
    {
        return [
            'nome.required' => 'O nome é obrigatório',
            'cpf.required' => 'O CPF é obrigatório',
            'cpf.size' => 'O CPF deve estar no formato 000.000.000-00',
            'cpf.regex' => 'O CPF deve estar no formato 000.000.000-00',
            'data_nascimento.required' => 'A data de nascimento é obrigatória',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Informe um email válido',
            'telefone.required' => 'O telefone é obrigatório',
            'telefone.min' => 'O telefone deve ter pelo menos 14 caracteres',
            'genero.required' => 'Selecione um gênero',
            'estado_civil.required' => 'Selecione um estado civil',
            'religiao.required' => 'A religião é obrigatória',

            // Responsável
            'nome_responsavel.required_if' => 'O nome do responsável é obrigatório para menores de idade',
            'cpf_responsavel.required_if' => 'O CPF do responsável é obrigatório para menores de idade',
            'telefone_responsavel.required_if' => 'O telefone do responsável é obrigatório para menores de idade',
            'parentesco.required_if' => 'O parentesco é obrigatório para menores de idade',

            // Endereço
            'cep.required' => 'O CEP é obrigatório',
            'cep.size' => 'O CEP deve ter 9 caracteres (00000-000)',
            'logradouro.required' => 'O logradouro é obrigatório',
            'numero.required' => 'O número é obrigatório',
            'bairro.required' => 'O bairro é obrigatório',
            'cidade.required' => 'A cidade é obrigatória',
            'estado.required' => 'O estado é obrigatório',
            'pais.required' => 'O país é obrigatório',

            // Outros
            'motivo.required' => 'O motivo é obrigatório',
            'aceita_termos.accepted' => 'É necessário aceitar os termos de uso',

            // Responsáveis adicionais
            'responsaveis.*.nome_pessoa.required' => 'O nome do responsável é obrigatório',
            'responsaveis.*.numero.required' => 'O telefone do responsável é obrigatório',
        ];
    }

    // Mount component como método público
    public function mount()
    {
        // Definir valores padrão
        $this->genero = $this->generos[0] ?? '';
        $this->estado_civil = $this->estados_civis[0] ?? '';
        $this->responsaveis = [['nome_pessoa' => '', 'numero' => '']];
    }


    // Buscar CEP
    public function buscarCep()
    {
        $cep = preg_replace('/[^0-9]/', '', $this->cep);

        if (strlen($cep) === 8) {
                $response = file_get_contents("https://viacep.com.br/ws/{$cep}/json/");

                if ($response === false) {
                    throw new \Exception('Erro na consulta do CEP');
                }

                $endereco = json_decode($response);

                if (!isset($endereco->erro)) {
                    $this->logradouro = $endereco->logradouro ?? '';
                    $this->bairro = $endereco->bairro ?? '';
                    $this->cidade = $endereco->localidade ?? '';
                    $this->estado = $endereco->uf ?? 'SC';
                    $this->pais = 'Brasil';

                    
                    $this->show;
                } else {
                   $this->showNotification('error', 'CEP não encontrado');
                }

        } else {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'CEP deve ter 8 dígitos',
            ]);
        }
    }

    // Formatar CEP
    public function formatarCep()
    {
        $cep = preg_replace('/[^0-9]/', '', $this->cep);

        if (strlen($cep) <= 8) {
            if (strlen($cep) > 5) {
                $this->cep = substr($cep, 0, 5) . '-' . substr($cep, 5);
            } else {
                $this->cep = $cep;
            }
        }
    }

    // Formatar CPF
    public function formatarCpf($campo = 'cpf')
    {
        $cpf = preg_replace('/[^0-9]/', '', $this->$campo);

        if (strlen($cpf) <= 11) {
            $formatted = '';

            if (strlen($cpf) > 3) {
                $formatted .= substr($cpf, 0, 3) . '.';

                if (strlen($cpf) > 6) {
                    $formatted .= substr($cpf, 3, 3) . '.';

                    if (strlen($cpf) > 9) {
                        $formatted .= substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
                    } else {
                        $formatted .= substr($cpf, 6);
                    }
                } else {
                    $formatted .= substr($cpf, 3);
                }
            } else {
                $formatted = $cpf;
            }

            $this->$campo = $formatted;
        }
    }

    // Formatar telefone
    public function formatarTelefone($campo = 'telefone')
    {
        $telefone = preg_replace('/[^0-9]/', '', $this->$campo);

        if (strlen($telefone) <= 11) {
            $formatted = '';

            if (strlen($telefone) > 2) {
                $formatted = '(' . substr($telefone, 0, 2) . ')';

                if (strlen($telefone) > 7) {
                    // Celular com 9 dígitos
                    $formatted .= ' ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
                } elseif (strlen($telefone) > 6) {
                    // Telefone fixo
                    $formatted .= ' ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
                } else {
                    $formatted .= ' ' . substr($telefone, 2);
                }
            } else {
                $formatted = $telefone;
            }

            $this->$campo = $formatted;
        }
    }

    // Formatar e validar telefone
    public function formatarEValidarTelefone($index)
    {
        // Remove caracteres não numéricos
        $telefone = preg_replace('/[^0-9]/', '', $this->responsaveis[$index]['numero']);

        // Formata o telefone
        if (strlen($telefone) <= 11) {
            $formatted = '';

            if (strlen($telefone) > 2) {
                $formatted = '(' . substr($telefone, 0, 2) . ')';

                if (strlen($telefone) > 7) {
                    // Formato com 9 dígitos: (XX) XXXXX-XXXX
                    $formatted .= ' ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
                } elseif (strlen($telefone) > 6) {
                    // Formato com 8 dígitos: (XX) XXXX-XXXX
                    $formatted .= ' ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
                } else {
                    $formatted .= ' ' . substr($telefone, 2);
                }
            } else {
                $formatted = $telefone;
            }

            $this->responsaveis[$index]['numero'] = $formatted;
        }

        // Valida o telefone
        if (strlen($telefone) < 10) {
            if (strlen($telefone) > 0) {
                $this->addError('responsaveis.' . $index . '.numero', 'Telefone deve ter pelo menos 10 dígitos.');
            }
        } else {
            $this->resetErrorBag('responsaveis.' . $index . '.numero');
        }
    }

    // Atualizar telefones
    public function updated($value, $key)
    {
        if (str_contains($key, 'responsaveis')) {
            // Extrair o índice do array de telefones
            preg_match('/responsaveis\.(\d+)\.numero/', $key, $matches);

            if (isset($matches[1])) {
                $index = $matches[1];
                $this->formatarEValidarTelefone($index);
            }
        }
    }

    // Adicionar responsável
    public function adicionarResponsavel()
    {
        $this->responsaveis[] = [
            'nome_pessoa' => $this->nome_pessoa,
            'numero' => $this->numero,
        ];

        // Limpar campos
        $this->nome_pessoa = '';
        $this->numero = '';

        $this->showNotification('success', 'Responsável adicionado com sucesso!');
    }

    // Remover responsável
    public function removerResponsavel($index)
    {
        if (isset($this->responsaveis[$index])) {
            unset($this->responsaveis[$index]);
            $this->responsaveis = array_values($this->responsaveis); // Reindexar array

            $this->showNotification('success', 'Responsável removido com sucesso!');
        }
    }

    public function showNotification($type, $message) {
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
    }


    // Submeter formulário
    public function submeterInscricao()
    {
        try {
            $this->validate();

            // Preparar dados para salvar
            $dados = [
                // Dados pessoais
                'nome' => $this->nome,
                'cpf' => $this->cpf,
                'rg' => $this->rg,
                'data_nascimento' => $this->data_nascimento,
                'email' => $this->email,
                'telefone' => $this->telefone,
                'genero' => $this->genero,
                'estado_civil' => $this->estado_civil,
                'nacionalidade' => $this->nacionalidade,
                'profissao' => $this->profissao,
                'religiao' => $this->religiao,
                'sacramento' => $this->sacramento,
                'comunidade' => $this->comunidade,

                // Responsáveis adicionais
                'responsaveis' => $this->responsaveis,

                // Endereço
                'cep' => $this->cep,
                'logradouro' => $this->logradouro,
                'numero' => $this->numero,
                'complemento' => $this->complemento,
                'bairro' => $this->bairro,
                'cidade' => $this->cidade,
                'estado' => $this->estado,
                'pais' => $this->pais,

                // Saúde
                'is_problema_saude' => $this->is_problema_saude,
                'descricao' => $this->descricao,

                // Motivo
                'motivo' => $this->motivo,

                // Termos
                'aceita_termos' => $this->aceita_termos,
                'aceita_imagem' => $this->aceita_imagem,
            ];

            $existingPessoa = Pessoa::where('cpf', $dados['cpf'])->orWhere('email', $dados['email'])->first();

            if ($existingPessoa) {

                $this->update($dados, $existingPessoa);

            } else {
                // Salvar dados no banco
                $this->save($dados);
            }

            $this->showNotification('success', 'Inscrição realizada com sucesso!');

            // Opcionalmente, redirecionar ou limpar formulário
            $this->reset();
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->showNotification('error', 'Erro ao processar inscrição: ' . $e->getMessage());
            throw $e;
        }
    }

    public function save($dados)
    {
        $this->validate();

        // Save person
        $pessoa = Pessoa::create([
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'],
            'data_nascimento' => $dados['data_nascimento'] ?: null,
            'email' => $dados['email'] ?: null,
            'tipo_pessoa_id' => 3,
            'is_problema_saude' => $dados['is_problema_saude'] ?? false,
            'descricao' => $dados['descricao'] ?: null,
            'ja_trabalhou' => $dados['ja_trabalhou'] ?? false,
            'genero' => $dados['genero'] ?: null,
            'estado_civil' => $dados['estado_civil'] ?: null,
            'motivo' => $dados['motivo'],
            'religiao' => $dados['religiao'],
            'sacramento' => $dados['sacramento'],
            'comunidade' => $dados['comunidade'],
        ]);

        // Save address if provided
        if (!empty($dados['logradouro']) || !empty($dados['cep'])) {
            $endereco = Endereco::create([
                'logradouro' => $dados['logradouro'] ?: '',
                'numero' => $dados['numero'] ?: '',
                'complemento' => $dados['complemento'] ?: '',
                'bairro' => $dados['bairro'] ?: '',
                'cidade' => $dados['cidade'] ?: '',
                'estado' => $dados['estado'] ?: 'SC',
                'cep' => $dados['cep'] ?: '',
                'pais' => $dados['pais'] ?: 'Brasil',
            ]);

            // Relate person and address
            DB::table('enderecos_pessoas')->insert([
                'pessoa_id' => $pessoa->id,
                'endereco_id' => $endereco->id,
            ]);
        }

        Telefone::create([
            'numero' => $dados['telefone'],
            'tipo' => 'celular',
            'nome_pessoa' => $dados['nome'],
            'pessoa_id' => $pessoa->id,
            'is_principal' => true,
        ]);

        // Save phone numbers if provided
        if (!empty($dados['responsaveis']) && is_array($dados['responsaveis'])) {
            foreach ($dados['responsaveis'] as $telefone) {
                if (!empty($telefone['numero'])) {
                    Telefone::create([
                        'numero' => $telefone['numero'],
                        'tipo' => 'celular',
                        'nome_pessoa' => $telefone['nome_pessoa'],
                        'pessoa_id' => $pessoa->id,
                        'is_principal' => false,
                    ]);
                }
            }
        }
    }

    public function update($dados, $pessoa)
    {
        // Atualizar os dados da pessoa
        $pessoa->update([
            'nome' => $dados['nome'],
            'cpf' => $dados['cpf'],
            'data_nascimento' => $dados['data_nascimento'] ?: null,
            'email' => $dados['email'] ?: null,
            'tipo_pessoa_id' => 3,
            'is_problema_saude' => $dados['is_problema_saude'] ?? false,
            'descricao' => $dados['descricao'] ?: null,
            'genero' => $dados['genero'] ?: null,
            'estado_civil' => $dados['estado_civil'] ?: null,
            'motivo' => $dados['motivo'] ?: null,
            'religiao' => $dados['religiao'] ?: null,
            'sacramento' => $dados['sacramento'] ?: null,
            'comunidade' => $dados['comunidade'] ?: null,
        ]);

        // Atualizar ou criar endereço
        if (!empty($dados['logradouro']) || !empty($dados['cep'])) {
            $endereco = DB::table('pessoas')
                    ->join('enderecos_pessoas', 'enderecos_pessoas.pessoa_id', '=', 'pessoas.id')
                    ->join('enderecos', 'enderecos.id', '=', 'enderecos_pessoas.endereco_id')
                    ->where('pessoas.id', $pessoa->id)
                    ->select('enderecos.*')
                    ->limit(1)
                    ->first();

            $endereco = Endereco::find($endereco->id);
            if ($endereco) {
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
            } else {
                $novoEndereco = Endereco::create([
                    'logradouro' => $dados['logradouro'] ?: '',
                    'numero' => $dados['numero'] ?: '',
                    'complemento' => $dados['complemento'] ?: '',
                    'bairro' => $dados['bairro'] ?: '',
                    'cidade' => $dados['cidade'] ?: '',
                    'estado' => $dados['estado'] ?: 'SC',
                    'cep' => $dados['cep'] ?: '',
                    'pais' => $dados['pais'] ?: 'Brasil',
                ]);

                // Relacionar pessoa e novo endereço
                DB::table('enderecos_pessoas')->insert([
                    'pessoa_id' => $pessoa->id,
                    'endereco_id' => $novoEndereco->id,
                ]);
            }
        }

        // Buscar telefone principal existente
        $existingPhoneIds = Telefone::where('pessoa_id', $pessoa->id)
                ->pluck('id')
                ->toArray();

        $updatedPhoneIds = [];

        $telefonePrincipal = Telefone::where('pessoa_id', $pessoa->id)
            ->where('is_principal', true)
            ->first();

        if ($telefonePrincipal) {
            $telefonePrincipal->update([
                'numero' => $dados['telefone'],
                'tipo' => 'celular',
                'nome_pessoa' => $dados['nome'],
                'is_principal' => true,
            ]);
            $updatedPhoneIds[] = $telefonePrincipal->id;
        } else {
            $phone =  Telefone::create([
                'numero' => $dados['telefone'],
                'tipo' => 'celular',
                'nome_pessoa' => $dados['nome'],
                'pessoa_id' => $pessoa->id,
                'is_principal' => true,
            ]);
            // Note: No need to add to $updatedPhoneIds since it's a new entry
            $updatedPhoneIds[] = $phone->id;

        }

        if (!empty($dados['responsaveis']) && is_array($dados['responsaveis'])) {

            foreach ($dados['responsaveis'] as $telefone) {
                if (!empty($telefone['numero'])) {
                    if (isset($telefone['id']) && in_array($telefone['id'], $existingPhoneIds)) {
                        Telefone::where('id', $telefone['id'])->update([
                            'numero' => $telefone['numero'],
                            'tipo' => 'celular',
                            'nome_pessoa' => $telefone['nome_pessoa'],
                            'is_principal' => false,
                        ]);
                        $updatedPhoneIds[] = $telefone['id'];
                    } else {
                        $novoTelefone = Telefone::create([
                            'numero' => $telefone['numero'],
                            'tipo' => 'celular',
                            'nome_pessoa' => $telefone['nome_pessoa'],
                            'pessoa_id' => $pessoa->id,
                            'is_principal' => false,
                        ]);
                        $updatedPhoneIds[] = $novoTelefone->id;
                    }
                }
            }

            // Excluir telefones que não estão mais na lista
            $phonesToDelete = array_diff($existingPhoneIds, $updatedPhoneIds);
            if (!empty($phonesToDelete)) {
                Telefone::whereIn('id', $phonesToDelete)->delete();
            }
        }
    }

    // Método para limpar formulário
    public function limparFormulario()
    {
        $this->reset();
        $this->mount(); // Recarregar valores padrão

        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Formulário limpo com sucesso!',
        ]);
    }

    // Validar CPF (método auxiliar)
    private function validarCpf($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Calcular primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        // Calcular segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    }
}; ?>

<div class="max-w-4xl mx-auto bg-white dark:bg-gray-800 rounded-lg shadow-lg p-8" style="min-width: 100%;">
    <livewire:components.notification :notification="$notification" />

    <div class="text-center mb-6">
        <a href="" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
            <span class="flex h-9 w-9 mb-1 items-center justify-center rounded-md">
                <x-app-logo-icon class="size-9 fill-current text-black dark:text-white" />
            </span>
            <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
        </a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Ficha de Inscrição</h1>
        <p class="text-gray-600 dark:text-gray-400 mt-2">Preencha todos os campos obrigatórios para completar sua
            inscrição</p>
    </div>

    <form wire:submit="submeterInscricao">
        <div class="space-y-8">

            <!-- DADOS PESSOAIS -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Dados Pessoais</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <flux:input wire:model="nome" :label="__('Nome Completo')" type="text"  />
                    </div>

                    <div>
                        <flux:input wire:model="data_nascimento" :label="__('Data de Nascimento')" type="date"
                             />
                    </div>

                    <div>
                        <flux:input wire:model="cpf" :label="__('CPF')" type="text" placeholder="000.000.000-00"
                             wire:change="formatarCpf" />
                    </div>

                    <div>
                        <flux:select wire:model="genero" :label="__('Gênero')" >
                            @foreach ($generos as $item)
                                <flux:select.option value="{{ $item }}">{{ $item }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:select wire:model="estado_civil" :label="__('Estado Civil')" >
                            @foreach ($estados_civis as $item)
                                <flux:select.option value="{{ $item }}">{{ $item }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>

                    <div>
                        <flux:input wire:model="religiao" :label="__('Religião')" type="text"  />
                    </div>

                    <div>
                        <flux:input wire:model="sacramento" :label="__('Sacramentos')" type="text"  />
                    </div>
                    <div>
                        <flux:input wire:model="comunidade" :label="__('Comunidade que frequênta')" type="text"
                             />
                    </div>
                </div>
            </div>


            <!-- CONTATO -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Contato</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <flux:input wire:model="email" :label="__('E-mail')" type="email"  />
                    </div>

                    <div>
                        <flux:input wire:model="telefone" :label="__('Telefone')" type="text"
                            placeholder="(00) 00000-0000"  wire:change="formatarTelefone" />
                    </div>
                </div>
            </div>

            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Responsáveis</h2>

                @foreach ($responsaveis as $index => $item)
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 mb-5">
                        <div>
                            <flux:input wire:model="responsaveis.{{ $index }}.nome_pessoa"
                                :label="__('Nome')" type="text"  />
                        </div>

                        <div>
                            <flux:input wire:model="responsaveis.{{ $index }}.numero" :label="__('Telefone')"
                                type="text" placeholder="(00) 00000-0000" wire:change="formatarEValidarTelefone({{ $index }})"  />
                        </div>

                        <div class="flex items-center">
                            <flux:button type="button" variant="danger"
                                wire:click="removerResponsavel({{ $index }})">
                                Remover
                            </flux:button>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-end mb-3 mt-3">
                    <flux:button type="button" wire:click="adicionarResponsavel">
                        Adicionar responsável
                    </flux:button>
                </div>
            </div>



            <!-- ENDEREÇO -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Endereço</h2>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                    <div class="md:col-span-1">
                        <div class="flex flex-col md:flex-row md:space-x-2 space-y-2 md:space-y-0">
                            <div class="flex-grow">
                                <flux:input wire:model="cep" :label="__('CEP')" type="text"
                                    placeholder="00000-000"  />
                            </div>
                            <div class="flex items-end">
                                <flux:button type="button" wire:click="buscarCep" size="sm">
                                    Buscar
                                </flux:button>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <flux:input wire:model="logradouro" :label="__('Logradouro')" type="text"  />
                    </div>

                    <div class="md:col-span-1">
                        <flux:input wire:model="numero" :label="__('Número')" type="text"  />
                    </div>

                    <div class="md:col-span-1">
                        <flux:input wire:model="complemento" :label="__('Complemento')" type="text" />
                    </div>

                    <div class="md:col-span-1">
                        <flux:input wire:model="bairro" :label="__('Bairro')" type="text"  />
                    </div>

                    <div class="md:col-span-1">
                        <flux:input wire:model="cidade" :label="__('Cidade')" type="text"  />
                    </div>

                    <div class="md:col-span-1">
                        <flux:select wire:model="estado" :label="__('Estado')" >
                            @foreach ($estados as $uf)
                                <flux:select.option value="{{ $uf }}">{{ $uf }}
                                </flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
            </div>


            <!-- INFORMAÇÕES DE SAÚDE E EMERGÊNCIA -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Informações de Saúde e
                    Emergência</h2>

                <div class="space-y-4">
                    <div class="flex items-center space-x-4">
                        <input wire:model="is_problema_saude" type="checkbox"
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Possui alguma deficiência ou necessidade especial?
                        </label>
                    </div>


                    <div class="grid grid-cols-1 gap-4 md:grid-cols-1">
                        <div>
                            <flux:textarea wire:model="descricao"
                                :label="__('Descreva a deficiência/necessidade especial')" rows="2" />
                        </div>

                    </div>

                </div>
            </div>

            <!-- OBSERVAÇÕES -->
            <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Motivo</h2>

                <flux:textarea wire:model="motivo" :label="__('Descreva o motivo pelo qual deseja fazer o efeta')"
                    rows="4" placeholder="" />
            </div>

            <!-- TERMOS E CONDIÇÕES -->
            <div class="pb-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Termos e Condições</h2>

                <div class="space-y-4">
                    <div class="flex items-start space-x-4">
                        <input wire:model="aceita_termos" type="checkbox" required
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded mt-1">
                        <label class="text-sm text-gray-700 dark:text-gray-300">
                            <span class="font-medium">*</span> Li e aceito os
                            <a href="#" class="text-indigo-600 hover:text-indigo-500">termos de uso</a>
                            e <a href="#" class="text-indigo-600 hover:text-indigo-500">política de
                                privacidade</a>
                        </label>
                    </div>

                    <div class="flex items-start space-x-4">
                        <input wire:model="aceita_imagem" type="checkbox"
                            class="h-4 w-4 text-indigo-600 border-gray-300 rounded mt-1">
                        <label class="text-sm text-gray-700 dark:text-gray-300">
                            Autorizo o uso de minha imagem em materiais de divulgação da instituição
                        </label>
                    </div>

                </div>
            </div>

            <!-- BOTÕES -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                <flux:button type="button" variant="ghost" wire:click="$refresh">
                    Cancelar
                </flux:button>
                <flux:button type="submit" variant="primary">
                    Enviar Inscrição
                </flux:button>
            </div>

        </div>
    </form>
</div>
