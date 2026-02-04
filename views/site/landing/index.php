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
                        <i class="fa-solid fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"
                            aria-hidden="true"></i>
                    </a>

                    <a href="#funcionalidades"
                        class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-gray-700 bg-white border-2 border-gray-200 rounded-xl hover:border-primary hover:text-primary shadow-lg hover:shadow-xl transition-all duration-300"
                        title="Ver funcionalidades do app de controle financeiro"
                        aria-label="Conhecer as funcionalidades do Lukrato">
                        <i class="fa-solid fa-play mr-3" aria-hidden="true"></i>
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
                            <i class="fa-solid fa-star text-yellow-400" aria-hidden="true"></i>
                            <i class="fa-solid fa-star text-yellow-400" aria-hidden="true"></i>
                            <i class="fa-solid fa-star text-yellow-400" aria-hidden="true"></i>
                            <i class="fa-solid fa-star text-yellow-400" aria-hidden="true"></i>
                            <i class="fa-solid fa-star text-yellow-400" aria-hidden="true"></i>
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
                        <i class="fa-solid fa-check-circle text-green-500" aria-hidden="true"></i>
                        <span class="text-sm font-medium">Grátis para começar</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500" aria-hidden="true"></i>
                        <span class="text-sm font-medium">Sem cartão</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500" aria-hidden="true"></i>
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
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-dollar-sign text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Saldo Total</p>
                            <p class="text-lg font-bold text-gray-900">R$ 12.450</p>
                        </div>
                    </div>
                </div>

                <!-- Badge flutuante inferior -->
                <div class="absolute -bottom-6 -left-6 bg-white rounded-2xl shadow-xl p-4" aria-hidden="true">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-12 h-12 bg-gradient-to-br from-primary to-orange-600 rounded-xl flex items-center justify-center">
                            <i class="fa-solid fa-chart-line text-white text-xl"></i>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">Economia</p>
                            <p class="text-lg font-bold text-green-600">+23%</p>
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
            <i class="fa-solid fa-chevron-down text-xl"></i>
        </a>
    </div>
    </div>
</section>

<!-- Seção de Funcionalidades -->
<section id="funcionalidades" class="relative py-20 md:py-32 overflow-hidden bg-gradient-to-b from-white to-gray-50"
    aria-labelledby="funcionalidades-titulo">
    <!-- Background decoration -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div
            class="absolute top-20 right-0 w-96 h-96 bg-orange-100 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob">
        </div>
        <div
            class="absolute bottom-20 left-0 w-96 h-96 bg-orange-100 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000">
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">

            <!-- Conteúdo de texto -->
            <article class="order-2 lg:order-1 space-y-8" data-aos="fade-right">
                <div class="space-y-4">
                    <h2 id="funcionalidades-titulo"
                        class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                        Funcionalidades para Organizar suas
                        <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                            Finanças Pessoais
                        </span>
                    </h2>
                    <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                        Acompanhe <strong>receitas, despesas e agendamentos</strong> em um painel simples de entender,
                        pensado para o seu dia a dia. Controle financeiro nunca foi tão fácil.
                    </p>
                </div>

                <!-- Lista de features -->
                <ul class="space-y-4" role="list">
                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300"
                            aria-hidden="true">
                            <i class="fa-solid fa-chart-line text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-semibold text-lg text-gray-900 mb-1">Dashboard com visão clara do mês</h3>
                            <p class="text-gray-600">Saldo consolidado e leitura rápida de <strong>receitas e
                                    despesas</strong> do período.</p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300"
                            aria-hidden="true">
                            <i class="fa-regular fa-calendar-check text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-semibold text-lg text-gray-900 mb-1">Agendamentos e lembretes de contas</h3>
                            <p class="text-gray-600">Organize <strong>contas a pagar</strong> e evite atrasos com
                                lembretes automáticos.</p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300"
                            aria-hidden="true">
                            <i class="fa-solid fa-chart-pie text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-semibold text-lg text-gray-900 mb-1">Relatórios e gráficos financeiros</h3>
                            <p class="text-gray-600">Entenda seus <strong>hábitos de consumo</strong> com visual limpo e
                                objetivo.</p>
                        </div>
                    </li>
                </ul>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="<?= BASE_URL ?>login"
                        class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
                        title="Começar a usar o Lukrato grátis" aria-label="Criar conta gratuita">
                        Começar grátis
                        <i class="fa-solid fa-arrow-right ml-2" aria-hidden="true"></i>
                    </a>

                    <button type="button" id="openGalleryBtn" @click="$dispatch('open-gallery')"
                        onclick="document.getElementById('galleryModal').style.display='flex'"
                        class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 bg-white border-2 border-gray-200 rounded-xl hover:border-primary hover:text-primary hover:shadow-lg transition-all duration-300"
                        title="Ver screenshots do sistema" aria-label="Abrir galeria de imagens do sistema">
                        <i class="fa-regular fa-images mr-2" aria-hidden="true"></i>
                        Ver o sistema por dentro
                    </button>
                </div>

                <!-- Social proof -->
                <div class="flex items-center gap-3 pt-4">
                    <div class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full animate-pulse" aria-hidden="true"></div>
                    <p class="text-sm text-gray-600">
                        Painel pensado para ser rápido, bonito e fácil de usar.
                    </p>
                </div>
            </article>
            <div class="relative">
                <!-- Decoração de fundo -->
                <div
                    class="absolute -inset-4 bg-gradient-to-r from-primary to-orange-600 rounded-3xl blur-2xl opacity-20 animate-pulse">
                </div>

                <!-- Card Principal -->
                <div
                    class="relative bg-gradient-to-br from-white via-orange-50/30 to-white rounded-3xl shadow-2xl p-8 sm:p-10 border-2 border-orange-100">

                    <!-- Ícone decorativo -->
                    <div
                        class="absolute -top-6 -right-6 w-24 h-24 bg-gradient-to-br from-primary to-orange-600 rounded-full flex items-center justify-center shadow-xl">
                        <i class="fa-solid fa-lightbulb text-4xl text-white"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="relative">
                        <!-- Badge -->
                        <div
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary to-orange-600 text-white rounded-full text-sm font-semibold mb-6 shadow-lg">
                            <i class="fa-solid fa-star"></i>
                            <span>Por que Lukrato?</span>
                        </div>

                        <!-- Título -->
                        <h3 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                            O significado do
                            <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                                nosso nome
                            </span>
                        </h3>

                        <!-- Descrição principal -->
                        <div class="space-y-4 mb-8">
                            <p class="text-lg text-gray-700 leading-relaxed">
                                <strong class="text-primary font-bold">Lukrato</strong> vem do verbo
                                <strong class="text-gray-900">"lucrar"</strong> – e não é por acaso!
                            </p>

                            <p class="text-lg text-gray-600 leading-relaxed">
                                Para começar a ter lucros de verdade, você precisa primeiro se organizar
                                financeiramente.
                                É assim que você consegue guardar sua grana e fazer seu dinheiro trabalhar para
                                você.
                            </p>
                        </div>

                        <!-- Cards de benefícios -->
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div
                                class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                                <div
                                    class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-piggy-bank text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Organize-se</h4>
                                    <p class="text-sm text-gray-600">Controle total das suas finanças</p>
                                </div>
                            </div>

                            <div
                                class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                                <div
                                    class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-primary to-orange-600 rounded-lg flex items-center justify-center">
                                    <i class="fa-solid fa-chart-line text-white"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-900 mb-1">Lucre mais</h4>
                                    <p class="text-sm text-gray-600">Faça seu dinheiro crescer</p>
                                </div>
                            </div>
                        </div>

                        <!-- Citação -->
                        <div
                            class="mt-8 p-6 bg-gradient-to-r from-primary/10 to-orange-600/10 border-l-4 border-primary rounded-r-xl">
                            <p class="text-gray-800 italic font-medium">
                                "Organização financeira é o primeiro passo para conquistar seus objetivos e ter
                                tranquilidade no futuro."
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
    </div>
