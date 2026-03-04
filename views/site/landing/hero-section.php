<!-- Hero Section -->
<section
    class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-b from-white via-orange-50/40 to-white"
    aria-label="Seção principal - Controle financeiro pessoal" itemscope itemtype="https://schema.org/WebPageElement">
    <!-- Background Decorations (sutil) -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div class="absolute top-20 left-10 w-96 h-96 bg-orange-100 rounded-full filter blur-3xl opacity-30"></div>
        <div class="absolute bottom-20 right-10 w-80 h-80 bg-gray-100 rounded-full filter blur-3xl opacity-40"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Conteúdo Principal -->
            <article class="text-center lg:text-left space-y-6" data-aos="fade-up">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-orange-50 border border-orange-100 rounded-full">
                    <i data-lucide="shield-check" class="w-4 h-4 text-primary" aria-hidden="true"></i>
                    <span class="text-sm font-medium text-gray-700">100% gratuito para começar</span>
                </div>

                <!-- Título Principal -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 leading-tight">
                    Organize suas Finanças Pessoais de Forma Simples e
                    <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                        Gratuita
                    </span>
                </h1>

                <!-- Subtítulo -->
                <p class="text-lg sm:text-xl text-gray-600 leading-relaxed max-w-xl mx-auto lg:mx-0">
                    O app brasileiro de controle financeiro gratuito com dashboard inteligente, gestão de cartões de crédito, relatórios visuais e controle de gastos.
                    Tudo em um só lugar, sem complicação.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-3 justify-center lg:justify-start pt-2">
                    <a href="<?= BASE_URL ?>login"
                        class="group inline-flex items-center justify-center px-8 py-4 text-base font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg shadow-orange-500/20 hover:shadow-xl hover:shadow-orange-500/30 hover:scale-[1.02] transition-all duration-300"
                        title="Criar conta gratuita no Lukrato" aria-label="Começar a usar o Lukrato gratuitamente">
                        Começar grátis agora
                        <i data-lucide="arrow-right" class="ml-2 w-5 h-5 group-hover:translate-x-1 transition-transform" aria-hidden="true"></i>
                    </a>

                    <a href="#como-funciona"
                        class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 bg-white border border-gray-200 rounded-xl hover:border-primary/40 hover:text-primary shadow-sm hover:shadow-md transition-all duration-300"
                        title="Ver como funciona o Lukrato" aria-label="Entenda como funciona">
                        <i data-lucide="play-circle" class="mr-2 w-5 h-5" aria-hidden="true"></i>
                        Ver como funciona
                    </a>
                </div>

                <!-- Trust badges -->
                <div class="flex flex-wrap items-center gap-4 pt-4 justify-center lg:justify-start">
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <i data-lucide="circle-check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>Sem cartão de crédito</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <i data-lucide="circle-check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>Cadastro em 1 minuto</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <i data-lucide="circle-check" class="w-4 h-4 text-green-500" aria-hidden="true"></i>
                        <span>LGPD compliant</span>
                    </div>
                </div>
            </article>

            <!-- Imagem / Mockup -->
            <figure class="relative" data-aos="fade-up" data-aos-delay="150">
                <!-- Card principal -->
                <div class="relative bg-white rounded-2xl shadow-xl border border-gray-100 p-3 lg:p-4">
                    <img src="<?= BASE_URL ?>/assets/img/mockups/5.png"
                        alt="Dashboard do Lukrato mostrando controle financeiro pessoal em modo claro"
                        title="Sistema de controle financeiro Lukrato - Dashboard principal"
                        class="w-full h-auto rounded-xl" loading="eager" width="800" height="500"
                        fetchpriority="high" />
                </div>

                <!-- Badge flutuante - Saldo Total -->
                <div class="absolute -bottom-4 -left-2 sm:-left-4 bg-white rounded-xl shadow-lg border border-gray-100 p-3"
                    aria-hidden="true" data-aos="fade-up" data-aos-delay="400">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                            <i data-lucide="trending-up" class="w-5 h-5 text-green-600"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Saldo do mês</p>
                            <p class="text-sm font-bold text-gray-900">R$ 3.450,00</p>
                        </div>
                    </div>
                </div>

                <!-- Badge flutuante - Economia -->
                <div class="absolute -top-4 -right-2 sm:-right-4 bg-white rounded-xl shadow-lg border border-gray-100 p-3"
                    aria-hidden="true" data-aos="fade-up" data-aos-delay="500">
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
                            <i data-lucide="piggy-bank" class="w-5 h-5 text-primary"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-medium">Economia</p>
                            <p class="text-sm font-bold text-green-600">+18% no mês</p>
                        </div>
                    </div>
                </div>

                <figcaption class="sr-only">Dashboard do aplicativo Lukrato para controle financeiro pessoal</figcaption>
            </figure>
        </div>
    </div>

    <!-- Seta de scroll suave -->
    <div class="absolute bottom-8 left-1/2 -translate-x-1/2 hidden lg:block" data-aos="fade-up" data-aos-delay="800">
        <a href="#como-funciona"
            class="flex flex-col items-center gap-2 text-gray-400 hover:text-primary transition-colors animate-bounce">
            <span class="text-sm font-medium">Descubra mais</span>
            <i data-lucide="chevron-down" class="w-5 h-5"></i>
        </a>
    </div>
</section>
