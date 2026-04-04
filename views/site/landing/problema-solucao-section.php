<!-- Problema-Solução — Espelhamento de dor + Calculadora de Vazamento -->
<section class="py-16 lg:py-24 bg-gradient-to-b from-white to-gray-50" aria-label="Problemas financeiros e soluções">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="text-center mb-12" data-aos="fade-up">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 border border-orange-100 rounded-full mb-4">
                <i data-lucide="lightbulb" class="w-4 h-4 text-primary" aria-hidden="true"></i>
                <span class="text-sm font-medium text-gray-700">Diagnóstico</span>
            </div>
            <h2 class="text-3xl sm:text-4xl font-bold text-gray-900">
                Isso parece <span
                    class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">familiar</span>?
            </h2>
        </div>

        <!-- Grid Problema x Solução -->
        <div class="grid lg:grid-cols-2 gap-6 lg:gap-8 mb-12">

            <!-- Problema -->
            <div class="bg-green-100 border border-red-100 rounded-2xl p-6 lg:p-8" data-aos="fade-up"
                data-aos-delay="100">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-500" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Sem o Lukrato</h3>
                </div>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <i data-lucide="x" class="w-5 h-5 text-red-400 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Chega no fim do mês sem saber onde o dinheiro foi</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="x" class="w-5 h-5 text-red-400 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Paga juros por esquecer vencimentos</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="x" class="w-5 h-5 text-red-400 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Já tentou planilha mas nunca manteve</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="x" class="w-5 h-5 text-red-400 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Tem medo de olhar o extrato</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="x" class="w-5 h-5 text-red-400 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Sente ansiedade quando pensa em dinheiro</span>
                    </li>
                </ul>
            </div>

            <!-- Solução -->
            <div class="bg-green-100 border border-green-100 rounded-2xl p-6 lg:p-8" data-aos="fade-up"
                data-aos-delay="200">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                        <i data-lucide="check-circle-2" class="w-5 h-5 text-green-600" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Com o Lukrato</h3>
                </div>
                <ul class="space-y-4">
                    <li class="flex items-start gap-3">
                        <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Vê em segundos se está no positivo ou negativo</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Recebe lembretes antes dos vencimentos</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Registra gastos em 5 segundos (até por WhatsApp)</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Tem clareza total sobre cada centavo</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5 shrink-0" aria-hidden="true"></i>
                        <span class="text-gray-700">Sente controle e confiança sobre seu dinheiro</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Calculadora de Vazamento -->
        <div class="max-w-xl mx-auto bg-white rounded-2xl shadow-lg border border-gray-100 p-6 lg:p-8 mb-10" data-leak-calculator data-aos="fade-up" data-aos-delay="300">
            <div class="text-center mb-6">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-orange-50 rounded-xl mb-3">
                    <i data-lucide="calculator" class="w-6 h-6 text-primary" aria-hidden="true"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900">Calculadora de Vazamento</h3>
                <p class="text-sm text-gray-500 mt-1">Descubra quanto você pode estar perdendo por mês</p>
            </div>

            <div class="space-y-4">
                <div>
                    <label for="renda-mensal" class="block text-sm font-medium text-gray-700 mb-2">Quanto você ganha por
                        mês?</label>
                    <input type="text" id="renda-mensal" data-lk-vazamento-input placeholder="R$ 3.000,00"
                        class="w-full px-4 py-3 text-lg border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary transition-colors"
                        inputmode="numeric" autocomplete="off" />
                </div>

                <button type="button" data-lk-vazamento-submit
                    class="w-full px-6 py-3 text-base font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:scale-[1.02] active:scale-[0.98] transition-all duration-300">
                    Calcular meu vazamento
                </button>

                <!-- Resultado -->
                <div hidden data-lk-vazamento-result class="lk-vazamento-resultado text-center pt-4" aria-live="polite">
                    <p class="text-sm text-gray-500">Brasileiros perdem em média <strong>15% da renda</strong> com
                        gastos desnecessários.</p>
                    <p class="text-2xl font-bold text-red-500 mt-2">
                        Você pode estar perdendo até R$ <span data-lk-vazamento-amount></span>/mês
                    </p>
                    <p class="text-sm text-gray-400 mt-1">sem perceber para onde o dinheiro vai.</p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center" data-aos="fade-up" data-aos-delay="400">
            <div class="flex flex-col items-center">
                <a href="<?= BASE_URL ?>login"
                    class="group inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:scale-[1.03] active:scale-[0.98] transition-all duration-300"
                    title="Criar conta gratuita no Lukrato" aria-label="Quero sair do descontrole financeiro">
                    Quero sair do descontrole
                    <i data-lucide="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform"
                        aria-hidden="true"></i>
                </a>
                <span class="text-xs text-gray-400 mt-2 font-medium">Cadastro grátis em 1 minuto</span>
            </div>
        </div>
    </div>
</section>