</section>


<!-- MODAL / GALERIA -->
<div id="galleryModal" x-data="{ 
        open: false, 
        currentSlide: 0, 
        slides: [
            { src: '<?= BASE_URL ?>assets/img/mockups/dashboard.png', title: 'Dashboard', desc: 'Visão geral rápida: saldo, receitas e despesas do mês.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/contas.png', title: 'Contas', desc: 'Crie e gerencie contas: banco, carteira, investimentos.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/categorias.png', title: 'Categorias', desc: 'Organize receitas e despesas por categoria com facilidade.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/relatorios.png', title: 'Relatórios', desc: 'Gráficos e insights para entender seus gastos e evolução.' },
            { src: '<?= BASE_URL ?>assets/img/mockups/5.png', title: 'Tema claro', desc: 'Escolha o tema que preferir para usar no dia a dia.' }
        ],
        nextSlide() {
            this.currentSlide = this.currentSlide < this.slides.length - 1 ? this.currentSlide + 1 : 0;
        },
        prevSlide() {
            this.currentSlide = this.currentSlide > 0 ? this.currentSlide - 1 : this.slides.length - 1;
        }
     }" @open-gallery.window="open = true; currentSlide = 0" @keydown.escape.window="open = false" x-show="open"
    x-cloak style="display: none;" class="fixed inset-0 z-[9999] flex items-center justify-center p-4">

    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" @click="open = false"
        onclick="document.getElementById('galleryModal').style.display='none'"></div>

    <!-- Modal Content -->
    <div class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden" @click.stop>

        <!-- Close button -->
        <button @click="open = false" onclick="document.getElementById('galleryModal').style.display='none'"
            class="absolute top-4 right-4 z-20 w-10 h-10 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-colors">
            <i class="fa-solid fa-xmark text-xl text-gray-700"></i>
        </button>

        <div class="p-6 sm:p-8">
            <h3 class="text-2xl font-bold text-gray-900 mb-6">Um pouco do Lukrato por dentro</h3>

            <!-- Gallery -->
            <div class="relative">
                <!-- Images -->
                <div class="relative aspect-video bg-gray-100 rounded-xl overflow-hidden mb-6">
                    <template x-for="(slide, index) in slides" :key="index">
                        <div x-show="currentSlide === index" x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                            class="absolute inset-0">
                            <img :src="slide.src" :alt="slide.title" class="w-full h-full object-contain"
                                loading="lazy" />
                        </div>
                    </template>
                </div>

                <!-- Navigation Arrows -->
                <button @click="prevSlide()"
                    class="absolute left-2 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-all hover:scale-110 z-10">
                    <i class="fa-solid fa-chevron-left text-gray-700"></i>
                </button>

                <button @click="nextSlide()"
                    class="absolute right-2 top-1/2 -translate-y-1/2 w-12 h-12 flex items-center justify-center bg-white rounded-full shadow-lg hover:bg-gray-100 transition-all hover:scale-110 z-10">
                    <i class="fa-solid fa-chevron-right text-gray-700"></i>
                </button>
            </div>

            <!-- Meta Info -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <div class="flex-1">
                    <h4 class="text-lg font-semibold text-gray-900" x-text="slides[currentSlide].title"></h4>
                    <p class="text-sm text-gray-600" x-text="slides[currentSlide].desc"></p>
                </div>
                <div class="text-sm font-medium text-gray-500">
                    <span x-text="currentSlide + 1"></span>/<span x-text="slides.length"></span>
                </div>
            </div>

            <!-- Thumbnail dots -->
            <div class="flex justify-center gap-2 mt-4">
                <template x-for="(slide, index) in slides" :key="index">
                    <button @click="currentSlide = index" class="w-2 h-2 rounded-full transition-all"
                        :class="currentSlide === index ? 'bg-primary w-8' : 'bg-gray-300 hover:bg-gray-400'">
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>



