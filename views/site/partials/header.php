<?php
$pageTitle = $pageTitle ?? 'Lukrato – Controle Financeiro Pessoal Simples e Inteligente';

$pageDescription = $pageDescription ??
    'Controle suas finanças pessoais de forma simples. Organize gastos, crie orçamento, acompanhe despesas e tenha clareza sobre seu dinheiro. Comece grátis e evolua quando quiser.';

$pageKeywords = $pageKeywords ??
    'controle financeiro pessoal, controle de gastos, orçamento pessoal, finanças pessoais, como economizar dinheiro, aplicativo de controle financeiro';

$pageUrl = $pageUrl ?? BASE_URL . $_SERVER['REQUEST_URI'];

$pageImage = $pageImage ?? BASE_URL . 'assets/img/og-image.png'; // 1200x630px

$extraCss  = $extraCss ?? [];

$canonicalUrl = $canonicalUrl ?? $pageUrl;
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
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">

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

    <!-- Schema.org Markup (JSON-LD) -->
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
                "priceValidUntil": "2025-12-31"
            },
            "aggregateRating": {
                "@type": "AggregateRating",
                "ratingValue": "4.8",
                "ratingCount": "342"
            },
            "description": "<?= htmlspecialchars($pageDescription) ?>",
            "url": "<?= htmlspecialchars(BASE_URL) ?>",
            "image": "<?= htmlspecialchars($pageImage) ?>",
            "provider": {
                "@type": "Organization",
                "name": "Lukrato",
                "url": "<?= htmlspecialchars(BASE_URL) ?>",
                "logo": "<?= BASE_URL ?>assets/img/logo.png",
                "contactPoint": {
                    "@type": "ContactPoint",
                    "contactType": "Customer Service",
                    "availableLanguage": ["Portuguese"]
                },
                "address": {
                    "@type": "PostalAddress",
                    "addressLocality": "Nova Aurora",
                    "addressRegion": "PR",
                    "addressCountry": "BR"
                }
            },
            "featureList": [
                "Controle de gastos mensais",
                "Planejamento de orçamento pessoal",
                "Acompanhamento de despesas",
                "Metas financeiras",
                "Relatórios de gastos",
                "Lembretes de contas a pagar"
            ]
        }
    </script>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "Organization",
            "name": "Lukrato",
            "url": "<?= htmlspecialchars(BASE_URL) ?>",
            "logo": "<?= BASE_URL ?>assets/img/logo.png",
            "description": "Controle financeiro pessoal simples e gratuito para organizar suas finanças",
            "sameAs": [
                "https://www.facebook.com/lukrato",
                "https://www.instagram.com/lukrato",
                "https://www.linkedin.com/company/lukrato"
            ],
            "contactPoint": {
                "@type": "ContactPoint",
                "contactType": "Customer Service",
                "availableLanguage": ["Portuguese"],
                "areaServed": "BR"
            }
        }
    </script>

    <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "WebSite",
            "name": "Lukrato",
            "url": "<?= htmlspecialchars(BASE_URL) ?>",
            "potentialAction": {
                "@type": "SearchAction",
                "target": "<?= htmlspecialchars(BASE_URL) ?>busca?q={search_term_string}",
                "query-input": "required name=search_term_string"
            }
        }
    </script>

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
                        class="h-8 sm:h-16 transition-transform duration-300 group-hover:scale-110" loading="eager"
                        width="180" height="64" onerror="console.error('Logo não carregou:', this.src)">
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
                    <a href="<?= BASE_URL ?>login"
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
                <a href="<?= BASE_URL ?>login"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
                    aria-label="Começar a usar grátis">
                    <span>Começar grátis</span>
                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>