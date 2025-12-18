<!-- Hero Section -->
<section class="relative min-h-screen flex items-center justify-center overflow-hidden bg-gradient-to-br from-orange-50 via-orange-50/30 to-gray-50">
    <!-- Background Decorations -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-orange-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute top-40 right-10 w-72 h-72 bg-orange-300 rounded-full mix-blend-multiply filter blur-3xl opacity-15 animate-blob animation-delay-2000"></div>
        <div class="absolute -bottom-8 left-1/2 w-72 h-72 bg-gray-200 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10 py-20">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            
            <!-- Conteúdo Principal -->
            <div class="text-center lg:text-left space-y-8" data-aos="fade-right">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur-sm rounded-full shadow-md">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-sm font-medium text-gray-700">Mais de 1.000 usuários organizando suas finanças</span>
                </div>

                <!-- Título -->
                <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-bold text-gray-900 leading-tight">
                    Organize suas finanças de forma 
                    <span class="bg-gradient-to-r from-primary via-orange-500 to-orange-600 bg-clip-text text-transparent">
                        simples e inteligente
                    </span>
                </h1>

                <!-- Subtítulo -->
                <p class="text-xl sm:text-2xl text-gray-600 leading-relaxed max-w-2xl mx-auto lg:mx-0">
                    Controle suas entradas, saídas e agendamentos em um só lugar. 
                    Sem complicação, sem surpresas.
                </p>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start pt-4">
                    <a href="<?= BASE_URL ?>login" 
                       class="group inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300">
                        Começar grátis agora
                        <i class="fa-solid fa-arrow-right ml-3 group-hover:translate-x-1 transition-transform"></i>
                    </a>

                    <a href="#funcionalidades" 
                       class="inline-flex items-center justify-center px-10 py-5 text-lg font-bold text-gray-700 bg-white border-2 border-gray-200 rounded-xl hover:border-primary hover:text-primary shadow-lg hover:shadow-xl transition-all duration-300">
                        <i class="fa-solid fa-play mr-3"></i>
                        Ver como funciona
                    </a>
                </div>

                <!-- Social Proof -->
                <div class="flex flex-col sm:flex-row items-center gap-6 pt-8">
                    <div class="flex -space-x-3">
                        <div class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white font-bold shadow-lg">
                            J
                        </div>
                        <div class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white font-bold shadow-lg">
                            M
                        </div>
                        <div class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white font-bold shadow-lg">
                            A
                        </div>
                        <div class="w-12 h-12 rounded-full border-4 border-white bg-gradient-to-br from-warning to-yellow-600 flex items-center justify-center text-white font-bold shadow-lg">
                            +
                        </div>
                    </div>
                    <div class="text-center sm:text-left">
                        <div class="flex gap-1 mb-1 justify-center sm:justify-start">
                            <i class="fa-solid fa-star text-yellow-400"></i>
                            <i class="fa-solid fa-star text-yellow-400"></i>
                            <i class="fa-solid fa-star text-yellow-400"></i>
                            <i class="fa-solid fa-star text-yellow-400"></i>
                            <i class="fa-solid fa-star text-yellow-400"></i>
                        </div>
                        <p class="text-sm text-gray-600">
                            <strong class="text-gray-900">4.9/5</strong> baseado em mais de 200 avaliações
                        </p>
                    </div>
                </div>

                <!-- Features rápidos -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 pt-4">
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span class="text-sm font-medium">Grátis para começar</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span class="text-sm font-medium">Sem cartão</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-700">
                        <i class="fa-solid fa-check-circle text-green-500"></i>
                        <span class="text-sm font-medium">Fácil de usar</span>
                    </div>
                </div>
            </div>

            <!-- Imagem / Mockup -->
            <div class="relative" data-aos="fade-left">
                <!-- Decoração de fundo -->
                <div class="absolute -inset-8 bg-gradient-to-r from-primary via-orange-500 to-orange-600 rounded-3xl blur-3xl opacity-20 animate-pulse"></div>
                
                <!-- Card principal -->
                <div class="relative bg-white rounded-3xl shadow-2xl p-4 transform hover:scale-105 transition-transform duration-500">
                    <img src="<?= BASE_URL ?>/assets/img/mockups/mockup.png" 
                         alt="Dashboard do Lukrato"
                         class="w-full h-auto rounded-2xl"
                         loading="eager" />
                    
                    <!-- Badge flutuante -->
                    <div class="absolute -top-6 -right-6 bg-white rounded-2xl shadow-xl p-4 animate-bounce">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-green-500 to-emerald-600 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-dollar-sign text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Saldo Total</p>
                                <p class="text-lg font-bold text-gray-900">R$ 12.450</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Badge flutuante inferior -->
                    <div class="absolute -bottom-6 -left-6 bg-white rounded-2xl shadow-xl p-4">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-gradient-to-br from-primary to-orange-600 rounded-xl flex items-center justify-center">
                                <i class="fa-solid fa-chart-line text-white text-xl"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Economia</p>
                                <p class="text-lg font-bold text-green-600">+23%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Seta de scroll suave -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 hidden lg:block" data-aos="fade-up" data-aos-delay="1000">
            <a href="#funcionalidades" class="flex flex-col items-center gap-2 text-gray-400 hover:text-gray-600 transition-colors animate-bounce">
                <span class="text-sm font-medium">Role para descobrir</span>
                <i class="fa-solid fa-chevron-down text-xl"></i>
            </a>
        </div>
    </div>
