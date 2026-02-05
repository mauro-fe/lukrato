<?php
$pageTitle = $pageTitle ?? 'Lukrato – Controle Financeiro Pessoal Simples e Inteligente | Grátis';

$pageDescription = $pageDescription ??
    'Organize suas finanças pessoais de forma simples e inteligente. O Lukrato é o melhor aplicativo gratuito de controle financeiro para gerenciar gastos, criar orçamentos, acompanhar despesas e ter clareza sobre seu dinheiro. Comece grátis agora!';

$pageKeywords = $pageKeywords ??
    'controle financeiro pessoal, aplicativo controle financeiro grátis, controle de gastos, orçamento pessoal, finanças pessoais, como economizar dinheiro, app financeiro, planilha de gastos, gerenciador financeiro, controle de despesas, organizar finanças, planejamento financeiro pessoal, app para controlar gastos, controle financeiro online, sistema financeiro pessoal';

$pageUrl = $pageUrl ?? BASE_URL . ltrim($_SERVER['REQUEST_URI'], '/');

$pageImage = $pageImage ?? BASE_URL . 'assets/img/og-image.png'; // 1200x630px

$extraCss  = $extraCss ?? [];

$canonicalUrl = $canonicalUrl ?? rtrim(BASE_URL, '/') . '/';

// Breadcrumb data
$breadcrumbItems = $breadcrumbItems ?? [];
?>


