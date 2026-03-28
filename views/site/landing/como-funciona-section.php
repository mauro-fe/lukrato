<!-- Seção Como Funciona — 3 passos simples -->
<section id="como-funciona" class="relative py-16 md:py-24 bg-white" aria-labelledby="como-funciona-titulo">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="lk-header-card max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 rounded-full mb-4">
                <i data-lucide="list-checks" class="w-5 h-5 text-primary"></i>
                <span class="text-sm font-semibold text-primary">Passo a passo</span>
            </div>
            <h2 id="como-funciona-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                Tão simples que você começa em
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    3 passos
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                Sem burocracia, sem planilha, sem complicação. Em 1 minuto você já está no controle.
            </p>
        </header>

        <!-- Steps — 3 passos -->
        <div class="max-w-4xl mx-auto mb-12">
            <div class="grid sm:grid-cols-3 gap-8 lg:gap-10 relative">

                <!-- Linha conectora (desktop) -->
                <div class="hidden sm:block absolute top-12 left-[16.5%] right-[16.5%] h-0.5 bg-gradient-to-r from-orange-200 via-primary/30 to-orange-200"
                    aria-hidden="true"></div>

                <!-- Passo 1 -->
                <div class="relative text-center group" data-aos="fade-up" data-aos-delay="0">
                    <div
                        class="relative z-10 w-24 h-24 bg-white border-2 border-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-md group-hover:shadow-lg group-hover:border-primary/30 transition-all duration-300">
                        <div
                            class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-primary to-orange-600 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-md">
                            1
                        </div>
                        <i data-lucide="user-plus" class="w-10 h-10 text-primary"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 mb-2">Crie sua conta em 1 minuto</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        E-mail ou Google. Sem burocracia, sem cartão.
                    </p>
                </div>

                <!-- Passo 2 -->
                <div class="relative text-center group" data-aos="fade-up" data-aos-delay="100">
                    <div
                        class="relative z-10 w-24 h-24 bg-white border-2 border-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-md group-hover:shadow-lg group-hover:border-primary/30 transition-all duration-300">
                        <div
                            class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-primary to-orange-600 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-md">
                            2
                        </div>
                        <i data-lucide="sparkles" class="w-10 h-10 text-primary"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 mb-2">Registre gastos (ou deixe a IA fazer)</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Pelo app, WhatsApp ou Telegram. A IA categoriza tudo.
                    </p>
                </div>

                <!-- Passo 3 -->
                <div class="relative text-center group" data-aos="fade-up" data-aos-delay="200">
                    <div
                        class="relative z-10 w-24 h-24 bg-white border-2 border-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-md group-hover:shadow-lg group-hover:border-primary/30 transition-all duration-300">
                        <div
                            class="absolute -top-3 -right-3 w-8 h-8 bg-gradient-to-br from-primary to-orange-600 rounded-lg flex items-center justify-center text-white text-sm font-bold shadow-md">
                            3
                        </div>
                        <i data-lucide="bar-chart-3" class="w-10 h-10 text-primary"></i>
                    </div>
                    <h3 class="font-bold text-lg text-gray-900 mb-2">Veja seu dinheiro com clareza</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">
                        Dashboard, gráficos e metas. Tudo em tempo real.
                    </p>
                </div>

            </div>
        </div>

        <!-- CTA -->
        <div class="text-center" data-aos="fade-up" data-aos-delay="300">
            <div class="flex flex-col items-center">
                <a href="<?= BASE_URL ?>login"
                    class="group inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:scale-[1.03] active:scale-[0.98] transition-all duration-300"
                    title="Criar conta gratuita no Lukrato" aria-label="Criar minha conta grátis">
                    Criar minha conta grátis
                    <i data-lucide="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
                </a>
                <span class="text-xs text-gray-400 mt-2 font-medium">Sem cartão de crédito</span>
            </div>
        </div>

    </div>
</section>