</section>

<!-- Seção de Funcionalidades -->
<section id="funcionalidades" class="relative py-20 md:py-32 overflow-hidden bg-gradient-to-b from-white to-gray-50">
    <!-- Background decoration -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-20 right-0 w-96 h-96 bg-orange-100 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob"></div>
        <div class="absolute bottom-20 left-0 w-96 h-96 bg-orange-100 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000"></div>
    </div>

    <div class="container mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
            
            <!-- Conteúdo de texto -->
            <div class="order-2 lg:order-1 space-y-8" data-aos="fade-right">
                <div class="space-y-4">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 leading-tight">
                        Veja o Lukrato organizando suas 
                        <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                            finanças por você
                        </span>
                    </h2>
                    <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                        Acompanhe entradas, saídas e agendamentos em um painel simples de entender,
                        pensado para o seu dia a dia.
                    </p>
                </div>

                <!-- Lista de features -->
                <ul class="space-y-4">
                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-chart-line text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-semibold text-lg text-gray-900 mb-1">Visão clara do mês</h3>
                            <p class="text-gray-600">Saldo consolidado e leitura rápida do que importa.</p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-regular fa-calendar-check text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-semibold text-lg text-gray-900 mb-1">Agendamentos inteligentes</h3>
                            <p class="text-gray-600">Organize contas e evite atrasos com lembretes.</p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-chart-pie text-xl"></i>
                        </div>
                        <div class="flex-1 pt-1">
                            <h3 class="font-semibold text-lg text-gray-900 mb-1">Relatórios e gráficos</h3>
                            <p class="text-gray-600">Entenda seus hábitos com visual limpo e objetivo.</p>
                        </div>
                    </li>
                </ul>

                <!-- CTAs -->
                <div class="flex flex-col sm:flex-row gap-4 pt-4">
                    <a href="<?= BASE_URL ?>login" 
                       class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-white bg-gradient-to-r from-primary to-orange-600 rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                        Começar grátis
                        <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>

                    <button type="button" 
                            id="openGalleryBtn"
                            @click="$dispatch('open-gallery')"
                            onclick="document.getElementById('galleryModal').style.display='flex'"
                            class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 bg-white border-2 border-gray-200 rounded-xl hover:border-primary hover:text-primary hover:shadow-lg transition-all duration-300">
                        <i class="fa-regular fa-images mr-2"></i>
                        Ver o sistema por dentro
                    </button>
                </div>

                <!-- Social proof -->
                <div class="flex items-center gap-3 pt-4">
                    <div class="flex-shrink-0 w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <p class="text-sm text-gray-600">
                        Painel pensado para ser rápido, bonito e fácil de usar.
                    </p>
                </div>
            </div>

            <!-- Card Explicativo do Nome -->
            <div class="order-1 lg:order-2" data-aos="fade-left">
                <div class="relative">
                    <!-- Decoração de fundo -->
                    <div class="absolute -inset-4 bg-gradient-to-r from-primary to-orange-600 rounded-3xl blur-2xl opacity-20 animate-pulse"></div>
                    
                    <!-- Card Principal -->
                    <div class="relative bg-gradient-to-br from-white via-orange-50/30 to-white rounded-3xl shadow-2xl p-8 sm:p-10 border-2 border-orange-100">
                        
                        <!-- Ícone decorativo -->
                        <div class="absolute -top-6 -right-6 w-24 h-24 bg-gradient-to-br from-primary to-orange-600 rounded-full flex items-center justify-center shadow-xl">
                            <i class="fa-solid fa-lightbulb text-4xl text-white"></i>
                        </div>

                        <!-- Conteúdo -->
                        <div class="relative">
                            <!-- Badge -->
                            <div class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary to-orange-600 text-white rounded-full text-sm font-semibold mb-6 shadow-lg">
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
                                    Para começar a ter lucros de verdade, você precisa primeiro se organizar financeiramente. 
                                    É assim que você consegue guardar sua grana e fazer seu dinheiro trabalhar para você.
                                </p>
                            </div>

                            <!-- Cards de benefícios -->
                            <div class="grid sm:grid-cols-2 gap-4">
                                <div class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-piggy-bank text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-1">Organize-se</h4>
                                        <p class="text-sm text-gray-600">Controle total das suas finanças</p>
                                    </div>
                                </div>

                                <div class="flex items-start gap-3 p-4 bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow">
                                    <div class="flex-shrink-0 w-10 h-10 bg-gradient-to-br from-primary to-orange-600 rounded-lg flex items-center justify-center">
                                        <i class="fa-solid fa-chart-line text-white"></i>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 mb-1">Lucre mais</h4>
                                        <p class="text-sm text-gray-600">Faça seu dinheiro crescer</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Citação -->
                            <div class="mt-8 p-6 bg-gradient-to-r from-primary/10 to-orange-600/10 border-l-4 border-primary rounded-r-xl">
                                <p class="text-gray-800 italic font-medium">
                                    "Organização financeira é o primeiro passo para conquistar seus objetivos e ter tranquilidade no futuro."
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
<div id="galleryModal" 
     x-data="{ 
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
     }"
     @open-gallery.window="open = true; currentSlide = 0"
     @keydown.escape.window="open = false"
     x-show="open"
     x-cloak
     style="display: none;"
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4">
    
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" 
         @click="open = false"
         onclick="document.getElementById('galleryModal').style.display='none'"></div>
    
    <!-- Modal Content -->
    <div class="relative w-full max-w-6xl bg-white rounded-2xl shadow-2xl overflow-hidden"
         @click.stop>
        
        <!-- Close button -->
        <button @click="open = false"
                onclick="document.getElementById('galleryModal').style.display='none'"
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
                        <div x-show="currentSlide === index"
                             x-transition:enter="transition ease-out duration-300"
                             x-transition:enter-start="opacity-0"
                             x-transition:enter-end="opacity-100"
                             class="absolute inset-0">
                            <img :src="slide.src" 
                                 :alt="slide.title"
                                 class="w-full h-full object-contain"
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
                    <button @click="currentSlide = index"
                            class="w-2 h-2 rounded-full transition-all"
                            :class="currentSlide === index ? 'bg-primary w-8' : 'bg-gray-300 hover:bg-gray-400'">
                    </button>
                </template>
            </div>
        </div>
    </div>
