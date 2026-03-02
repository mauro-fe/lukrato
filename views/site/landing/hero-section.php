<!-- Hero Section -->
<section
    class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-orange-50 via-orange-50/30 to-gray-50"
    aria-label="Seção principal - Controle financeiro pessoal" itemscope itemtype="https://schema.org/WebPageElement">
    <!-- Background Decorations -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div
            class="absolute top-20 left-10 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob">
        </div>
        <div
            class="absolute top-40 right-10 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-15 animate-blob animation-delay-2000">
        </div>
        <div
            class="absolute -bottom-8 left-1/2 w-72 h-72 bg-gray-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000">
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Conteúdo Principal -->
            <article class="text-center lg:text-left space-y-8" data-aos="fade-right">
                <!-- Badge -->
                <div
                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full shadow-md">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse" aria-hidden="true"></span>
                    <span class="text-sm font-medium text-gray-700">Mais de 1.000 usuários organizando suas
                        finanças</span>
                </div>

                <!-- Título Principal - H1 único e otimizado -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-gray-900 leading-tight">
                    Controle Financeiro Pessoal
                    <span
                        class="bg-gradient-to-r from-primary via-orange-500 to-orange-600 bg-clip-text text-transparent">
                        Simples e Inteligente
                    </span>
                </h1>

                <!-- Subtítulo otimizado para SEO -->
                <p class="text-xl sm:text-2xl text-gray-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Organize suas <strong>receitas, despesas e orçamento</strong> em um só lugar.
                    O melhor <strong>aplicativo gratuito</strong> para gerenciar seu dinheiro sem complicação.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
                    <a href="<?= BASE_URL ?>login"
                        class="group inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300"
                        title="Criar conta gratuita no Lukrato" aria-label="Começar a usar o Lukrato gratuitamente">
                        Começar grátis agora
                        <i data-lucide="arrow-right" class="ml-3 group-hover:translate-x-1 transition-transform"
                            aria-hidden="true"></i>
                    </a>

                    <a href="#funcionalidades"
                        class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-gray-700 bg-white border-2 border-gray-200 rounded-xl hover:border-primary hover:text-primary shadow-lg hover:shadow-xl transition-all duration-300"
                        title="Ver funcionalidades do app de controle financeiro"
                        aria-label="Conhecer as funcionalidades do Lukrato">
                        <i data-lucide="play" class="mr-3" aria-hidden="true"></i>
                        Ver como funciona
                    </a>
                </div>

                <!-- Social Proof -->
                <div class="flex flex-col sm:flex-row items-center gap-6 pt-8" itemscope
                    itemtype="https://schema.org/AggregateRating">
                    <div class="flex -space-x-3" aria-hidden="true">
                        <div
                            class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white font-bold shadow-lg">
                            J
                        </div>
                        <div
                            class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white font-bold shadow-lg">
                            M
                        </div>
                        <div
                            class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white font-bold shadow-lg">
                            A
                        </div>
                        <div
                            class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-warning to-yellow-600 flex items-center justify-center text-white font-bold shadow-lg">
                            +
                        </div>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="flex gap-1 mb-1 justify-center sm:justify-start"
                            aria-label="Avaliação 4.9 de 5 estrelas">
                            <i data-lucide="star" class="text-yellow-400" style="fill:#facc15;width:20px;height:20px;" aria-hidden="true"></i>
                            <i data-lucide="star" class="text-yellow-400" style="fill:#facc15;width:20px;height:20px;" aria-hidden="true"></i>
                            <i data-lucide="star" class="text-yellow-400" style="fill:#facc15;width:20px;height:20px;" aria-hidden="true"></i>
                            <i data-lucide="star" class="text-yellow-400" style="fill:#facc15;width:20px;height:20px;" aria-hidden="true"></i>
                            <!-- 5ª estrela: 90% preenchida (4.9/5) -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <defs>
                                    <linearGradient id="star-partial">
                                        <stop offset="90%" stop-color="#facc15" />
                                        <stop offset="90%" stop-color="#d1d5db" />
                                    </linearGradient>
                                </defs>
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" fill="url(#star-partial)" stroke="url(#star-partial)" />
                            </svg>
                        </div>
                        <p class="text-sm text-gray-600">
                            <strong class="text-gray-900" itemprop="ratingValue">4.9</strong>/5 baseado em mais de <span
                                itemprop="ratingCount">200</span> avaliações
                        </p>
                        <meta itemprop="bestRating" content="5">
                        <meta itemprop="worstRating" content="1">
                    </div>
                </div>

                <!-- Features rápidos -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4">
                    <div class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500" aria-hidden="true"></i>
                        <span class="text-sm font-medium">Grátis para começar</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500" aria-hidden="true"></i>
                        <span class="text-sm font-medium">Sem cartão</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i data-lucide="circle-check" class="text-green-500" aria-hidden="true"></i>
                        <span class="text-sm font-medium">Fácil de usar</span>
                    </div>
                </div>
            </article>

            <!-- Imagem / Mockup -->
            <figure class="relative" data-aos="fade-left">
                <!-- Decoração de fundo -->
                <div class="absolute -inset-8 bg-gradient-to-r from-primary via-orange-500 to-orange-600 rounded-3xl blur-3xl opacity-20 animate-pulse"
                    aria-hidden="true">
                </div>

                <!-- Card principal -->
                <div
                    class="relative bg-white rounded-3xl shadow-2xl p-4 transform hover:scale-105 transition-transform duration-500">
                    <img src="<?= BASE_URL ?>/assets/img/mockups/notebook.jpeg"
                        alt="Dashboard do Lukrato mostrando controle financeiro pessoal com gráficos de receitas e despesas"
                        title="Sistema de controle financeiro Lukrato - Dashboard principal"
                        class="w-full h-auto rounded-2xl" loading="eager" width="800" height="500"
                        fetchpriority="high" />
                    <div class="flex items-center gap-3">
                    </div>
                </div>

                <!-- Badge flutuante - Saldo Total -->
                <div class="absolute -bottom-5 -left-2 sm:-left-4 lg:-left-6 bg-white rounded-2xl shadow-xl p-3 lg:p-4"
                    aria-hidden="true">
                    <div class="flex items-center gap-2 lg:gap-3">
                        <div
                            class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="dollar-sign" class="text-white text-lg lg:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Saldo Total</p>
                            <p class="text-base lg:text-lg font-bold text-gray-900">R$ 12.450</p>
                        </div>
                    </div>
                </div>

                <!-- Badge flutuante - Economia -->
                <div class="absolute -top-5 -left-2 sm:-left-4 lg:-left-6 bg-white rounded-2xl shadow-xl p-3 lg:p-4"
                    aria-hidden="true">
                    <div class="flex items-center gap-2 lg:gap-3">
                        <div
                            class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-br from-primary to-orange-600 rounded-xl flex items-center justify-center">
                            <i data-lucide="line-chart" class="text-white text-lg lg:text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Economia</p>
                            <p class="text-base lg:text-lg font-bold text-green-600">+23%</p>
                        </div>
                    </div>
                </div>
        </div>
        <figcaption class="sr-only">Dashboard do aplicativo Lukrato para controle financeiro pessoal</figcaption>
        </figure>
    </div>

    <!-- Seta de scroll suave -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 hidden lg:block" data-aos="fade-up" data-aos-delay="1000">
        <a href="#funcionalidades"
            class="flex flex-col items-center gap-2 text-gray-400 hover:text-gray-600 transition-colors animate-bounce">
            <span class="text-sm font-medium">Role para descobrir</span>
            <i data-lucide="chevron-down" class="text-xl"></i>
        </a>
    </div>
    </div>
</section>
