<?php
use App\Models\Pessoa;
use App\Models\Endereco;
use App\Models\Telefone;
use App\Models\TipoPessoa;
use Illuminate\Support\Facades\DB;
use function Livewire\Volt\{state, mount, computed};

// Define state properties
state([
    'dados' => [],
    'pessoa' => null,
    'endereco' => null,
    'telefones' => [],
    'tipoPessoa' => null,
    'retiros' => [],
]);

// Mount component
mount(function ($id = null) {
    if (!$id) {
        abort(404, 'ID da pessoa não informado');
    }

    $this->pessoaId = $id;
    $this->carregarDadosPessoa();
});

$getRetiros = function () {
    return  DB::table('pessoa_retiros')
            ->where('pessoa_retiros.pessoa_id', $this->pessoaId)
            ->join('retiros', 'pessoa_retiros.retiro_id', '=', 'retiros.id')
            ->join('equipes', 'pessoa_retiros.equipe_id', '=', 'equipes.id')
            ->join('status_chamados', 'pessoa_retiros.status_id', '=', 'status_chamados.id')
            ->select('retiros.nome', 'equipes.nome as equipe_nome', 'status_chamados.nome as status_nome')
            ->paginate();
};

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
        $this->dados['id'] = $this->pessoa->id;
        $this->dados['nome'] = $this->pessoa->nome;
        $this->dados['cpf'] = $this->pessoa->cpf;
        $this->dados['data_nascimento'] = $this->pessoa->data_nascimento;
        $this->dados['created_at'] = $this->pessoa->created_at;
        $this->dados['email'] = $this->pessoa->email ?? '';
        $this->dados['tipo_pessoa_id'] = $this->pessoa->tipo_pessoa_id;
        $this->dados['is_problema_saude'] = $this->pessoa->is_problema_saude;
        $this->dados['descricao'] = $this->pessoa->descricao ?? '';
        $this->dados['ja_trabalhou'] = $this->pessoa->ja_trabalhou;
        $this->dados['genero'] = $this->pessoa->genero ?? '';
        $this->dados['estado_civil'] = $this->pessoa->estado_civil ?? '';
        $this->dados['motivo'] = $this->pessoa->motivo ?? '';
        $this->dados['religiao'] = $this->pessoa->religiao ?? 'Católica';
        $this->dados['sacramento'] = $this->pessoa->sacramento ?? '';
        $this->dados['comunidade'] = $this->pessoa->comunidade ?? '';
        $this->dados['gostaria_de_trabalhar'] = $this->pessoa->gostaria_de_trabalhar ?? '';
        $this->dados['trabalha_onde_comunidade'] = $this->pessoa->trabalha_onde_comunidade ?? '';
        $this->dados['ja_fez_retiro'] = $this->pessoa->ja_fez_retiro;

        // Carregar dados do endereço (primeiro endereço se existir)
        $this->dados['logradouro'] = $this->pessoa->logradouro ?? '';
        $this->dados['numero'] = $this->pessoa->numero ?? '';
        $this->dados['complemento'] = $this->pessoa->complemento ?? '';
        $this->dados['bairro'] = $this->pessoa->bairro ?? '';
        $this->dados['cidade'] = $this->pessoa->cidade ?? '';
        $this->dados['estado'] = $this->pessoa->estado ?? 'SC';
        $this->dados['cep'] = $this->pessoa->cep ?? '';
        $this->dados['pais'] = $this->pessoa->pais ?? 'Brasil';

        // Carregar telefones
        $this->dados['telefones'] = array_map(function ($telefone) {
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

$voltar = function () {
    if($this->dados['tipo_pessoa_id'] == 1) {
        return redirect()->route('servos.index');
    } else {
        return redirect()->route('retirantes.index');
    }
};

// Computed para formatar data
$dataFormatada = computed(function () {
    if ($this->dados['data_nascimento']) {
        return \Carbon\Carbon::parse($this->dados['data_nascimento'])->format('d/m/Y');
    }
    return '';
});

$dataCriacaoFormatada = computed(function () {
    if ($this->dados['created_at']) {
        return \Carbon\Carbon::parse($this->dados['created_at'])->format('d/m/Y');
    }
    return '';
});

// Computed para calcular idade
$idade = computed(function () {
    if ($this->dados['data_nascimento']) {
        return \Carbon\Carbon::parse($this->dados['data_nascimento'])->age;
    }
    return '';
});

?>

<div class="min-h-screen">
    <div class="max-w-7xl mx-auto p-0">


        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-2xl font-bold">Detalhes da pessoa</h1>
            </div>
            <flux:button type="button" variant="outline" wire:click="voltar" class="flex bg-white/10 border-white/20 text-white hover:bg-white/20 backdrop-blur-sm">
            Voltar a lista
        </flux:button>
        </div>

        <!-- Main Content Grid -->
        <div class="gap-8">
            <!-- Left Column - Personal Data -->
            <div class="space-y-8">
                
                <!-- Personal Information Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                        <h2 class="text-xl font-bold  text-gray-900 dark:text-gray-100 flex items-center">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-900 dark:text-gray-100 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            Informações Pessoais
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data do cadastro</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $this->dataCriacaoFormatada ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Nome Completo</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['nome'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">CPF</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100 font-mono">{{ $dados['cpf'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Data de Nascimento</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $this->dataFormatada }} 
                                    @if($this->idade)
                                        <span class="text-sm text-gray-500 dark:text-gray-400 ml-2 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded-full">{{ $this->idade }} anos</span>
                                    @endif
                                </p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">E-mail</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100 break-all">{{ $dados['email'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Gênero</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['genero'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado Civil</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['estado_civil'] ?? '-' }}</p>
                            </div>

                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Religião</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['religiao'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sacramentos</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['sacramento'] ?? '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Comunidade</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['comunidade'] ?? '-' }}</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">O que faz na comunidade</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['trabalha_onde_comunidade'] ?? '-' }}</p>
                            </div>
                         
           
                            @if ($dados['ja_fez_retiro'] != '')
                                <div class="space-y-1">
                                    <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Já  fez algum retiro?</label>
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['ja_fez_retiro'] == 1 ? 'Sim' : 'Não' }}</p>
                                </div>
                            @endif
                           
                        </div>
                    </div>
                </div>

                 @if ($dados['motivo'] && auth()->user()->role_id != 3)
                 <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-900 dark:text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                                </svg>
                            </div>
                            Motivo
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">O que fez você buscar a Efeta?</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['motivo'] ?? '-' }}</p>
                            </div>                           
                        </div>
                    </div>
                </div>
                 @endif


                @if ($dados['is_problema_saude'])
                    
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-900 dark:text-gray-100 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-7 4a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2.586a1 1 0 01-.707-.293l-1.414-1.414A1 1 0 0012 3H8a2 2 0 00-2 2v14z"/>
                                </svg>
                            </div>
                            Informações de Saúde e Emergência
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Descrição da deficiência/necessidade especial</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['descricao'] ?? '-' }}</p>
                            </div>
                            

                        </div>
                    </div>
                </div>
                @endif

                <!-- Address Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-900 dark:text-gray-100 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            Endereço
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2 space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Logradouro</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                    {{ $dados['logradouro'] ?? '-' }}, {{ $dados['numero'] ?? '-' }}
                                    @if($dados['complemento'])
                                        <span class="text-gray-600 dark:text-gray-400"> - {{ $dados['complemento'] }}</span>
                                    @endif
                                </p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Bairro</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['bairro'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">CEP</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100 font-mono">{{ $dados['cep'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Cidade</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['cidade'] ?? '-' }}</p>
                            </div>
                            
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</label>
                                <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['estado'] ?? '-' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Phone Numbers Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-900 dark:text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.986.836l1.498 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-1C7.82 18 2 12.18 2 5V3z"/>
                                </svg>
                            </div>
                            Telefones
                        </h2>
                    </div>
                    
                    <div class="p-6">
                        @if(isset($dados['telefones']) && count($dados['telefones']) > 0)
                            <div class="space-y-4">
                                @foreach($dados['telefones'] as $telefone)
                                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3">
                                                <div class="w-10 h-10 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900  rounded-lg flex items-center justify-center">
                                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l1.498 4.493a1 1 0 01-.502 1.21l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.949V17a1 1 0 01-1 1h-1C7.82 18 2 12.18 2 5V3z"/>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <span class="text-lg font-semibold text-gray-900 dark:text-gray-100 font-mono">
                                                            {{ $telefone['numero'] ?? '-' }}
                                                        </span>
                                                        @if($telefone['is_principal'] ?? false)
                                                            <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100 rounded-full">
                                                                Principal
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400">
                                                        <span class="font-medium">{{ $telefone['tipo'] ?? '-' }}</span>
                                                        @if($telefone['nome_pessoa'])
                                                            <span> • {{ $telefone['nome_pessoa'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">Nenhum telefone cadastrado</p>
                            </div>
                        @endif
                    </div>
                </div>

                @if ($dados['gostaria_de_trabalhar'] && auth()->user()->role_id != 3)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                   <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                       <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                           <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                               <svg class="w-5 h-5 text-gray-900 dark:text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                   <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16h6a2 2 0 002-2V7a2 2 0 00-2-2H9a2 2 0 00-2 2v7a2 2 0 002 2z"/>
                               </svg>
                           </div>
                           Onde você gostaria de servir?
                       </h2>
                   </div>
                   
                   <div class="p-6">
                       <div class="grid grid-cols-1 md:grid-cols-2 gap-6">                            
                           <div class="space-y-1">
                               <p class="text-lg font-medium text-gray-900 dark:text-gray-100">{{ $dados['gostaria_de_trabalhar'] ?? '-' }}</p>
                           </div>
                       </div>
                   </div>
               </div>
                @endif

                <!-- Retiros History Card -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div class="bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 rounded-lg shadow-sm px-6 py-4">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 flex items-center">
                            <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-5 h-5 text-gray-900 dark:text-gray-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            Histórico de retiros
                        </h2>
                    </div>
                    
                    <div class="overflow-hidden">
                        @php $retiros = $this->getRetiros(); @endphp
                        
                        @if(count($retiros) > 0)
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Retiro</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Função</th>
                                            <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($retiros as $retiro)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                                <td class="px-6 py-4">
                                                    <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $retiro->nome }}</div>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                                                        {{ $retiro->equipe_nome }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                                        {{ $retiro->status_nome }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-12 px-6">
                                <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <p class="text-gray-500 dark:text-gray-400">Nenhum registro encontrado</p>
                            </div>
                        @endif
                        
                        <!-- Pagination -->
                        @if(count($retiros) > 0)
                            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
                                {{ $retiros->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>