</div>



<!-- Seção de Benefícios -->
<section id="beneficios" class="relative py-20 md:py-32 bg-gradient-to-br from-gray-50 via-orange-50/30 to-orange-50/20">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header da seção -->
        <div class="max-w-3xl mx-auto text-center mb-16" data-aos="fade-up">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                Benefícios pensados para facilitar sua 
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    vida financeira
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 leading-relaxed">
                O Lukrato não é apenas um sistema. Ele foi criado para ajudar você a
                organizar seu dinheiro, evitar preocupações e tomar decisões melhores
                no dia a dia, sem complicação.
            </p>
        </div>

        <!-- Grid de benefícios -->
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8 mb-16">

            <!-- Card 1 -->
            <div class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="100">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-primary to-orange-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-regular fa-eye text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Mais clareza sobre o seu dinheiro</h3>
                <p class="text-gray-600 leading-relaxed">
                    Veja suas entradas, saídas e saldo de forma clara e organizada.
                    Nada de confusão, anotações soltas ou planilhas difíceis de entender.
                </p>
            </div>

            <!-- Card 2 -->
            <div class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="200">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-secondary to-gray-700 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-regular fa-clock text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Economia de tempo no dia a dia</h3>
                <p class="text-gray-600 leading-relaxed">
                    Registre seus gastos rapidamente e acompanhe tudo em poucos minutos.
                    Menos tempo organizando, mais tempo para o que realmente importa.
                </p>
            </div>

            <!-- Card 3 -->
            <div class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="300">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-warning to-yellow-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-regular fa-bell text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Evite atrasos e juros desnecessários</h3>
                <p class="text-gray-600 leading-relaxed">
                    Com agendamentos e lembretes, você não esquece mais contas importantes
                    e evita pagar juros por atraso.
                </p>
            </div>

            <!-- Card 4 -->
            <div class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="400">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-orange-500 to-orange-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-regular fa-chart-bar text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Decisões melhores com dados visuais</h3>
                <p class="text-gray-600 leading-relaxed">
                    Gráficos simples mostram seus hábitos financeiros e ajudam você
                    a entender onde pode economizar ou se planejar melhor.
                </p>
            </div>

            <!-- Card 5 -->
            <div class="group bg-white rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2" data-aos="fade-up" data-aos-delay="500">
                <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-regular fa-face-smile text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Fácil de usar, mesmo para iniciantes</h3>
                <p class="text-gray-600 leading-relaxed">
                    O Lukrato foi pensado para qualquer pessoa, mesmo quem nunca usou
                    um sistema financeiro antes. Tudo é simples, intuitivo e direto.
                </p>
            </div>

            <!-- Card 6 - destaque extra -->
            <div class="sm:col-span-2 lg:col-span-1 group bg-gradient-to-br from-primary to-orange-600 rounded-2xl p-8 shadow-md hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 text-white" data-aos="fade-up" data-aos-delay="600">
                <div class="w-14 h-14 rounded-xl bg-white/20 backdrop-blur-sm flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300">
                    <i class="fa-solid fa-rocket text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Comece agora mesmo</h3>
                <p class="text-blue-50 leading-relaxed mb-6">
                    Sem necessidade de cartão de crédito. Crie sua conta gratuitamente e comece a organizar suas finanças hoje.
                </p>
                <a href="<?= BASE_URL ?>login" 
                   class="inline-flex items-center gap-2 px-6 py-3 bg-white text-blue-600 font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    Começar grátis
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

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