<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Primary Meta Tags -->
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    <meta name="author" content="Lukrato">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="bingbot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <!-- Mobile & App Meta Tags -->
    <meta name="theme-color" content="#e67e22">
    <meta name="msapplication-TileColor" content="#e67e22">
    <meta name="application-name" content="Lukrato">
    <meta name="apple-mobile-web-app-title" content="Lukrato">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">

    <!-- Geo Tags -->
    <meta name="geo.region" content="BR-PR">
    <meta name="geo.placename" content="Campina da Lagoa, Paraná, Brasil">
    <meta name="geo.position" content="-24.5896;-52.7983">
    <meta name="ICBM" content="-24.5896, -52.7983">


    <!-- Language -->
    <meta name="language" content="Portuguese">
    <link rel="alternate" hreflang="pt-br" href="<?= htmlspecialchars($pageUrl) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= htmlspecialchars($pageUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="Lukrato - Controle Financeiro Pessoal">
    <meta property="og:site_name" content="Lukrato">
    <meta property="og:locale" content="pt_BR">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($pageUrl) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($pageImage) ?>">
    <meta name="twitter:image:alt" content="Lukrato - Controle Financeiro Pessoal">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <meta name="msapplication-TileImage" content="<?= BASE_URL ?>assets/img/icone.png">

    <!-- DNS Prefetch & Preconnect -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdn.tailwindcss.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#e67e22',
                        secondary: '#2c3e50',
                        success: '#2ecc71',
                        warning: '#f39c12',
                        danger: '#e74c3c',
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <!-- Style para Alpine.js x-cloak -->
    <style>
        [x-cloak] {
            display: none !important;
        }

        html,
        body {
            overflow-x: hidden;
            width: 100%;
            max-width: 100vw;
        }
    </style>

    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/variables.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site/landing-base.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site/modal-override.css">

    <?php foreach ($extraCss as $css): ?>
        <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/site/<?= htmlspecialchars($css) ?>.css">
    <?php endforeach; ?>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Schema.org Markup (JSON-LD) - SoftwareApplication -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "SoftwareApplication",
            "name": "Lukrato",
            "applicationCategory": "FinanceApplication",
            "applicationSubCategory": "PersonalFinance",
            "operatingSystem": "Web-based",
            "offers": {
                "@type": "Offer",
                "price": "0",
                "priceCurrency": "BRL",
                "availability": "https://schema.org/InStock",
                "priceValidUntil": "2027-12-31"
            },
            "aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": "4.9",
                "bestRating": "5",
                "worstRating": "1",
                "ratingCount": "487",
                "reviewCount": "234"
            },
            "description": "<?= htmlspecialchars($pageDescription) ?>",
            "url": "<?= htmlspecialchars(BASE_URL) ?>",
            "image": "<?= htmlspecialchars($pageImage) ?>",
            "screenshot": "<?= BASE_URL ?>assets/img/mockups/dashboard.png",
            "softwareVersion": "2.0",
            "datePublished": "2024-01-01",
            "dateModified": "2026-02-03",
            "inLanguage": "pt-BR",
            "provider": {
                "@type": "Organization",
                "name": "Lukrato",
                "url": "<?= htmlspecialchars(BASE_URL) ?>",
                "logo": {
                    "@type": "ImageObject",
                    "url": "<?= BASE_URL ?>assets/img/logo.png",
                    "width": 180,
                    "height": 64
                },
                "contactPoint": {
                    "@type": "ContactPoint",
                    "telephone": "+55-44-99950-6302",
                    "contactType": "Customer Service",
                    "availableLanguage": ["Portuguese"],
                    "areaServed": "BR"
                },
                "address": {
                    "@type": "PostalAddress",
                    "addressLocality": "Campina da Lagoa",
                    "addressRegion": "PR",
                    "addressCountry": "BR"
                }
            },
            "featureList": [
                "Controle de gastos mensais",
                "Planejamento de orçamento pessoal",
                "Acompanhamento de despesas e receitas",
                "Metas financeiras personalizadas",
                "Relatórios e gráficos detalhados",
                "Lembretes de contas a pagar",
                "Gestão de cartões de crédito",
                "Categorização automática de transações",
                "Dashboard intuitivo",
                "Exportação de dados"
            ],
            "review": [{
                    "@type": "Review",
                    "author": {
                        "@type": "Person",
                        "name": "João Silva"
                    },
                    "datePublished": "2026-01-15",
                    "reviewBody": "O melhor app de controle financeiro que já usei. Simples e eficiente!",
                    "reviewRating": {
                        "@type": "Rating",
                        "ratingValue": "5",
                        "bestRating": "5"
                    }
                },
                {
                    "@type": "Review",
                    "author": {
                        "@type": "Person",
                        "name": "Maria Oliveira"
                    },
                    "datePublished": "2026-01-20",
                    "reviewBody": "Finalmente consegui organizar minhas finanças. Recomendo demais!",
                    "reviewRating": {
                        "@type": "Rating",
                        "ratingValue": "5",
                        "bestRating": "5"
                    }
                }
            ]
        }
    </script>

    <!-- Schema.org Markup (JSON-LD) - Organization -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Lukrato",
            "alternateName": "Lukrato - Controle Financeiro",
            "url": "<?= htmlspecialchars(BASE_URL) ?>",
            "logo": {
                "@type": "ImageObject",
                "url": "<?= BASE_URL ?>assets/img/logo.png",
                "width": 180,
                "height": 64
            },
            "description": "Controle financeiro pessoal simples e gratuito para organizar suas finanças",
            "foundingDate": "2024",
            "sameAs": [
                "https://www.instagram.com/lukrato.oficial/"
            ],
            "contactPoint": {
                "@type": "ContactPoint",
                "telephone": "+55-44-99950-6302",
                "contactType": "Customer Service",
                "availableLanguage": ["Portuguese"],
                "areaServed": "BR",
                "email": "lukratosistema@gmail.com"
            },
            "address": {
                "@type": "PostalAddress",
                "addressLocality": "Campina da Lagoa",
                "addressRegion": "PR",
                "postalCode": "87345-000",
                "addressCountry": "BR"
            }
        }
    </script>

    <!-- Schema.org Markup (JSON-LD) - WebSite -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "Lukrato",
            "alternateName": "Lukrato Controle Financeiro",
            "url": "<?= htmlspecialchars(BASE_URL) ?>",
            "description": "<?= htmlspecialchars($pageDescription) ?>",
            "inLanguage": "pt-BR",
            "potentialAction": {
                "@type": "SearchAction",
                "target": {
                    "@type": "EntryPoint",
                    "urlTemplate": "<?= htmlspecialchars(BASE_URL) ?>busca?q={search_term_string}"
                },
                "query-input": "required name=search_term_string"
            }
        }
    </script>

    <!-- Schema.org Markup (JSON-LD) - FAQPage -->
    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "FAQPage",
            "mainEntity": [{
                    "@type": "Question",
                    "name": "O Lukrato é gratuito?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Sim! O Lukrato oferece um plano gratuito com funcionalidades essenciais para controle financeiro pessoal. Você pode começar a usar sem cartão de crédito e evoluir para o plano Pro quando quiser mais recursos."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Como o Lukrato me ajuda a organizar minhas finanças?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "O Lukrato permite registrar suas receitas e despesas, acompanhar seus gastos por categoria, gerenciar cartões de crédito, criar agendamentos de contas e visualizar relatórios detalhados do seu dinheiro."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Meus dados ficam seguros no Lukrato?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Sim, seus dados são protegidos com criptografia e seguimos todas as normas da LGPD (Lei Geral de Proteção de Dados). Seus dados financeiros são privados e nunca compartilhados com terceiros."
                    }
                },
                {
                    "@type": "Question",
                    "name": "Posso cancelar minha assinatura a qualquer momento?",
                    "acceptedAnswer": {
                        "@type": "Answer",
                        "text": "Sim! Não há fidelidade. Você pode cancelar sua assinatura Pro a qualquer momento diretamente pelo painel, sem burocracia."
                    }
                }
            ]
        }
    </script>

    <!-- Schema.org Markup (JSON-LD) - BreadcrumbList -->
    <?php if (!empty($breadcrumbItems)): ?>
        <script type="application/ld+json">
            {
                "@context": "https://schema.org",
                "@type": "BreadcrumbList",
                "itemListElement": [
                    <?php foreach ($breadcrumbItems as $index => $item): ?> {
                            "@type": "ListItem",
                            "position": <?= $index + 1 ?>,
                            "name": "<?= htmlspecialchars($item['name']) ?>",
                            "item": "<?= htmlspecialchars($item['url']) ?>"
                        }
                        <?= ($index < count($breadcrumbItems) - 1) ? ',' : '' ?>
                    <?php endforeach; ?>
                ]
            }
        </script>
    <?php endif; ?>

    <script>
        // Inicializar AOS quando a página carregar
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                duration: 800,
                once: true,
                offset: 100
            });
        });
    </script>

