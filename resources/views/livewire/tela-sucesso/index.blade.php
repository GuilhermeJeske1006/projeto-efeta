<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth-ficha')] class extends Component {
 
}; ?>

<div class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl overflow-hidden">
        
        <!-- Área de ícone de sucesso -->
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 p-8 text-center">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-4">
                <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">
                {{ __('Inscrição Realizada!') }}
            </h1>

        </div>

        <!-- Conteúdo principal -->
        <div class="p-8 md:p-12">
            <div class="text-center mb-8">
                <p class="text-lg text-gray-700 dark:text-gray-300 leading-relaxed">
                    {{ __('Obrigado por se inscrever! Recebemos suas informações com sucesso.') }}
                </p>
            </div>

            <!-- Cards informativos -->
            <div class="space-y-4 mb-8">


                <div class="flex items-start gap-4 p-4 bg-purple-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                            {{ __('Próximos passos') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Aguarde que na hora certa entraremos em contato com você.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-4 p-4 bg-amber-50 dark:bg-zinc-700 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                            {{ __('Dúvidas?') }}
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ __('Entre em contato conosco se tiver alguma pergunta.') }}
                        </p>
                    </div>
                </div>
            </div>


        </div>

    </div>
</div>