<!-- Seção de Benefícios -->
<section id="beneficios" class="relative py-20 md:py-32 bg-gradient-to-br from-gray-50 via-orange-50/30 to-orange-50/20"
    aria-labelledby="beneficios-titulo">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header da seção -->
        <header class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <h2 id="beneficios-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                Benefícios do Controle Financeiro
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    Pessoal Inteligente
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                O Lukrato não é apenas um sistema de <strong>gestão financeira</strong>. Ele foi criado para ajudar você
                a
                organizar seu dinheiro, evitar preocupações e tomar <strong>decisões financeiras melhores</strong>
                no dia a dia, sem complicação.
            </p>
        </header>

        <!-- Grid de benefícios -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-16" role="list">

            <!-- Card 1 -->
            <article
                class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                data-aos="fade-up" data-aos-delay="100" role="listitem">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300"
                    aria-hidden="true">
                    <i class="fa-regular fa-eye text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Clareza sobre seu orçamento pessoal</h3>
                <p class="text-gray-600 leading-relaxed">
                    Veja suas <strong>receitas, despesas</strong> e saldo de forma clara e organizada.
                    Nada de confusão, anotações soltas ou planilhas difíceis de entender.
                </p>
            </article>

            <!-- Card 2 -->
            <article
                class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                data-aos="fade-up" data-aos-delay="200" role="listitem">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300"
                    aria-hidden="true">
                    <i class="fa-regular fa-clock text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Economia de tempo no controle de gastos</h3>
                <p class="text-gray-600 leading-relaxed">
                    Registre seus gastos rapidamente e acompanhe tudo em poucos minutos.
                    Menos tempo organizando, mais tempo para o que realmente importa.
                </p>
            </article>

            <!-- Card 3 -->
            <article
                class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                data-aos="fade-up" data-aos-delay="300" role="listitem">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-warning to-yellow-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300"
                    aria-hidden="true">
                    <i class="fa-regular fa-bell text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Evite atrasos e juros desnecessários</h3>
                <p class="text-gray-600 leading-relaxed">
                    Com <strong>agendamentos financeiros</strong> e lembretes, você não esquece mais contas importantes
                    e evita pagar juros por atraso.
                </p>
            </article>

            <!-- Card 4 -->
            <article
                class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                data-aos="fade-up" data-aos-delay="400" role="listitem">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300"
                    aria-hidden="true">
                    <i class="fa-regular fa-chart-bar text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Relatórios financeiros com gráficos visuais</h3>
                <p class="text-gray-600 leading-relaxed">
                    Gráficos simples mostram seus <strong>hábitos de consumo</strong> e ajudam você
                    a entender onde pode economizar ou se planejar melhor.
                </p>
            </article>

            <!-- Card 5 -->
            <article
                class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2"
                data-aos="fade-up" data-aos-delay="500" role="listitem">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300"
                    aria-hidden="true">
                    <i class="fa-regular fa-face-smile text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">App financeiro fácil de usar</h3>
                <p class="text-gray-600 leading-relaxed">
                    O Lukrato foi pensado para qualquer pessoa, mesmo quem nunca usou
                    um <strong>sistema financeiro</strong> antes. Tudo é simples, intuitivo e direto.
                </p>
            </article>

            <!-- Card 6 - destaque extra -->
            <article
                class="sm:col-span-2 lg:col-span-1 group bg-gradient-to-br from-primary to-orange-600 rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 text-white"
                data-aos="fade-up" data-aos-delay="600" role="listitem">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300"
                    aria-hidden="true">
                    <i class="fa-solid fa-rocket text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Comece agora mesmo - É grátis!</h3>
                <p class="text-blue-50 leading-relaxed mb-6">
                    Sem necessidade de cartão de crédito. Crie sua conta gratuitamente e comece a <strong>organizar suas
                        finanças</strong> hoje.
                </p>
                <a href="<?= BASE_URL ?>login"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-gray-50 transition-colors"
                    title="Criar conta gratuita" aria-label="Começar a usar o Lukrato gratuitamente">
                    Começar grátis
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </article>

        </div>

        <!-- CTA final da seção -->
        <div class="max-w-2xl mx-auto text-center bg-white rounded-2xl shadow-xl p-8 sm:p-12" data-aos="fade-up">
            <h3 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-4">
                Pronto para cuidar melhor do seu dinheiro?
            </h3>
            <p class="text-lg text-gray-600 mb-8">
                Comece agora mesmo, sem complicação e sem custos iniciais.
            </p>
            <a href="<?= BASE_URL ?>login"
                class="inline-flex items-center justify-center px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                Começar grátis
                <i class="fa-solid fa-arrow-right ml-2"></i>
            </a>
        </div>

    </div>
