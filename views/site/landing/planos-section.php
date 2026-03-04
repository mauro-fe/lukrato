<!-- Seção de Planos -->
<section id="planos" class="relative py-16 md:py-24 bg-white" aria-labelledby="planos-titulo">
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

            <header class="lk-header-card max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
                <h2 id="planos-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Planos de Controle Financeiro
                    <span class="bg-gradient-to-r from-orange-500 to-orange-700 bg-clip-text text-transparent">
                        Simples e Acessíveis
                    </span>
                </h2>
                <p class="text-lg text-gray-600 mb-6">
                    Comece <strong>grátis</strong> e evolua para o Pro quando quiser mais controle sobre suas
                    <strong>finanças pessoais</strong>.
                </p>

                <div class="inline-flex bg-white border border-gray-200 rounded-2xl p-1.5 shadow-sm gap-1 mb-6">
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
                                        <i data-lucide="check" class="text-green-500"></i>
                                        <span>Controle financeiro essencial</span>
                                    </li>
                                    <?php
                                    $limitacoes = ['Relatórios avançados', 'Agendamentos', 'Exportação de dados', 'Suporte prioritário'];
                                    foreach ($limitacoes as $limite): ?>
                                        <li class="flex items-center gap-3 text-gray-400 opacity-60">
                                            <i data-lucide="x" class="text-gray-300"></i>
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
                        <div id="card-pro"
                            class="relative flex flex-col bg-gradient-to-b from-orange-500 to-orange-700 rounded-3xl p-8 text-white shadow-2xl shadow-orange-200 hover:scale-[1.02] transition-all duration-300"
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
                                            <i data-lucide="check" class="text-orange-200"></i>
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
                        <i data-lucide="shield" class="text-green-500"></i>
                        Sem fidelidade. Cancele quando quiser pelo painel.
                    </p>
                </div>

        </div>
    </div>
</section>