</head>

<body class="antialiased" x-data="{ mobileMenuOpen: false }">
    <!-- Header Premium -->
    <header x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 20"
        :class="scrolled ? 'bg-white shadow-lg' : 'bg-white/95'"
        class="fixed top-0 left-0 right-0 z-50 backdrop-blur-lg transition-all duration-300" role="banner">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <!-- Logo com animação -->
                <a href="<?= BASE_URL ?>" class="flex-shrink-0 group" aria-label="Lukrato - Página Inicial">
                    <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato - Controle Financeiro Pessoal Gratuito"
                        title="Lukrato - Organize suas Finanças"
                        class="h-8 w-auto max-w-[120px] sm:h-16 sm:max-w-none transition-transform duration-300 group-hover:scale-110"
                        loading="eager" width="180" height="64" onerror="console.error('Logo não carregou:', this.src)">
                </a>

                <!-- Desktop Navigation Premium -->
                <nav class="hidden lg:flex items-center gap-8" aria-label="Navegação principal" role="navigation">
                    <a href="<?= BASE_URL ?>#funcionalidades"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group"
                        aria-label="Ver funcionalidades do app">
                        Funcionalidades
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#beneficios"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group"
                        aria-label="Conhecer benefícios do Lukrato">
                        Benefícios
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#planos"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group"
                        aria-label="Ver planos e preços">
                        Planos
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#indicacao"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group"
                        aria-label="Programa de indicação">
                        <span class="inline-flex items-center gap-1">
                            Indicação
                            <span
                                class="text-xs bg-gradient-to-r from-primary to-orange-600 text-white px-1.5 py-0.5 rounded-full">🎁</span>
                        </span>
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#contato"
                        class="relative text-gray-700 font-semibold hover:text-transparent hover:bg-clip-text hover:bg-gradient-to-r hover:from-primary hover:to-orange-600 transition-all duration-300 group"
                        aria-label="Entre em contato conosco">
                        Contato
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                </nav>

                <!-- Desktop Actions Premium -->
                <div class="hidden lg:flex items-center gap-4">
                    <a href="<?= BASE_URL ?>login"
                        class="inline-flex items-center gap-2 px-4 py-2 text-gray-700 hover:text-primary font-semibold transition-all duration-300 group"
                        aria-label="Entrar na sua conta">
                        <i class="fa-regular fa-user text-sm transition-transform group-hover:scale-110"
                            aria-hidden="true"></i>
                        <span>Entrar</span>
                    </a>
                    <a href="<?= BASE_URL ?>login?tab=register"
                        class="inline-flex items-center gap-2 px-7 py-3 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-full shadow-lg shadow-orange-500/30 hover:shadow-xl hover:shadow-orange-500/50 hover:scale-105 transition-all duration-300 group"
                        aria-label="Começar a usar grátis">
                        <span>Começar grátis</span>
                        <i class="fa-solid fa-arrow-right text-sm transition-transform group-hover:translate-x-1"
                            aria-hidden="true"></i>
                    </a>
                </div>

                <!-- Mobile Menu Button Premium -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden relative p-2.5 text-gray-700 hover:text-primary hover:bg-orange-50 rounded-xl transition-all duration-300"
                    type="button" aria-label="Abrir menu de navegação" aria-expanded="false"
                    :aria-expanded="mobileMenuOpen">
                    <i class="fa-solid fa-bars text-2xl" x-show="!mobileMenuOpen" aria-hidden="true"></i>
                    <i class="fa-solid fa-xmark text-2xl" x-show="mobileMenuOpen" x-cloak aria-hidden="true"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Backdrop -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition-opacity ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" @click="mobileMenuOpen = false"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] lg:hidden" x-cloak aria-hidden="true">
    </div>

    <!-- Mobile Menu Panel -->
    <div x-show="mobileMenuOpen" x-transition:enter="transition ease-out duration-300 transform"
        x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full"
        class="fixed top-0 right-0 bottom-0 w-80 max-w-full bg-white shadow-2xl z-[70] lg:hidden overflow-y-auto"
        x-cloak role="dialog" aria-modal="true" aria-label="Menu de navegação mobile">
        <div class="p-6">
            <!-- Header do Menu -->
            <div class="flex items-center justify-end mb-8">
                <button @click="mobileMenuOpen = false"
                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
                    type="button" aria-label="Fechar menu">
                    <i class="fa-solid fa-xmark text-2xl" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Navegação -->
            <nav class="flex flex-col gap-4 mb-6" role="navigation" aria-label="Menu mobile">
                <a href="<?= BASE_URL ?>#funcionalidades" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Funcionalidades
                </a>
                <a href="<?= BASE_URL ?>#beneficios" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Benefícios
                </a>
                <a href="<?= BASE_URL ?>#planos" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Planos
                </a>
                <a href="<?= BASE_URL ?>#indicacao" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors flex items-center justify-between">
                    <span>Indicação</span>
                    <span
                        class="text-xs bg-gradient-to-r from-primary to-orange-600 text-white px-2 py-1 rounded-full">🎁
                        Ganhe PRO</span>
                </a>
                <a href="<?= BASE_URL ?>#contato" @click="mobileMenuOpen = false"
                    class="text-gray-700 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 transition-colors">
                    Contato
                </a>
            </nav>

            <!-- Botões de Ação -->
            <div class="flex flex-col gap-3 pt-4 border-t border-gray-100">
                <a href="<?= BASE_URL ?>login"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 text-gray-700 hover:text-primary font-semibold border-2 border-gray-200 rounded-xl hover:border-primary transition-all duration-300"
                    aria-label="Entrar">
                    <i class="fa-regular fa-user" aria-hidden="true"></i>
                    <span>Entrar</span>
                </a>
                <a href="<?= BASE_URL ?>login?tab=register"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
                    aria-label="Começar a usar grátis">
                    <span>Começar grátis</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Skip to main content link for accessibility -->
    <a href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">
        Pular para o conteúdo principal
    </a>

    <!-- Main Content Area -->
    <main id="main-content" role="main">