</section>


<?php include __DIR__ . '/gamification_section.php'; ?>


<!-- Seção de Planos -->
<section id="planos" class="relative py-20 md:py-32 bg-gray-50" aria-labelledby="planos-titulo">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <div x-data="{ 
                period: 'mensal',
                get discount() {
                    if (this.period === 'semestral') return '10';
                    if (this.period === 'anual') return '15';
                    return '0';
                },
                get periodLabel() {
                    if (this.period === 'mensal') return 'mês';
                    if (this.period === 'semestral') return 'semestre';
                    return 'ano';
                },
                scrollToProCard() {
                    setTimeout(() => {
                        const card = document.getElementById('card-pro');
                        if (card && window.innerWidth < 768) {
                            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 100);
                }
            }">

            <header class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
                <h2 id="planos-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                    Planos de Controle Financeiro
                    <span class="bg-gradient-to-r from-orange-500 to-orange-700 bg-clip-text text-transparent">
                        Simples e Acessíveis
                    </span>
                </h2>
                <p class="text-lg text-gray-600 mb-10">
                    Comece <strong>grátis</strong> e evolua para o Pro quando quiser mais controle sobre suas
                    <strong>finanças pessoais</strong>.
                </p>

                <div class="inline-flex bg-white border border-gray-200 rounded-2xl p-1.5 shadow-sm gap-1">
                    <button @click="period = 'mensal'; scrollToProCard()"
                        :class="period === 'mensal' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                        class="px-6 py-2.5 rounded-xl font-semibold transition-all duration-200">
                        Mensal
                    </button>
                    <button @click="period = 'semestral'; scrollToProCard()"
                        :class="period === 'semestral' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                        class="relative px-6 py-2.5 rounded-xl font-semibold transition-all duration-200">
                        Semestral
                        <span
                            class="absolute -top-2 -right-1 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">-10%</span>
                    </button>
                    <button @click="period = 'anual'; scrollToProCard()"
                        :class="period === 'anual' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                        class="relative px-6 py-2.5 rounded-xl font-semibold transition-all duration-200">
                        Anual
                        <span
                            class="absolute -top-2 -right-1 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">-15%</span>
                    </button>
                </div>

                <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto mb-16 items-stretch">

                    <?php if ($planoGratuito): ?>
                        <div class="flex flex-col bg-white border-2 border-gray-100 rounded-3xl p-8 hover:border-orange-200 transition-all duration-300 shadow-sm hover:shadow-xl"
                            data-aos="fade-right">
                            <div class="flex-grow">
                                <div class="mb-8">
                                    <h3 class="text-xl font-bold text-gray-900 mb-2">
                                        <?= htmlspecialchars($planoGratuito->nome) ?></h3>
                                    <div class="flex items-baseline gap-1 mb-4">
                                        <span class="text-5xl font-extrabold text-gray-900">R$ 0</span>
                                    </div>
                                    <p class="text-gray-500 text-sm leading-relaxed">
                                        <?= htmlspecialchars($planoGratuito->metadados['descricao'] ?? 'Ideal para testar o sistema e entender sua organização.') ?>
                                    </p>
                                </div>

                                <ul class="space-y-4 mb-8">
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <i class="fa-solid fa-check text-green-500"></i>
                                        <span>Controle financeiro essencial</span>
                                    </li>
                                    <?php
                                    $limitacoes = ['Relatórios avançados', 'Agendamentos', 'Exportação de dados', 'Suporte prioritário'];
                                    foreach ($limitacoes as $limite): ?>
                                        <li class="flex items-center gap-3 text-gray-400 opacity-60">
                                            <i class="fa-solid fa-xmark text-gray-300"></i>
                                            <span class="text-sm"><?= $limite ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <a href="<?= BASE_URL ?>login"
                                class="block w-full text-center py-4 px-6 text-orange-600 font-bold border-2 border-orange-600 rounded-2xl hover:bg-orange-50 transition-all">
                                Começar grátis
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($planosPagos as $plano):
                        $precoMensal = $plano->preco_centavos / 100;
                    ?>
                        <div id="card-pro" class="relative flex flex-col bg-gradient-to-b from-orange-500 to-orange-700 rounded-3xl p-8 text-white shadow-2xl shadow-orange-200 hover:scale-[1.02] transition-all duration-300"
                            x-data="{ basePrice: <?= $precoMensal ?> }" data-aos="fade-left">

                            <div
                                class="absolute -top-4 left-1/2 -translate-x-1/2 bg-yellow-400 text-orange-900 text-xs font-black uppercase tracking-widest px-4 py-1.5 rounded-full shadow-lg">
                                Mais Escolhido
                            </div>

                            <div class="flex-grow">
                                <div class="mb-8 pt-2">
                                    <h3 class="text-xl font-bold mb-2"><?= htmlspecialchars($plano->nome) ?></h3>

                                    <!-- Preço promocional - sempre mostra o "de" riscado -->
                                    <div class="h-6">
                                        <span class="text-orange-200 line-through text-sm">
                                            <span x-show="period === 'mensal'">De R$ 29,90</span>
                                            <span x-show="period !== 'mensal'">R$ <span
                                                    x-text="period === 'semestral' ? (basePrice * 6).toFixed(2) : (basePrice * 12).toFixed(2)"></span></span>
                                        </span>
                                    </div>

                                    <div class="flex items-baseline gap-1 mb-2">
                                        <span class="text-5xl font-extrabold"
                                            x-text="'R$ ' + (period === 'mensal' ? basePrice.toFixed(2) : (period === 'semestral' ? (basePrice * 6 * 0.9).toFixed(2) : (basePrice * 12 * 0.85).toFixed(2)))"></span>
                                        <span class="text-orange-100 text-sm" x-text="'/ ' + periodLabel"></span>
                                    </div>

                                    <p class="text-orange-100 text-sm opacity-90"
                                        x-text="period === 'mensal' ? 'Plano flexível' : 'Equivalente a R$ ' + (period === 'semestral' ? (basePrice * 0.9).toFixed(2) : (basePrice * 0.85).toFixed(2)) + ' / mês'">
                                    </p>
                                </div>

                                <ul class="space-y-4 mb-8">
                                    <?php
                                    $recursos = $plano->metadados['recursos'] ?? ['Relatórios avançados', 'Agendamentos', 'Exportação total', 'Categorias ilimitadas', 'Suporte VIP'];
                                    foreach ($recursos as $recurso): ?>
                                        <li class="flex items-center gap-3">
                                            <i class="fa-solid fa-check text-orange-200"></i>
                                            <span class="font-medium"><?= htmlspecialchars($recurso) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <a href="<?= BASE_URL ?>billing"
                                class="block w-full text-center py-4 px-6 bg-white text-orange-600 font-bold rounded-2xl hover:bg-gray-50 transition-all shadow-lg">
                                Assinar Pro agora
                            </a>
                        </div>
                    <?php endforeach; ?>

                </div>
                <div class="text-center" data-aos="fade-up">
                    <p class="text-gray-500 flex items-center justify-center gap-2 text-sm font-medium">
                        <i class="fa-solid fa-shield-halved text-green-500"></i>
                        Sem fidelidade. Cancele quando quiser pelo painel.
                    </p>
                </div>

        </div>
    </div>
</section>

<!-- Seção de Garantia -->
<section id="garantia" class="relative py-20 md:py-32 bg-gradient-to-br from-orange-50 via-orange-50/30 to-gray-50">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <div class="max-w-4xl mx-auto">
            <!-- Card principal -->
            <div class="bg-white rounded-3xl shadow-2xl p-8 sm:p-12 lg:p-16" data-aos="zoom-in">

                <!-- Badge/Icon -->
                <div class="flex justify-center mb-8">
                    <div
                        class="w-20 h-20 bg-gradient-to-br from-primary to-orange-600 rounded-full flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-shield-halved text-3xl text-white"></i>
                    </div>
                </div>

                <!-- Título -->
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-center text-gray-900 mb-6">
                    Sem riscos,
                    <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                        sem surpresas
                    </span>
                </h2>

                <!-- Subtítulo -->
                <p class="text-lg sm:text-xl text-center text-gray-600 mb-12 max-w-2xl mx-auto">
                    O Lukrato foi criado para simplificar sua vida financeira.
                    Você começa grátis e só evolui para o Pro se fizer sentido para você.
                </p>

                <!-- Lista de garantias -->
                <ul class="space-y-6 mb-12">
                    <li class="flex items-start gap-4 group">
                        <div
                            class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-check text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1 pt-2">
                            <p class="text-lg text-gray-800 font-medium">
                                Comece grátis, sem cartão de crédito
                            </p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div
                            class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-check text-primary text-xl"></i>
                        </div>
                        <div class="flex-1 pt-2">
                            <p class="text-lg text-gray-800 font-medium">
                                Cancele quando quiser, direto pelo sistema
                            </p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div
                            class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-check text-primary text-xl"></i>
                        </div>
                        <div class="flex-1 pt-2">
                            <p class="text-lg text-gray-800 font-medium">
                                Seus dados são privados e protegidos
                            </p>
                        </div>
                    </li>
                </ul>

                <!-- CTA -->
                <div class="text-center">
                    <a href="<?= BASE_URL ?>login"
                        class="inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300 mb-4">
                        Começar grátis agora
                        <i class="fa-solid fa-arrow-right ml-3"></i>
                    </a>
                    <p class="text-sm text-gray-500">
                        <i class="fa-regular fa-clock mr-2"></i>
                        Leva menos de 2 minutos para começar
                    </p>
                </div>

            </div>
        </div>

    </div>
</section>

<!-- Seção de Indicação -->
<section id="indicacao" class="relative py-20 md:py-32 bg-white overflow-hidden"
    aria-labelledby="indicacao-titulo">
    <!-- Background decorations -->
    <div class="absolute inset-0 pointer-events-none" aria-hidden="true">
        <div
            class="absolute top-20 left-10 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-15 animate-blob">
        </div>
        <div
            class="absolute bottom-20 right-10 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-10 animate-blob animation-delay-2000">
        </div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <!-- Header -->
        <header class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <div
                class="inline-flex items-center gap-2 px-4 py-2 bg-orange-100 rounded-full mb-6">
                <span class="text-2xl">🎁</span>
                <span class="text-sm font-semibold text-primary">Programa de Indicação</span>
            </div>

            <h2 id="indicacao-titulo"
                class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                Indique amigos e
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    ganhe dias grátis
                </span>
            </h2>

            <p class="text-lg sm:text-xl text-gray-600">
                Compartilhe o Lukrato com seus amigos e ganhe <strong>dias de PRO</strong> para você e para quem você indicar.
                Quanto mais indicações, mais benefícios!
            </p>
        </header>

        <!-- Cards de benefícios -->
        <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto mb-16">
            <!-- Card Você Ganha -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1"
                data-aos="fade-right">
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-primary to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <span class="text-3xl">👤</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Você ganha</h3>
                        <p class="text-gray-600">Por cada amigo indicado</p>
                    </div>
                </div>
                <div class="text-center py-6 bg-white rounded-2xl shadow-inner">
                    <p class="text-5xl font-bold bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                        15 dias
                    </p>
                    <p class="text-gray-600 mt-2 font-medium">de acesso PRO grátis</p>
                </div>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span>Acumule dias ilimitados</span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span>Ganhe quando seu amigo se cadastrar</span>
                    </li>
                </ul>
            </div>

            <!-- Card Amigo Ganha -->
            <div class="bg-gradient-to-br from-gray-50 to-gray-100/50 rounded-3xl p-8 shadow-xl hover:shadow-2xl transition-all duration-300 hover:-translate-y-1"
                data-aos="fade-left">
                <div class="flex items-center gap-4 mb-6">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-secondary to-gray-700 rounded-2xl flex items-center justify-center shadow-lg">
                        <span class="text-3xl">👥</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Seu amigo ganha</h3>
                        <p class="text-gray-600">Ao usar seu código</p>
                    </div>
                </div>
                <div class="text-center py-6 bg-white rounded-2xl shadow-inner">
                    <p class="text-5xl font-bold bg-gradient-to-r from-secondary to-gray-700 bg-clip-text text-transparent">
                        7 dias
                    </p>
                    <p class="text-gray-600 mt-2 font-medium">de acesso PRO grátis</p>
                </div>
                <ul class="mt-6 space-y-3">
                    <li class="flex items-center gap-3 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span>Começa já com benefícios PRO</span>
                    </li>
                    <li class="flex items-center gap-3 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span>Sem precisar pagar nada</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Como funciona -->
        <div class="max-w-4xl mx-auto" data-aos="fade-up">
            <h3 class="text-2xl font-bold text-center text-gray-900 mb-10">
                Como funciona?
            </h3>

            <div class="grid sm:grid-cols-3 gap-6">
                <!-- Passo 1 -->
                <div class="text-center group">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <span class="text-2xl font-bold text-primary">1</span>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Crie sua conta</h4>
                    <p class="text-gray-600 text-sm">
                        Cadastre-se grátis e acesse seu código de indicação no perfil
                    </p>
                </div>

                <!-- Linha conectora (hidden em mobile) -->
                <div class="hidden sm:flex items-center justify-center -mx-6">
                    <div class="w-full h-0.5 bg-gradient-to-r from-orange-200 via-primary to-orange-200"></div>
                </div>

                <!-- Passo 2 -->
                <div class="text-center group">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <span class="text-2xl font-bold text-primary">2</span>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Compartilhe</h4>
                    <p class="text-gray-600 text-sm">
                        Envie seu código ou link para amigos via WhatsApp, redes sociais
                    </p>
                </div>

                <!-- Linha conectora (hidden em mobile) -->
                <div class="hidden sm:flex items-center justify-center -mx-6">
                    <div class="w-full h-0.5 bg-gradient-to-r from-orange-200 via-primary to-orange-200"></div>
                </div>

                <!-- Passo 3 -->
                <div class="text-center group">
                    <div
                        class="w-16 h-16 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:scale-110 transition-transform duration-300">
                        <span class="text-2xl font-bold text-primary">3</span>
                    </div>
                    <h4 class="font-bold text-gray-900 mb-2">Ganhe dias PRO</h4>
                    <p class="text-gray-600 text-sm">
                        Quando seu amigo se cadastrar, vocês dois ganham dias de PRO!
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA -->
        <div class="text-center mt-16" data-aos="fade-up">
            <a href="<?= BASE_URL ?>login"
                class="inline-flex items-center justify-center px-10 py-5 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-xl hover:shadow-2xl hover:scale-105 transition-all duration-300">
                Criar conta e começar a indicar
                <i class="fa-solid fa-arrow-right ml-3"></i>
            </a>
            <p class="text-sm text-gray-500 mt-4">
                <i class="fa-solid fa-gift mr-2 text-primary"></i>
                Sem limite de indicações — quanto mais amigos, mais dias você ganha!
            </p>
        </div>
    </div>
</section>

<!-- Seção de Contato -->
<section id="contato" class="relative py-20 md:py-32 bg-white" x-data="{ activeTab: 'whatsapp' }"
    aria-labelledby="contato-titulo" itemscope itemtype="https://schema.org/ContactPage">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <header class="max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
            <h2 id="contato-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                Fale com o
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    Suporte Lukrato
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 mb-8">
                Dúvidas sobre <strong>controle financeiro pessoal</strong>? Quer sugestões ou precisa de ajuda? Escolha o canal abaixo.
            </p>

            <!-- Toggle Tabs com Alpine.js -->
            <div class="inline-flex bg-gray-100 rounded-xl p-1 shadow-inner">
                <button @click="activeTab = 'whatsapp'"
                    :class="activeTab === 'whatsapp' ? 'bg-white text-primary shadow-md' : 'text-gray-600 hover:text-gray-900'"
                    class="px-8 py-3 rounded-lg font-semibold transition-all duration-300">
                    <i class="fa-brands fa-whatsapp mr-2"></i>
                    WhatsApp
                </button>
                <button @click="activeTab = 'email'"
                    :class="activeTab === 'email' ? 'bg-white text-primary shadow-md' : 'text-gray-600 hover:text-gray-900'"
                    class="px-8 py-3 rounded-lg font-semibold transition-all duration-300">
                    <i class="fa-regular fa-envelope mr-2"></i>
                    E-mail
                </button>
            </div>
    </div>

    <!-- Panels -->
    <div class="max-w-3xl mx-auto">

        <!-- WhatsApp Panel -->
        <div x-show="activeTab === 'whatsapp'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 sm:p-12 shadow-xl"
            data-aos="fade-up">

            <div class="text-center mb-8">
                <div
                    class="inline-flex w-16 h-16 bg-green-500 rounded-full items-center justify-center mb-4 shadow-lg">
                    <i class="fa-brands fa-whatsapp text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Atendimento rápido</h3>
                <p class="text-gray-600">Normalmente respondemos em poucos minutos em horário comercial.</p>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 mb-8">
                <a href="https://wa.me/5544999506302?text=Ol%C3%A1!%20Quero%20falar%20sobre%20o%20Lukrato."
                    target="_blank" rel="noopener"
                    class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <i class="fa-brands fa-whatsapp text-xl"></i>
                    WhatsApp (Comercial)
                </a>

                <a href="https://wa.me/5544997178938?text=Ol%C3%A1!%20Preciso%20de%20suporte%20no%20Lukrato."
                    target="_blank" rel="noopener"
                    class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-white text-green-600 border-2 border-green-500 font-semibold rounded-xl hover:bg-green-50 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <i class="fa-solid fa-headset text-xl"></i>
                    WhatsApp (Suporte)
                </a>
            </div>

            <div class="flex flex-wrap justify-center gap-4 text-sm text-gray-600">
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm">
                    <i class="fa-solid fa-check-circle text-green-500"></i>
                    Sem compromisso
                </span>
                <span class="inline-flex items-center gap-2 px-4 py-2 bg-white rounded-full shadow-sm">
                    <i class="fa-solid fa-lock text-primary"></i>
                    Seus dados ficam privados
                </span>
            </div>
        </div>

        <!-- E-mail Panel -->
        <div x-show="activeTab === 'email'" x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95"
            x-transition:enter-end="opacity-100 transform scale-100"
            class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-2xl p-8 sm:p-12 shadow-xl"
            data-aos="fade-up">

            <div class="text-center mb-8">
                <div
                    class="inline-flex w-16 h-16 bg-primary rounded-full items-center justify-center mb-4 shadow-lg">
                    <i class="fa-regular fa-envelope text-3xl text-white"></i>
                </div>
                <h3 class="text-2xl font-bold text-gray-900 mb-2">Envie uma mensagem</h3>
                <p class="text-gray-600">Prefere e-mail? Mande sua dúvida e respondemos em até 1 dia útil.</p>
            </div>

            <form id="contactForm" class="space-y-6">
                <div class="grid sm:grid-cols-2 gap-6">
                    <div>
                        <label for="lk_nome" class="block text-sm font-semibold text-gray-700 mb-2">
                            Seu nome
                        </label>
                        <input id="lk_nome" name="nome" type="text" placeholder="Seu nome" required
                            class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>

                    <div>
                        <label for="whatsapp" class="block text-sm font-semibold text-gray-700 mb-2">
                            WhatsApp <span class="text-gray-400 font-normal">(opcional)</span>
                        </label>
                        <input id="whatsapp" name="whatsapp" type="text" placeholder="(00) 00000-0000"
                            autocomplete="tel"
                            class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>
                </div>

                <div>
                    <label for="lk_email" class="block text-sm font-semibold text-gray-700 mb-2">
                        Seu e-mail
                    </label>
                    <input id="lk_email" name="email" type="email" placeholder="voce@email.com" required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                </div>

                <div>
                    <label for="lk_assunto" class="block text-sm font-semibold text-gray-700 mb-2">
                        Assunto
                    </label>
                    <input id="lk_assunto" name="assunto" type="text" placeholder="Ex: Dúvida sobre o plano Pro"
                        required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                </div>

                <div>
                    <label for="lk_mensagem" class="block text-sm font-semibold text-gray-700 mb-2">
                        Mensagem
                    </label>
                    <textarea id="lk_mensagem" name="mensagem" rows="6" placeholder="Escreva sua mensagem..."
                        required
                        class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all resize-none"></textarea>
                </div>

                <button type="submit"
                    class="w-full px-8 py-4 text-lg font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                    <i class="fa-regular fa-paper-plane mr-2"></i>
                    Enviar mensagem
                </button>
            </form>

        </div>

    </div>

    </div>
</section>

<!-- Botão Voltar ao Topo -->
<button x-data="{ show: false }" @scroll.window="show = window.pageYOffset > 400" x-show="show"
    x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 scale-75"
    x-transition:enter-end="opacity-100 scale-100" x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-75"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })" type="button"
    class="fixed bottom-8 right-8 z-40 w-14 h-14 bg-gradient-to-r from-primary to-orange-600 text-white rounded-full shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center"
    aria-label="Voltar ao topo" title="Voltar ao topo" style="display: none;">
    <i class="fa-solid fa-arrow-up text-xl"></i>
</button>

<!-- Estilos Tailwind customizados e animações -->
<style>
    @keyframes blob {

        0%,
        100% {
            transform: translate(0, 0) scale(1);
        }

        33% {
            transform: translate(30px, -50px) scale(1.1);
        }

        66% {
            transform: translate(-20px, 20px) scale(0.9);
        }
    }

    .animate-blob {
        animation: blob 7s infinite;
    }

    .animation-delay-2000 {
        animation-delay: 2s;
    }

    .animation-delay-4000 {
        animation-delay: 4s;
    }

    [x-cloak] {
        display: none !important;
    }

    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }

    /* Melhorar a tipografia */
    body {
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
    }
