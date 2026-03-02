<!-- Seção de Funcionalidades -->
<section id="funcionalidades" class="relative py-14 md:py-24 overflow-hidden bg-gradient-to-b from-white to-gray-50"
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
                            <i data-lucide="line-chart" class="text-xl"></i>
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
                            <i data-lucide="calendar-check" class="text-xl"></i>
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
                            <i data-lucide="pie-chart" class="text-xl"></i>
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
                        <i data-lucide="arrow-right" class="ml-2" aria-hidden="true"></i>
                    </a>

                    <button type="button" id="openGalleryBtn" @click="$dispatch('open-gallery')"
                        onclick="document.getElementById('galleryModal').style.display='flex'"
                        class="inline-flex items-center justify-center px-8 py-4 text-base font-semibold text-gray-700 bg-white border-2 border-gray-200 rounded-xl hover:border-primary hover:text-primary hover:shadow-lg transition-all duration-300"
                        title="Ver screenshots do sistema" aria-label="Abrir galeria de imagens do sistema">
                        <i data-lucide="images" class="mr-2" aria-hidden="true"></i>
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
                        <i data-lucide="lightbulb" class="text-4xl text-white"></i>
                    </div>

                    <!-- Conteúdo -->
                    <div class="relative">
                        <!-- Badge -->
                        <div
                            class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-primary to-orange-600 text-white rounded-full text-sm font-semibold mb-6 shadow-lg">
                            <i data-lucide="star"></i>
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
                                    <i data-lucide="piggy-bank" class="text-white"></i>
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
                                    <i data-lucide="line-chart" class="text-white"></i>
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
