<!-- Botão Voltar ao Topo -->
<button x-data="{ show: false }" @scroll.window="show = window.pageYOffset > 400" x-show="show"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75"
    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-75"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })" type="button"
    class="fixed bottom-8 right-8 z-40 w-14 h-14 bg-gradient-to-r from-primary to-orange-600 text-white rounded-full shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center"
    aria-label="Voltar ao topo" title="Voltar ao topo" style="display: none;">
    <i data-lucide="arrow-up" class="text-xl"></i>
</button>