<!-- Seção de Planos -->
<section id="planos" class="relative py-20 md:py-32 bg-white">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <!-- Container único com Alpine.js para sincronizar toggle e cards -->
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
                }
            }">
        
            <div class="max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                    Planos simples, 
                    <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                        sem complicação
                    </span>
                </h2>
                <p class="text-lg sm:text-xl text-gray-600 mb-8">
                    Comece grátis e evolua para o Pro quando quiser mais controle, organização e tranquilidade no dia a dia.
                </p>

                <!-- Toggle de Período -->
                <div class="inline-flex bg-white border-2 border-gray-200 rounded-xl p-1.5 shadow-lg gap-2">
                    <button @click="period = 'mensal'" 
                            :class="period === 'mensal' ? 'bg-gradient-to-r from-primary to-orange-600 text-white shadow-md' : 'text-gray-600 hover:text-gray-900'"
                            class="px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                        Mensal
                    </button>
                    <button @click="period = 'semestral'" 
                            :class="period === 'semestral' ? 'bg-gradient-to-r from-primary to-orange-600 text-white shadow-md' : 'text-gray-600 hover:text-gray-900'"
                            class="relative px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                        Semestral
                        <span class="absolute -top-3 -right-1 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-md">-10%</span>
                    </button>
                    <button @click="period = 'anual'" 
                            :class="period === 'anual' ? 'bg-gradient-to-r from-primary to-orange-600 text-white shadow-md' : 'text-gray-600 hover:text-gray-900'"
                            class="relative px-6 py-3 rounded-lg font-semibold transition-all duration-300">
                        Anual
                        <span class="absolute -top-3 -right-1 bg-green-500 text-white text-xs font-bold px-2 py-1 rounded-full shadow-md">-15%</span>
                    </button>
                </div>
            </div>

            <!-- Grid de Planos -->
            <div class="grid lg:grid-cols-2 gap-8 max-w-5xl mx-auto mb-12">
            
            <!-- Plano Gratuito -->
            <?php if ($planoGratuito): ?>
            <div class="bg-white border-2 border-gray-200 rounded-2xl p-8 hover:border-orange-300 hover:shadow-xl transition-all duration-300" data-aos="fade-right">
                <div class="mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($planoGratuito->nome) ?></h3>
                    <div class="flex items-baseline gap-2 mb-4">
                        <span class="text-5xl font-bold text-gray-900">R$ 0</span>
                    </div>
                    <p class="text-gray-600">
                        <?= htmlspecialchars($planoGratuito->metadados['descricao'] ?? 'Ideal para testar o sistema e entender sua organização financeira.') ?>
                    </p>
                </div>

                <ul class="space-y-4 mb-8">
                    <?php 
                    $recursos = $planoGratuito->metadados['recursos'] ?? [
                        'Controle financeiro essencial'
                    ];
                    $limitacoes = $planoGratuito->metadados['limitacoes'] ?? [
                        'Relatórios avançados',
                        'Agendamentos de pagamentos',
                        'Exportação de dados',
                        'Categorias ilimitadas',
                        'Suporte prioritário'
                    ];
                    ?>
                    <?php foreach ($recursos as $recurso): ?>
                    <li class="flex items-start gap-3">
                        <i class="fa-solid fa-check text-green-500 mt-1"></i>
                        <span class="text-gray-700"><?= htmlspecialchars($recurso) ?></span>
                    </li>
                    <?php endforeach; ?>
                    <?php foreach ($limitacoes as $limitacao): ?>
                    <li class="flex items-start gap-3 opacity-50">
                        <i class="fa-solid fa-xmark text-gray-400 mt-1"></i>
                        <span class="text-gray-500"><?= htmlspecialchars($limitacao) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?= BASE_URL ?>login" 
                   class="block w-full text-center px-6 py-4 text-base font-semibold text-primary bg-white border-2 border-primary rounded-xl hover:bg-orange-50 transition-all duration-300">
                    Começar grátis
                </a>
            </div>
            <?php endif; ?>

            <!-- Planos Pagos -->
            <?php foreach ($planosPagos as $index => $plano): 
                $precoMensal = $plano->preco_centavos / 100;
                $recursos = $plano->metadados['recursos'] ?? [
                    'Controle financeiro essencial',
                    'Relatórios avançados',
                    'Agendamentos de pagamentos',
                    'Exportação de dados',
                    'Categorias ilimitadas',
                    'Suporte prioritário'
                ];
                $destaque = $plano->metadados['destaque'] ?? ($index === 0);
            ?>
            <div class="relative bg-gradient-to-br from-primary to-orange-600 rounded-2xl p-8 text-white shadow-2xl hover:shadow-3xl hover:scale-105 transition-all duration-300" 
                 data-aos="fade-left"
                 x-data="{ 
                    basePrice: <?= $precoMensal ?>,
                    get currentPrice() {
                        if (period === 'mensal') return this.basePrice.toFixed(2);
                        if (period === 'semestral') return (this.basePrice * 6 * 0.90).toFixed(2);
                        return (this.basePrice * 12 * 0.85).toFixed(2);
                    },
                    get monthlyEq() {
                        if (period === 'mensal') return this.basePrice.toFixed(2);
                        if (period === 'semestral') return (this.basePrice * 0.90).toFixed(2);
                        return (this.basePrice * 0.85).toFixed(2);
                    },
                    get originalPriceCalc() {
                        if (period === 'semestral') return (this.basePrice * 6).toFixed(2);
                        if (period === 'anual') return (this.basePrice * 12).toFixed(2);
                        return null;
                    }
                 }">
                
                <?php if ($destaque): ?>
                <!-- Badge -->
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="inline-flex items-center gap-2 px-4 py-2 bg-yellow-400 text-yellow-900 text-sm font-bold rounded-full shadow-lg">
                        <i class="fa-solid fa-star"></i>
                        <?= htmlspecialchars($plano->metadados['badge'] ?? 'Mais escolhido') ?>
                    </span>
                </div>
                <?php endif; ?>

                <!-- Badge de Desconto (aparece apenas em semestral/anual) -->
                <div x-show="discount > 0" 
                     x-transition
                     class="absolute -top-4 -right-4 bg-green-500 text-white font-bold px-4 py-2 rounded-full shadow-lg">
                    <span x-text="'-' + discount + '%'"></span>
                </div>

                <div class="mb-6 pt-4">
                    <h3 class="text-2xl font-bold mb-2"><?= htmlspecialchars($plano->nome) ?></h3>
                    
                    <!-- Preço Original Riscado (aparece apenas com desconto) -->
                    <div x-show="originalPriceCalc" x-transition class="mb-1">
                        <span class="text-lg line-through text-orange-200">
                            R$ <span x-text="originalPriceCalc"></span>
                        </span>
                    </div>

                    <div class="flex items-baseline gap-2 mb-2">
                        <span class="text-5xl font-bold" x-text="'R$ ' + currentPrice"></span>
                        <span class="text-xl text-orange-100">
                            / <span x-text="periodLabel"></span>
                        </span>
                    </div>

                    <!-- Equivalente mensal (aparece apenas em semestral/anual) -->
                    <div x-show="period !== 'mensal'" x-transition class="mb-2">
                        <p class="text-orange-100 text-sm">
                            Equivalente a 
                            <strong class="text-white text-lg" x-text="'R$ ' + monthlyEq"></strong>
                            por mês
                        </p>
                    </div>

                    <p class="text-orange-100">
                        <?php if (isset($plano->metadados['mensagens'])): ?>
                            <span x-show="period === 'mensal'"><?= htmlspecialchars($plano->metadados['mensagens']['mensal'] ?? 'Plano mensal flexível') ?></span>
                            <span x-show="period === 'semestral'"><?= htmlspecialchars($plano->metadados['mensagens']['semestral'] ?? 'Economize 10% pagando semestralmente!') ?></span>
                            <span x-show="period === 'anual'"><?= htmlspecialchars($plano->metadados['mensagens']['anual'] ?? 'Melhor oferta! Economize 15% no plano anual.') ?></span>
                        <?php else: ?>
                            <span x-show="period === 'mensal'">Menos que um lanche por mês para ter controle total do seu dinheiro.</span>
                            <span x-show="period === 'semestral'">Economize 10% pagando semestralmente!</span>
                            <span x-show="period === 'anual'">Melhor oferta! Economize 15% no plano anual.</span>
                        <?php endif; ?>
                    </p>
                </div>

                <ul class="space-y-4 mb-8">
                    <?php foreach ($recursos as $recurso): ?>
                    <li class="flex items-start gap-3">
                        <i class="fa-solid fa-check text-green-300 mt-1"></i>
                        <span><?= htmlspecialchars($recurso) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>

                <a href="<?= BASE_URL ?>billing" 
                   class="block w-full text-center px-6 py-4 text-base font-semibold text-primary bg-white rounded-xl hover:bg-gray-50 shadow-lg hover:shadow-xl transition-all duration-300">
                    Assinar <?= htmlspecialchars($plano->nome) ?>
                </a>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- Nota de rodapé -->
        <p class="text-center text-gray-600 max-w-2xl mx-auto" data-aos="fade-up">
            <i class="fa-solid fa-shield-halved text-primary mr-2"></i>
            Sem fidelidade. Cancele quando quiser, direto pelo sistema.
        </p>

        </div> <!-- Fim do container Alpine.js -->

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
                    <div class="w-20 h-20 bg-gradient-to-br from-primary to-orange-600 rounded-full flex items-center justify-center shadow-lg">
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
                        <div class="flex-shrink-0 w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-check text-green-600 text-xl"></i>
                        </div>
                        <div class="flex-1 pt-2">
                            <p class="text-lg text-gray-800 font-medium">
                                Comece grátis, sem cartão de crédito
                            </p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                            <i class="fa-solid fa-check text-primary text-xl"></i>
                        </div>
                        <div class="flex-1 pt-2">
                            <p class="text-lg text-gray-800 font-medium">
                                Cancele quando quiser, direto pelo sistema
                            </p>
                        </div>
                    </li>

                    <li class="flex items-start gap-4 group">
                        <div class="flex-shrink-0 w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
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



