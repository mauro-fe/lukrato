<!-- Sticky CTA Mobile — Fixo no bottom em dispositivos móveis -->
<div
    x-data="{ showSticky: false }"
    data-sticky-cta
    x-show="showSticky"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-y-full opacity-0"
    x-transition:enter-end="translate-y-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-y-0 opacity-100"
    x-transition:leave-end="translate-y-full opacity-0"
    class="fixed bottom-0 left-0 right-0 z-40 lg:hidden bg-white/95 backdrop-blur-sm border-t border-gray-200 shadow-[0_-4px_20px_rgba(0,0,0,0.08)] px-4 py-3"
    role="complementary"
    aria-label="Ação rápida: criar conta">
    <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
        <div class="flex-1">
            <a href="<?= BASE_URL ?>login"
                class="flex items-center justify-center w-full px-6 py-3 text-sm font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl active:scale-[0.98] transition-all duration-300"
                title="Criar conta gratuita" aria-label="Criar conta grátis">
                Criar conta grátis
                <i data-lucide="arrow-right" class="ml-2 w-4 h-4" aria-hidden="true"></i>
            </a>
        </div>
    </div>
    <p class="text-center text-[11px] text-gray-400 mt-1">Sem cartão · 1 minuto</p>
</div>