</style>

<script>
    // Formulário de contato
    document.addEventListener('DOMContentLoaded', function() {
        const contactForm = document.getElementById('contactForm');

        if (contactForm) {
            contactForm.addEventListener('submit', async function(e) {
                e.preventDefault();

                const submitButton = this.querySelector('button[type="submit"]');
                const originalText = submitButton.innerHTML;

                // Desabilita o botão e mostra loading
                submitButton.disabled = true;
                submitButton.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i> Enviando...';

                try {
                    const formData = new FormData(this);

                    const response = await fetch('<?= BASE_URL ?>api/contato/enviar', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // Sucesso
                        showNotification(
                            'Mensagem enviada com sucesso! Em breve entraremos em contato.',
                            'success');
                        this.reset();
                    } else {
                        // Erro
                        showNotification(data.message || 'Erro ao enviar mensagem. Tente novamente.',
                            'error');
                    }

                } catch (error) {
                    console.error('Erro:', error);
                    showNotification('Erro ao enviar mensagem. Tente novamente.', 'error');
                } finally {
                    // Restaura o botão
                    submitButton.disabled = false;
                    submitButton.innerHTML = originalText;
                }
            });
        }

        // Função para mostrar notificações
        function showNotification(message, type = 'success') {
            // Remove notificação anterior se existir
            const oldNotification = document.querySelector('.contact-notification');
            if (oldNotification) {
                oldNotification.remove();
            }

            // Cria nova notificação
            const notification = document.createElement('div');
            notification.className =
                'contact-notification fixed top-8 right-8 z-50 max-w-md px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-300 ease-out';

            if (type === 'success') {
                notification.classList.add('bg-green-500', 'text-white');
                notification.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-check-circle text-2xl"></i>
                        <div>
                            <p class="font-semibold">Sucesso!</p>
                            <p class="text-sm">${message}</p>
                        </div>
                    </div>
                `;
            } else {
                notification.classList.add('bg-red-500', 'text-white');
                notification.innerHTML = `
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-exclamation-circle text-2xl"></i>
                        <div>
                            <p class="font-semibold">Erro!</p>
                            <p class="text-sm">${message}</p>
                        </div>
                    </div>
                `;
            }

            document.body.appendChild(notification);

            // Animação de entrada
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.style.opacity = '1';
                    notification.style.transform = 'translateX(0)';
                }, 10);
            }, 10);

            // Remove após 5 segundos
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }, 5000);
        }
    });

    // Fallback para galeria (caso Alpine.js não carregue)
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('galleryModal');

        // Adiciona listener global para o evento
        window.addEventListener('open-gallery', function() {
            if (modal) {
                modal.style.display = 'flex';
                // Se Alpine.js estiver carregado, ele cuida do resto
                // Se não, pelo menos o modal fica visível
            }
        });

        // Fallback: se clicar no botão e nada acontecer após 100ms, abre manualmente
        setTimeout(function() {
            const buttons = document.querySelectorAll('[\\@click*="open-gallery"]');
            buttons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    setTimeout(function() {
                        if (modal && modal.style.display === 'none') {
                            modal.style.display = 'flex';
                        }
                    }, 100);
                });
            });
        }, 1000);
    });
</script>