<!-- Seção de Contato -->
<section id="contato" class="relative py-20 md:py-32 bg-white" x-data="{ activeTab: 'whatsapp' }">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header -->
        <div class="max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
            <h2 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-6">
                Fale com 
                <span class="bg-gradient-to-r from-primary to-orange-600 bg-clip-text text-transparent">
                    a gente
                </span>
            </h2>
            <p class="text-lg sm:text-xl text-gray-600 mb-8">
                Tirou dúvidas, quer sugestões ou precisa de ajuda? Escolha o canal abaixo.
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
            <div x-show="activeTab === 'whatsapp'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-8 sm:p-12 shadow-xl"
                 data-aos="fade-up">
                
                <div class="text-center mb-8">
                    <div class="inline-flex w-16 h-16 bg-green-500 rounded-full items-center justify-center mb-4 shadow-lg">
                        <i class="fa-brands fa-whatsapp text-3xl text-white"></i>
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Atendimento rápido</h3>
                    <p class="text-gray-600">Normalmente respondemos em poucos minutos em horário comercial.</p>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 mb-8">
                    <a href="https://wa.me/5544999506302?text=Ol%C3%A1!%20Quero%20falar%20sobre%20o%20Lukrato." 
                       target="_blank" 
                       rel="noopener"
                       class="flex-1 inline-flex items-center justify-center gap-3 px-6 py-4 bg-green-500 text-white font-semibold rounded-xl hover:bg-green-600 shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300">
                        <i class="fa-brands fa-whatsapp text-xl"></i>
                        WhatsApp (Comercial)
                    </a>

                    <a href="https://wa.me/5544997178938?text=Ol%C3%A1!%20Preciso%20de%20suporte%20no%20Lukrato." 
                       target="_blank" 
                       rel="noopener"
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
            <div x-show="activeTab === 'email'" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="bg-gradient-to-br from-orange-50 to-orange-100/50 rounded-2xl p-8 sm:p-12 shadow-xl"
                 data-aos="fade-up">
                
                <div class="text-center mb-8">
                    <div class="inline-flex w-16 h-16 bg-primary rounded-full items-center justify-center mb-4 shadow-lg">
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
                            <input id="lk_nome" 
                                   name="nome" 
                                   type="text" 
                                   placeholder="Seu nome" 
                                   required
                                   class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                        </div>

                        <div>
                            <label for="whatsapp" class="block text-sm font-semibold text-gray-700 mb-2">
                                WhatsApp <span class="text-gray-400 font-normal">(opcional)</span>
                            </label>
                            <input id="whatsapp" 
                                   name="whatsapp" 
                                   type="text" 
                                   placeholder="(00) 00000-0000"
                                   autocomplete="tel"
                                   class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                        </div>
                    </div>

                    <div>
                        <label for="lk_email" class="block text-sm font-semibold text-gray-700 mb-2">
                            Seu e-mail
                        </label>
                        <input id="lk_email" 
                               name="email" 
                               type="email" 
                               placeholder="voce@email.com" 
                               required
                               class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>

                    <div>
                        <label for="lk_assunto" class="block text-sm font-semibold text-gray-700 mb-2">
                            Assunto
                        </label>
                        <input id="lk_assunto" 
                               name="assunto" 
                               type="text" 
                               placeholder="Ex: Dúvida sobre o plano Pro" 
                               required
                               class="w-full px-4 py-3 bg-white border-2 border-gray-200 rounded-xl focus:border-primary focus:ring-2 focus:ring-orange-200 outline-none transition-all">
                    </div>

                    <div>
                        <label for="lk_mensagem" class="block text-sm font-semibold text-gray-700 mb-2">
                            Mensagem
                        </label>
                        <textarea id="lk_mensagem" 
                                  name="mensagem" 
                                  rows="6" 
                                  placeholder="Escreva sua mensagem..."
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
<button x-data="{ show: false }" 
        @scroll.window="show = window.pageYOffset > 400"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-75"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-75"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        type="button" 
        class="fixed bottom-8 right-8 z-40 w-14 h-14 bg-gradient-to-r from-primary to-orange-600 text-white rounded-full shadow-xl hover:shadow-2xl hover:scale-110 transition-all duration-300 flex items-center justify-center"
        aria-label="Voltar ao topo" 
        title="Voltar ao topo"
        style="display: none;">
    <i class="fa-solid fa-arrow-up text-xl"></i>
</button>

<!-- Estilos Tailwind customizados e animações -->
<style>
    @keyframes blob {
        0%, 100% { transform: translate(0, 0) scale(1); }
        33% { transform: translate(30px, -50px) scale(1.1); }
        66% { transform: translate(-20px, 20px) scale(0.9); }
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
                        showNotification('Mensagem enviada com sucesso! Em breve entraremos em contato.', 'success');
                        this.reset();
                    } else {
                        // Erro
                        showNotification(data.message || 'Erro ao enviar mensagem. Tente novamente.', 'error');
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
            notification.className = 'contact-notification fixed top-8 right-8 z-50 max-w-md px-6 py-4 rounded-xl shadow-2xl transform transition-all duration-300 ease-out';
            
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
