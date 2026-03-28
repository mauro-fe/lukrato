<!-- Seção de Planos -->
<section id="planos" class="relative py-16 md:py-24 bg-gradient-to-b from-gray-50 to-white"
    aria-labelledby="planos-titulo">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8">

        <?php
        // === Features unificadas para comparação padronizada (sem acesso ao banco) ===
        $allFeatures = [
            ['label' => 'Lançamentos/mês',          'free' => 'Até 100',  'pro' => 'Ilimitados',  'ultra' => 'Ilimitados'],
            ['label' => 'Contas bancárias',          'free' => 'Até 2',   'pro' => 'Ilimitadas',  'ultra' => 'Ilimitadas'],
            ['label' => 'Cartões de crédito',        'free' => '1',       'pro' => 'Ilimitados',  'ultra' => 'Ilimitados'],
            ['label' => 'Categorias',                'free' => '15',      'pro' => 'Ilimitadas',  'ultra' => 'Ilimitadas'],
            ['label' => 'Metas financeiras',         'free' => '3',       'pro' => 'Ilimitadas',  'ultra' => 'Ilimitadas'],
            ['label' => 'Sugestões IA/mês',          'free' => '5',       'pro' => '50',          'ultra' => 'Ilimitadas'],
            ['label' => 'Relatórios avançados',      'free' => false,     'pro' => true,          'ultra' => true],
            ['label' => 'Exportação PDF/Excel',      'free' => false,     'pro' => true,          'ultra' => true],
            ['label' => 'Dashboard avançado',        'free' => false,     'pro' => true,          'ultra' => true],
            ['label' => 'Suporte prioritário',       'free' => false,     'pro' => true,          'ultra' => true],
            ['label' => 'Análise financeira com IA', 'free' => false,     'pro' => false,         'ultra' => true],
            ['label' => 'Suporte VIP',               'free' => false,     'pro' => false,         'ultra' => true],
        ];
        ?>

        <div x-data="{ period: 'mensal', showTable: false }">

            <!-- Header -->
            <header class="max-w-3xl mx-auto text-center mb-12" data-aos="fade-up">
                <span
                    class="inline-flex items-center gap-2 text-orange-600 font-semibold text-sm mb-4 bg-orange-50 px-4 py-1.5 rounded-full">
                    <i data-lucide="crown" class="w-4 h-4"></i>
                    Planos e Preços
                </span>
                <h2 id="planos-titulo" class="text-3xl sm:text-4xl lg:text-5xl font-bold text-gray-900 mb-4">
                    Comece grátis.
                    <span class="bg-gradient-to-r from-orange-500 to-orange-700 bg-clip-text text-transparent">
                        Evolua quando quiser.
                    </span>
                </h2>
                <p class="text-lg text-gray-600 mb-2">
                    Escolha o plano ideal para o seu momento. Sem surpresas, sem letras miúdas.
                </p>
                <p class="text-sm text-gray-400">
                    Todos os planos incluem acesso imediato. Upgrade ou downgrade a qualquer momento.
                </p>

                <!-- Toggle de Período -->
                <div class="inline-flex bg-white border border-gray-200 rounded-2xl p-1.5 shadow-sm gap-1 mt-8">
                    <button @click="period = 'mensal'"
                        :class="period === 'mensal' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                        class="px-5 sm:px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 text-sm sm:text-base">
                        Mensal
                    </button>
                    <button @click="period = 'semestral'"
                        :class="period === 'semestral' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                        class="relative px-5 sm:px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 text-sm sm:text-base">
                        Semestral
                        <span
                            class="absolute -top-2 -right-1 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">-10%</span>
                    </button>
                    <button @click="period = 'anual'"
                        :class="period === 'anual' ? 'bg-orange-600 text-white shadow-md' : 'text-gray-600 hover:bg-gray-50'"
                        class="relative px-5 sm:px-6 py-2.5 rounded-xl font-semibold transition-all duration-200 text-sm sm:text-base">
                        Anual
                        <span
                            class="absolute -top-2 -right-1 bg-green-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">-15%</span>
                    </button>
                </div>
            </header>

            <!-- Cards dos Planos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 lg:gap-8 max-w-6xl mx-auto mb-12 items-stretch">

                <!-- ============================================ -->
                <!-- CARD FREE -->
                <!-- ============================================ -->
                <div class="flex flex-col bg-white border-2 border-gray-100 rounded-3xl p-8 hover:border-orange-200 transition-all duration-300 shadow-sm hover:shadow-xl group"
                    data-aos="fade-up" data-aos-delay="0">
                    <div class="flex-grow">
                        <div class="mb-6">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-10 h-10 rounded-xl bg-gray-100 flex items-center justify-center">
                                    <i data-lucide="user" class="w-5 h-5 text-gray-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Gratuito</h3>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">Para quem está começando</p>
                        </div>

                        <div class="mb-6 pb-6 border-b border-gray-100">
                            <div class="flex items-baseline gap-1">
                                <span class="text-5xl font-extrabold text-gray-500">R$ 0</span>
                                <span class="text-gray-400 text-sm">/ mês</span>
                            </div>
                            <p class="text-gray-400 text-sm mt-1">Grátis para sempre</p>
                        </div>

                        <ul class="space-y-3 mb-8">
                            <?php foreach ($allFeatures as $feat): ?>
                                <?php if ($feat['free'] === false): ?>
                                    <li class="flex items-center gap-3 text-gray-400">
                                        <span
                                            class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                                        </span>
                                        <span class="text-sm line-through"><?= htmlspecialchars($feat['label']) ?></span>
                                    </li>
                                <?php elseif ($feat['free'] === true): ?>
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <span
                                            class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="check" class="w-3 h-3 text-green-600"></i>
                                        </span>
                                        <span class="text-sm"><?= htmlspecialchars($feat['label']) ?></span>
                                    </li>
                                <?php else: ?>
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <span
                                            class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="check" class="w-3 h-3 text-green-600"></i>
                                        </span>
                                        <span class="text-sm"><?= htmlspecialchars($feat['label']) ?>: <strong
                                                class="text-gray-900"><?= htmlspecialchars($feat['free']) ?></strong></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="flex flex-col items-center mt-auto">
                        <a href="<?= BASE_URL ?>login?tab=register"
                            class="block w-full text-center py-4 px-6 text-orange-600 font-bold border-2 border-orange-600 rounded-2xl hover:bg-orange-50 transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                            Começar grátis agora
                        </a>
                        <span class="text-xs text-gray-400 mt-3 flex items-center gap-1">
                            <i data-lucide="credit-card" class="w-3 h-3"></i>
                            Sem cartão de crédito necessário
                        </span>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- CARD PRO — Mais Popular (R$ 14,90/mês) -->
                <!-- ============================================ -->
                <div id="card-pro"
                    class="relative flex flex-col bg-white border-2 border-orange-300 rounded-3xl p-8 transition-all duration-300 shadow-lg shadow-orange-100/50 hover:shadow-xl hover:shadow-orange-200/50 ring-1 ring-orange-200 group"
                    data-aos="fade-up" data-aos-delay="100">

                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                        <span
                            class="bg-gradient-to-r from-orange-500 to-orange-600 text-white text-xs font-black uppercase tracking-widest px-5 py-2 rounded-full shadow-lg flex items-center gap-1.5">
                            <i data-lucide="star" class="w-3 h-3 fill-white"></i>
                            Mais Popular
                        </span>
                    </div>

                    <div class="flex-grow">
                        <div class="mb-6 pt-2">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                                    <i data-lucide="zap" class="w-5 h-5 text-orange-600"></i>
                                </div>
                                <h3 class="text-xl font-bold text-gray-900">Pro</h3>
                            </div>
                            <p class="text-sm text-orange-600 font-medium mt-2">Para quem quer controle real</p>
                        </div>

                        <!-- Preço Pro -->
                        <div class="mb-6 pb-6 border-b border-orange-100">
                            <div class="h-5 mb-1">
                                <span x-show="period === 'semestral'" class="text-gray-400 line-through text-sm">De R$
                                    89,40</span>
                                <span x-show="period === 'anual'" class="text-gray-400 line-through text-sm">De R$
                                    178,80</span>
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span x-show="period === 'mensal'" class="text-5xl font-extrabold text-gray-900">R$
                                    14,90</span>
                                <span x-show="period === 'semestral'" class="text-5xl font-extrabold text-gray-900">R$
                                    80,46</span>
                                <span x-show="period === 'anual'" class="text-5xl font-extrabold text-gray-900">R$
                                    151,98</span>
                            </div>
                            <div class="mt-1">
                                <span x-show="period === 'mensal'" class="text-gray-500 text-sm">/ mês</span>
                                <span x-show="period === 'semestral'" class="text-gray-500 text-sm">/ semestre</span>
                                <span x-show="period === 'anual'" class="text-gray-500 text-sm">/ ano</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <template x-if="period === 'semestral'">
                                    <div>
                                        <p class="text-sm text-gray-500">Equivale a R$ 13,41 / mês</p>
                                        <p class="text-sm text-green-600 font-semibold flex items-center gap-1">
                                            <i data-lucide="trending-down" class="w-3.5 h-3.5"></i>
                                            Economia de R$ 8,94
                                        </p>
                                    </div>
                                </template>
                                <template x-if="period === 'anual'">
                                    <div>
                                        <p class="text-sm text-gray-500">Equivale a R$ 12,67 / mês</p>
                                        <p class="text-sm text-green-600 font-semibold flex items-center gap-1">
                                            <i data-lucide="trending-down" class="w-3.5 h-3.5"></i>
                                            Economia de R$ 26,82
                                        </p>
                                    </div>
                                </template>
                                <template x-if="period === 'mensal'">
                                    <p class="text-sm text-orange-500 font-medium">Melhor custo-benefício</p>
                                </template>
                            </div>
                        </div>

                        <ul class="space-y-3 mb-8">
                            <?php foreach ($allFeatures as $feat): ?>
                                <?php if ($feat['pro'] === false): ?>
                                    <li class="flex items-center gap-3 text-gray-400">
                                        <span
                                            class="w-5 h-5 rounded-full bg-gray-100 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="x" class="w-3 h-3 text-gray-400"></i>
                                        </span>
                                        <span class="text-sm line-through"><?= htmlspecialchars($feat['label']) ?></span>
                                    </li>
                                <?php elseif ($feat['pro'] === true): ?>
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <span
                                            class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="check" class="w-3 h-3 text-green-600"></i>
                                        </span>
                                        <span class="text-sm"><?= htmlspecialchars($feat['label']) ?></span>
                                    </li>
                                <?php else: ?>
                                    <li class="flex items-center gap-3 text-gray-700">
                                        <span
                                            class="w-5 h-5 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="check" class="w-3 h-3 text-green-600"></i>
                                        </span>
                                        <span class="text-sm"><?= htmlspecialchars($feat['label']) ?>: <strong
                                                class="text-gray-900"><?= htmlspecialchars($feat['pro']) ?></strong></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="flex flex-col items-center mt-auto">
                        <a href="<?= BASE_URL ?>login?tab=register"
                            class="block w-full text-center py-4 px-6 text-white font-bold bg-gradient-to-r from-orange-500 to-orange-600 rounded-2xl hover:from-orange-600 hover:to-orange-700 transition-all shadow-md hover:shadow-lg hover:scale-[1.02] active:scale-[0.98]">
                            Quero organizar minhas finanças
                        </a>
                        <span class="text-xs text-gray-400 mt-3 flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                            Sem compromisso · Cancele quando quiser
                        </span>
                    </div>
                </div>

                <!-- ============================================ -->
                <!-- CARD ULTRA — Premium (R$ 39,90/mês) -->
                <!-- ============================================ -->
                <div id="card-ultra"
                    class="relative flex flex-col bg-gradient-to-b from-gray-900 to-gray-800 rounded-3xl p-8 text-white shadow-2xl shadow-gray-900/20 hover:shadow-gray-900/30 hover:scale-[1.01] transition-all duration-300 group"
                    data-aos="fade-up" data-aos-delay="200">

                    <div class="absolute -top-4 left-1/2 -translate-x-1/2 z-10">
                        <span
                            class="bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-900 text-xs font-black uppercase tracking-widest px-5 py-2 rounded-full shadow-lg flex items-center gap-1.5">
                            <i data-lucide="gem" class="w-3 h-3"></i>
                            Experiência Completa
                        </span>
                    </div>

                    <div class="flex-grow">
                        <div class="mb-6 pt-2">
                            <div class="flex items-center gap-3 mb-1">
                                <div class="w-10 h-10 rounded-xl bg-amber-400/20 flex items-center justify-center">
                                    <i data-lucide="sparkles" class="w-5 h-5 text-amber-400"></i>
                                </div>
                                <h3 class="text-xl font-bold text-white">Ultra</h3>
                            </div>
                            <p class="text-sm text-amber-400 font-medium mt-2">Para quem quer dominar suas finanças</p>
                        </div>

                        <!-- Preço Ultra -->
                        <div class="mb-6 pb-6 border-b border-white/10">
                            <div class="h-5 mb-1">
                                <span x-show="period === 'semestral'" class="text-gray-500 line-through text-sm">De R$
                                    239,40</span>
                                <span x-show="period === 'anual'" class="text-gray-500 line-through text-sm">De R$
                                    478,80</span>
                            </div>
                            <div class="flex items-baseline gap-1">
                                <span x-show="period === 'mensal'" class="text-5xl font-extrabold text-white">R$
                                    39,90</span>
                                <span x-show="period === 'semestral'" class="text-5xl font-extrabold text-white">R$
                                    215,46</span>
                                <span x-show="period === 'anual'" class="text-5xl font-extrabold text-white">R$
                                    406,98</span>
                            </div>
                            <div class="mt-1">
                                <span x-show="period === 'mensal'" class="text-gray-400 text-sm">/ mês</span>
                                <span x-show="period === 'semestral'" class="text-gray-400 text-sm">/ semestre</span>
                                <span x-show="period === 'anual'" class="text-gray-400 text-sm">/ ano</span>
                            </div>
                            <div class="mt-2 space-y-1">
                                <template x-if="period === 'semestral'">
                                    <div>
                                        <p class="text-sm text-gray-400">Equivale a R$ 35,91 / mês</p>
                                        <p class="text-sm text-green-400 font-semibold flex items-center gap-1">
                                            <i data-lucide="trending-down" class="w-3.5 h-3.5"></i>
                                            Economia de R$ 23,94
                                        </p>
                                    </div>
                                </template>
                                <template x-if="period === 'anual'">
                                    <div>
                                        <p class="text-sm text-gray-400">Equivale a R$ 33,92 / mês</p>
                                        <p class="text-sm text-green-400 font-semibold flex items-center gap-1">
                                            <i data-lucide="trending-down" class="w-3.5 h-3.5"></i>
                                            Economia de R$ 71,82
                                        </p>
                                    </div>
                                </template>
                                <template x-if="period === 'mensal'">
                                    <p class="text-sm text-amber-400 font-medium">Tudo ilimitado + IA completa</p>
                                </template>
                            </div>
                        </div>

                        <ul class="space-y-3 mb-8">
                            <?php foreach ($allFeatures as $feat): ?>
                                <?php if ($feat['ultra'] === false): ?>
                                    <li class="flex items-center gap-3 text-gray-500">
                                        <span
                                            class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="x" class="w-3 h-3 text-gray-500"></i>
                                        </span>
                                        <span class="text-sm line-through"><?= htmlspecialchars($feat['label']) ?></span>
                                    </li>
                                <?php elseif ($feat['ultra'] === true): ?>
                                    <li class="flex items-center gap-3 text-white">
                                        <span
                                            class="w-5 h-5 rounded-full bg-amber-400/20 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="check" class="w-3 h-3 text-amber-400"></i>
                                        </span>
                                        <span class="text-sm"><?= htmlspecialchars($feat['label']) ?></span>
                                    </li>
                                <?php else: ?>
                                    <li class="flex items-center gap-3 text-white">
                                        <span
                                            class="w-5 h-5 rounded-full bg-amber-400/20 flex items-center justify-center flex-shrink-0">
                                            <i data-lucide="check" class="w-3 h-3 text-amber-400"></i>
                                        </span>
                                        <span class="text-sm"><?= htmlspecialchars($feat['label']) ?>: <strong
                                                class="text-amber-300"><?= htmlspecialchars($feat['ultra']) ?></strong></span>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <div class="flex flex-col items-center mt-auto">
                        <a href="<?= BASE_URL ?>login?tab=register"
                            class="block w-full text-center py-4 px-6 bg-gradient-to-r from-amber-400 to-yellow-500 text-gray-900 font-bold rounded-2xl hover:from-amber-500 hover:to-yellow-600 transition-all shadow-lg hover:shadow-amber-400/25 hover:scale-[1.02] active:scale-[0.98]">
                            Quero controle completo com IA
                        </a>
                        <span class="text-xs text-gray-500 mt-3 flex items-center gap-1">
                            <i data-lucide="shield-check" class="w-3 h-3"></i>
                            Sem compromisso · Cancele quando quiser
                        </span>
                    </div>
                </div>

            </div>

            <!-- Garantia + Link para tabela comparativa -->
            <div class="text-center mb-12" data-aos="fade-up">
                <p class="text-gray-500 flex items-center justify-center gap-2 text-sm font-medium mb-4">
                    <i data-lucide="shield" class="w-4 h-4 text-green-500"></i>
                    Sem fidelidade. Cancele quando quiser pelo painel.
                </p>
                <button @click="showTable = !showTable"
                    class="text-orange-600 hover:text-orange-700 text-sm font-semibold transition-colors inline-flex items-center gap-1.5 hover:underline">
                    <i data-lucide="table-2" class="w-4 h-4"></i>
                    <span x-show="showTable">Ocultar comparação detalhada</span>
                    <span x-show="!showTable">Ver comparação detalhada dos planos</span>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"
                        :class="showTable && 'rotate-180'"></i>
                </button>
            </div>

            <!-- ============================================ -->
            <!-- TABELA COMPARATIVA (expandível) -->
            <!-- ============================================ -->
            <div x-show="showTable" x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
                class="max-w-4xl mx-auto" x-cloak>

                <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="text-left py-4 px-6 font-semibold text-gray-900 w-2/5">Funcionalidade
                                    </th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-600 w-1/5">
                                        <span class="block">Free</span>
                                        <span class="text-xs font-normal text-gray-400">R$ 0</span>
                                    </th>
                                    <th class="text-center py-4 px-4 w-1/5 relative">
                                        <span class="block font-bold text-orange-600">Pro</span>
                                        <span class="text-xs font-normal text-gray-400">R$ 14,90/mês</span>
                                        <span
                                            class="absolute -top-0.5 left-1/2 -translate-x-1/2 bg-orange-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-b-md">POPULAR</span>
                                    </th>
                                    <th class="text-center py-4 px-4 font-semibold text-gray-900 w-1/5">
                                        <span class="block">Ultra</span>
                                        <span class="text-xs font-normal text-gray-400">R$ 39,90/mês</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($allFeatures as $i => $feat): ?>
                                    <tr class="<?= $i % 2 === 0 ? 'bg-gray-50/50' : 'bg-white' ?> border-b border-gray-50">
                                        <td class="py-3 px-6 text-gray-700 font-medium">
                                            <?= htmlspecialchars($feat['label']) ?></td>
                                        <?php foreach (['free', 'pro', 'ultra'] as $tier): ?>
                                            <td class="py-3 px-4 text-center">
                                                <?php if ($feat[$tier] === true): ?>
                                                    <span
                                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100">
                                                        <i data-lucide="check" class="w-3.5 h-3.5 text-green-600"></i>
                                                    </span>
                                                <?php elseif ($feat[$tier] === false): ?>
                                                    <span
                                                        class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-100">
                                                        <i data-lucide="minus" class="w-3.5 h-3.5 text-gray-400"></i>
                                                    </span>
                                                <?php else: ?>
                                                    <span
                                                        class="text-gray-900 font-semibold text-xs"><?= htmlspecialchars($feat[$tier]) ?></span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- CTAs na tabela -->
                    <div class="grid grid-cols-4 gap-0 border-t border-gray-100 bg-gray-50/50">
                        <div class="py-4 px-6"></div>
                        <div class="py-4 px-3 text-center">
                            <a href="<?= BASE_URL ?>login?tab=register"
                                class="inline-block text-xs font-bold text-orange-600 border border-orange-300 rounded-lg px-4 py-2 hover:bg-orange-50 transition-colors">
                                Começar grátis
                            </a>
                        </div>
                        <div class="py-4 px-3 text-center">
                            <a href="<?= BASE_URL ?>login?tab=register"
                                class="inline-block text-xs font-bold text-white bg-orange-600 rounded-lg px-4 py-2 hover:bg-orange-700 transition-colors shadow-sm">
                                Assinar Pro
                            </a>
                        </div>
                        <div class="py-4 px-3 text-center">
                            <a href="<?= BASE_URL ?>login?tab=register"
                                class="inline-block text-xs font-bold text-gray-900 bg-amber-400 rounded-lg px-4 py-2 hover:bg-amber-500 transition-colors shadow-sm">
                                Assinar Ultra
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>