<!-- Seção de Inteligência Artificial — Chat simulado + Cards -->
<section id="inteligencia-artificial" class="relative py-16 md:py-24 overflow-hidden bg-gradient-to-b from-white to-gray-50"
    aria-labelledby="ia-titulo">

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">

        <!-- Header -->
        <header class="lk-header-card max-w-3xl mx-auto text-center mb-14" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-full mb-4">
                <i data-lucide="sparkles" class="w-5 h-5 text-primary"></i>
                <span class="text-sm font-semibold text-primary">Inteligência Artificial</span>
            </div>
            <h2 id="ia-titulo"
                class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight mb-4">
                Controle suas finanças
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    conversando
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                Mande uma mensagem como "almoço 35" e a IA registra, categoriza e organiza tudo. Pelo chat, WhatsApp ou Telegram.
            </p>
        </header>

        <!-- Simulação de Chat -->
        <div class="max-w-lg mx-auto mb-14" data-aos="fade-up" data-aos-delay="100">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <!-- Header do chat -->
                <div class="bg-gradient-to-r from-primary to-orange-600 px-5 py-3 flex items-center gap-3">
                    <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center">
                        <i data-lucide="sparkles" class="w-4 h-4 text-white"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-white">Lukrato IA</p>
                        <p class="text-xs text-white/70">Online agora</p>
                    </div>
                </div>
                <!-- Mensagens -->
                <div class="p-5 space-y-4">
                    <!-- Mensagem do usuário -->
                    <div class="flex justify-end">
                        <div class="bg-orange-50 border border-orange-100 rounded-2xl rounded-br-md px-4 py-2.5 max-w-[80%]">
                            <p class="text-sm text-gray-800">gastei 45 reais no mercado</p>
                        </div>
                    </div>
                    <!-- Resposta da IA -->
                    <div class="flex justify-start">
                        <div class="bg-gray-50 border border-gray-100 rounded-2xl rounded-bl-md px-4 py-2.5 max-w-[85%]">
                            <p class="text-sm text-gray-800">
                                <span class="text-green-600 font-semibold">✅ Registrado!</span> R$ 45,00 em <strong>Alimentação</strong>. Você já gastou R$ 380 de R$ 500 do orçamento este mês.
                            </p>
                        </div>
                    </div>
                    <!-- Mensagem do usuário -->
                    <div class="flex justify-end">
                        <div class="bg-orange-50 border border-orange-100 rounded-2xl rounded-br-md px-4 py-2.5 max-w-[80%]">
                            <p class="text-sm text-gray-800">quanto posso gastar hoje?</p>
                        </div>
                    </div>
                    <!-- Resposta da IA -->
                    <div class="flex justify-start">
                        <div class="bg-gray-50 border border-gray-100 rounded-2xl rounded-bl-md px-4 py-2.5 max-w-[85%]">
                            <p class="text-sm text-gray-800">
                                <span class="font-semibold">📊 Saldo disponível:</span> R$ 1.230. Recomendo limitar a <strong>R$ 85</strong> hoje para fechar o mês no positivo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid de cards IA -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-2 gap-6 lg:gap-8 max-w-5xl mx-auto mb-12">

            <!-- Card 1 -->
            <article class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="0">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="bot" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Registre gastos sem abrir o app</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Mande "almoço 35" no WhatsApp ou Telegram e a IA cria o lançamento completo pra você. Sem abrir o app.
                </p>
            </article>

            <!-- Card 2 -->
            <article class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="100">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="wand-2" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Categorização automática inteligente</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Digitou a descrição? A IA sugere a categoria certa. Economize tempo e mantenha tudo organizado sem esforço.
                </p>
            </article>

            <!-- Card 3 -->
            <article class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="200">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="brain" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Receba alertas e insights personalizados</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    A IA analisa seus padrões, identifica gastos excessivos e dá dicas personalizadas para você economizar mais.
                </p>
            </article>

            <!-- Card 4 -->
            <article class="group bg-white rounded-2xl p-7 shadow-sm border border-gray-100 hover:shadow-lg hover:border-orange-100 transition-all duration-300"
                data-aos="fade-up" data-aos-delay="300">
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center text-white mb-5"
                    aria-hidden="true">
                    <i data-lucide="message-circle" class="w-6 h-6"></i>
                </div>
                <h3 class="font-bold text-lg text-gray-900 mb-2">Funciona no WhatsApp e Telegram</h3>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Vincule sua conta e gerencie suas finanças direto do app que você já usa todo dia.
                </p>
            </article>

        </div>

        <!-- Destaque / CTA -->
        <div class="max-w-4xl mx-auto" data-aos="fade-up">
            <div class="relative bg-gradient-to-r from-orange-500 to-orange-700 rounded-3xl p-8 md:p-12 text-white overflow-hidden">
                <div class="absolute inset-0 opacity-10">
                    <div class="absolute -top-10 -right-10 w-60 h-60 bg-white rounded-full"></div>
                    <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white rounded-full"></div>
                </div>
                <div class="relative z-10 flex flex-col md:flex-row items-center gap-6 md:gap-10">
                    <div class="flex-shrink-0">
                        <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur flex items-center justify-center">
                            <i data-lucide="sparkles" class="w-10 h-10 text-white"></i>
                        </div>
                    </div>
                    <div class="text-center md:text-left flex-1">
                        <h3 class="text-2xl md:text-3xl font-bold mb-2">IA disponível em todos os planos</h3>
                        <p class="text-orange-100 text-base md:text-lg leading-relaxed">
                            Experimente grátis com 5 mensagens por mês. No plano Pro, use até 100 mensagens — pelo chat na plataforma, WhatsApp ou Telegram.
                        </p>
                    </div>
                    <div class="flex-shrink-0 flex flex-col items-center">
                        <a href="<?= BASE_URL ?>login"
                            class="inline-flex items-center gap-2 px-8 py-4 bg-white text-orange-600 font-bold rounded-xl hover:bg-gray-50 transition-all shadow-lg hover:shadow-xl hover:scale-[1.02] active:scale-[0.98]">
                            <i data-lucide="zap" class="w-5 h-5"></i>
                            Quero testar a IA
                        </a>
                        <span class="text-xs text-white/60 mt-2">Disponível no plano gratuito</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>