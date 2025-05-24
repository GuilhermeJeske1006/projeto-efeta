<?php
use function Livewire\Volt\{state, rules, mount, computed, on};

// Recebe a propriedade notification do componente pai
state([
    'notification' => [
        'show' => false,
        'type' => '',
        'message' => ''
    ],
]);

// Mount para receber os dados do componente pai
mount(function ($notification = null) {
    if ($notification) {
        $this->notification = $notification;
    }
});

// Escuta mudanças na propriedade notification do componente pai
on([
    'notification-updated' => function ($notification) {
        $this->notification = $notification;
    },
]);

// Escuta o evento notify dispatched do componente pai
on([
    'notify' => function ($data) {
        $this->notification = [
            'show' => true,
            'type' => $data['type'],
            'message' => $data['message']
        ];
    },
]);

$closeNotification = function () {
    $this->notification['show'] = false;
    // Notifica o componente pai que a notificação foi fechada
    $this->dispatch('notification-closed');
};

// Auto-close notification after 5 seconds for success messages
computed(function () {
    if ($this->notification['show'] && $this->notification['type'] === 'success') {
        $this->js('setTimeout(() => { $wire.closeNotification() }, 5000)');
    }
});

?>

<div>
    @if ($notification['show'])
    <div class="fixed top-4 right-4 z-50 max-w-sm w-full">
        <div class="mb-4 p-4 rounded-md shadow-lg transition-all duration-300 transform translate-x-0 opacity-100 {{ $notification['type'] === 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700' }}" 
             role="alert"
             x-data="{ show: true }"
             x-show="show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-full"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-300"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-full">
            
            <div class="flex justify-between items-start">
                <div class="flex items-start">
                    @if ($notification['type'] === 'success')
                        <!-- Ícone de sucesso -->
                        <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                    @else
                        <!-- Ícone de erro -->
                        <svg class="w-5 h-5 mr-3 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    @endif
                    <div class="flex-1">
                        <strong class="font-medium block">
                            {{ $notification['type'] === 'success' ? 'Sucesso!' : 'Erro!' }}
                        </strong>
                        <p class="mt-1 text-sm">{{ $notification['message'] }}</p>
                    </div>
                </div>
                <button wire:click="closeNotification" 
                        class="ml-4 text-current opacity-50 hover:opacity-75 transition-opacity flex-shrink-0"
                        aria-label="Fechar notificação">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>