<?php
$headerBlogCategorias = $headerBlogCategorias ?? [];
$pageTitle = $pageTitle ?? 'Lukrato – Controle Financeiro Pessoal Simples e Inteligente | Grátis';

$pageDescription = $pageDescription ??
    'Organize suas finanças pessoais de forma simples e inteligente. O Lukrato é o melhor aplicativo gratuito de controle financeiro para gerenciar gastos, criar orçamentos, acompanhar despesas e ter clareza sobre seu dinheiro. Comece grátis agora!';

$pageKeywords = $pageKeywords ??
    'controle financeiro pessoal, aplicativo controle financeiro grátis, controle de gastos, orçamento pessoal, finanças pessoais, como economizar dinheiro, app financeiro, planilha de gastos, gerenciador financeiro, controle de despesas, organizar finanças, planejamento financeiro pessoal, app para controlar gastos, controle financeiro online, sistema financeiro pessoal';

// $pageUrl removed — unused (canonical handled by $canonicalUrl)

$pageImage = $pageImage ?? BASE_URL . 'assets/img/og-image.png'; // 1200x630px

$extraCss  = $extraCss ?? [];

$canonicalUrl = $canonicalUrl ?? rtrim(BASE_URL, '/') . '/';

// Page type (og:type override)
$pageType = $pageType ?? 'website';
$pageImageAlt = $pageImageAlt ?? 'Lukrato - Controle Financeiro Pessoal';

// Article OG meta (only for blog posts)
$articlePublishedTime = $articlePublishedTime ?? null;
$articleModifiedTime = $articleModifiedTime ?? null;
$articleSection = $articleSection ?? null;

// Breadcrumb data
$breadcrumbItems = $breadcrumbItems ?? [];

// Pagination prev/next (for category listing pages)
$paginationPrev = $paginationPrev ?? null;
$paginationNext = $paginationNext ?? null;

// Landing-only flag (controls which schemas are rendered)
$isLandingPage = $isLandingPage ?? false;
?>


<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <script>
        (function() {
            var t = localStorage.getItem('lukrato-theme');
            if (t !== 'light' && t !== 'dark') t = 'light';
            document.documentElement.setAttribute('data-theme', t);
            if (!localStorage.getItem('lukrato-theme')) localStorage.setItem('lukrato-theme', t);
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <!-- Primary Meta Tags -->
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($pageKeywords) ?>">
    <meta name="author" content="Lukrato">

    <!-- Facebook Domain Verification (WhatsApp Business API) -->
    <meta name="facebook-domain-verification"
        content="<?= htmlspecialchars($_ENV['FACEBOOK_DOMAIN_VERIFICATION'] ?? '') ?>">
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
    <link rel="alternate" hreflang="pt-br" href="<?= htmlspecialchars($canonicalUrl) ?>">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="<?= htmlspecialchars($pageType) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($pageImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= htmlspecialchars($pageImageAlt) ?>">
    <meta property="og:site_name" content="Lukrato">
    <meta property="og:locale" content="pt_BR">
    <?php if ($articlePublishedTime): ?>
        <meta property="article:published_time" content="<?= htmlspecialchars($articlePublishedTime) ?>">
    <?php endif; ?>
    <?php if ($articleModifiedTime): ?>
        <meta property="article:modified_time" content="<?= htmlspecialchars($articleModifiedTime) ?>">
    <?php endif; ?>
    <?php if ($articleSection): ?>
        <meta property="article:section" content="<?= htmlspecialchars($articleSection) ?>">
    <?php endif; ?>

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= htmlspecialchars($canonicalUrl) ?>">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($pageImage) ?>">
    <meta name="twitter:image:alt" content="<?= htmlspecialchars($pageImageAlt) ?>">

    <!-- Pagination -->
    <?php if ($paginationPrev): ?>
        <link rel="prev" href="<?= htmlspecialchars($paginationPrev) ?>">
    <?php endif; ?>
    <?php if ($paginationNext): ?>
        <link rel="next" href="<?= htmlspecialchars($paginationNext) ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="apple-touch-icon" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?= BASE_URL ?>assets/img/icone.png">
    <link rel="manifest" href="<?= BASE_URL ?>manifest.json">
    <meta name="msapplication-TileImage" content="<?= BASE_URL ?>assets/img/icone.png">

    <!-- DNS Prefetch & Preconnect -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="preconnect" href="https://unpkg.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">

    <!-- Tailwind CSS + base visual do site (Vite) -->
    <?= function_exists('vite_css') ? vite_css('site-app') : '' ?>
    <?= function_exists('vite_css') ? vite_css('site-base') : '' ?>

    <!-- Alpine.js CSP-compatible + Plugins -->
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/csp@3.x.x/dist/cdn.min.js"></script>

    <!-- AOS (Animate On Scroll) -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <?php foreach ($extraCss as $css): ?>
        <?= function_exists('vite_css') ? vite_css('site-' . (string) $css) : '' ?>
    <?php endforeach; ?>

    <!-- Lucide Icons + FA Brands (para ícones de marca) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.1/css/brands.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="<?= BASE_URL ?>assets/js/lucide.min.js"></script>

    <?php if ($isLandingPage): ?>
        <!-- Schema.org Markup (JSON-LD) - SoftwareApplication (landing only) -->
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
                "description": "<?= htmlspecialchars($pageDescription) ?>",
                "url": "<?= htmlspecialchars(BASE_URL) ?>",
                "image": "<?= htmlspecialchars($pageImage) ?>",
                "screenshot": "<?= BASE_URL ?>assets/img/mockups/dashboard.png",
                "softwareVersion": "2.0",
                "datePublished": "2024-01-01",
                "dateModified": "2026-03-04",
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
                "description": "Controle financeiro pessoal simples e gratuito para organizar suas finanças",
                "inLanguage": "pt-BR"
            }
        </script>

        <!-- Schema.org Markup (JSON-LD) - FAQPage (landing only) -->
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
    <?php endif; ?>

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
                            "name": "<?= htmlspecialchars($item['label'] ?? $item['name'] ?? '') ?>",
                            "item": "<?= htmlspecialchars($item['url'] ?? '') ?>"
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
    <!-- Preloader anti-FOUC -->
    <div id="lk-preloader">
        <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Carregando Lukrato..." width="120" height="42">
    </div>
    <script>
        // Esconde o preloader assim que o DOM e Tailwind estiverem prontos
        (function() {
            function hidePreloader() {
                var p = document.getElementById('lk-preloader');
                if (p) {
                    p.classList.add('hide');
                    setTimeout(function() {
                        p.remove();
                    }, 500);
                }
            }
            // Aguarda o DOMContentLoaded + pequeno delay para Tailwind processar
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(hidePreloader, 150);
                });
            } else {
                setTimeout(hidePreloader, 150);
            }
        })();
    </script>

    <!-- Header Premium -->
    <header x-data="{ scrolled: false }" @scroll.window="scrolled = window.scrollY > 50"
        :class="scrolled ? 'bg-white/80 dark:bg-[#1c2c3c]/80 backdrop-blur-xl shadow-[0_1px_3px_rgba(0,0,0,0.08)] dark:shadow-[0_1px_3px_rgba(0,0,0,0.3)] border-b border-gray-200/50 dark:border-white/10' : 'bg-transparent backdrop-blur-none border-b border-transparent'"
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-500 ease-out header-navbar" role="banner">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between transition-all duration-500"
                :class="scrolled ? 'h-16' : 'h-20'">
                <!-- Logo com animação -->
                <a href="<?= BASE_URL ?>" class="flex-shrink-0 group" aria-label="Lukrato - Página Inicial">
                    <img src="<?= BASE_URL ?>assets/img/logo.png" alt="Lukrato - Controle Financeiro Pessoal Gratuito"
                        title="Lukrato - Organize suas Finanças"
                        class="w-auto max-w-[120px] sm:max-w-none transition-all duration-500 group-hover:scale-105"
                        :class="scrolled ? 'h-7 sm:h-12' : 'h-8 sm:h-14'" loading="eager" width="180" height="64"
                        onerror="this.style.display='none'">
                </a>

                <!-- Desktop Navigation Premium -->
                <nav class="hidden lg:flex items-center gap-7" aria-label="Navegação principal" role="navigation">
                    <a href="<?= BASE_URL ?>#como-funciona"
                        class="relative font-semibold transition-all duration-300 group"
                        :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary' : 'text-gray-700 dark:text-gray-300 hover:text-primary'"
                        aria-label="Como funciona o Lukrato">
                        Como Funciona
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#funcionalidades"
                        class="relative font-semibold transition-all duration-300 group"
                        :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary' : 'text-gray-700 dark:text-gray-300 hover:text-primary'"
                        aria-label="Ver funcionalidades do app">
                        Funcionalidades
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#planos" class="relative font-semibold transition-all duration-300 group"
                        :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary' : 'text-gray-700 dark:text-gray-300 hover:text-primary'"
                        aria-label="Ver planos e preços">
                        Planos
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#faq" class="relative font-semibold transition-all duration-300 group"
                        :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary' : 'text-gray-700 dark:text-gray-300 hover:text-primary'"
                        aria-label="Perguntas frequentes">
                        FAQ
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>
                    <a href="<?= BASE_URL ?>#contato" class="relative font-semibold transition-all duration-300 group"
                        :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary' : 'text-gray-700 dark:text-gray-300 hover:text-primary'"
                        aria-label="Entre em contato conosco">
                        Contato
                        <span
                            class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                            aria-hidden="true"></span>
                    </a>

                    <!-- Aprenda Dropdown -->
                    <div class="relative" x-data="{ aprendaOpen: false }" @mouseenter="aprendaOpen = true"
                        @mouseleave="aprendaOpen = false">
                        <a href="<?= BASE_URL ?>blog"
                            class="relative font-semibold transition-all duration-300 group inline-flex items-center gap-1"
                            :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary' : 'text-gray-700 dark:text-gray-300 hover:text-primary'"
                            aria-label="Aprenda sobre finanças">
                            Aprenda
                            <i data-lucide="chevron-down" class="w-3.5 h-3.5 transition-transform duration-200"
                                :class="aprendaOpen ? 'rotate-180' : ''" aria-hidden="true"></i>
                            <span
                                class="absolute -bottom-1 left-0 w-0 h-0.5 bg-gradient-to-r from-primary to-orange-600 group-hover:w-full transition-all duration-300"
                                aria-hidden="true"></span>
                        </a>
                        <div x-show="aprendaOpen" x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-2"
                            class="absolute top-full left-1/2 -translate-x-1/2 mt-3 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 p-2 z-50"
                            x-cloak>
                            <?php foreach ($headerBlogCategorias as $cat): ?>
                                <a href="<?= BASE_URL ?>blog/categoria/<?= htmlspecialchars($cat->slug) ?>"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-orange-50 dark:hover:bg-white/5 transition-colors group/item">
                                    <?php if ($cat->icone): ?>
                                        <i data-lucide="<?= htmlspecialchars($cat->icone) ?>" class="w-4 h-4 text-primary"
                                            aria-hidden="true"></i>
                                    <?php endif; ?>
                                    <span
                                        class="text-sm font-medium text-gray-700 dark:text-gray-300 group-hover/item:text-primary transition-colors"><?= htmlspecialchars($cat->nome) ?></span>
                                </a>
                            <?php endforeach; ?>
                            <div class="border-t border-gray-100 dark:border-gray-700 mt-1 pt-1">
                                <a href="<?= BASE_URL ?>blog"
                                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-orange-50 dark:hover:bg-white/5 transition-colors group/item">
                                    <i data-lucide="book-open" class="w-4 h-4 text-primary" aria-hidden="true"></i>
                                    <span class="text-sm font-semibold text-primary">Ver todos os artigos</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Desktop Actions Premium -->
                <div class="hidden lg:flex items-center gap-3">
                    <!-- Theme Toggle -->
                    <button id="landingThemeToggle"
                        class="lk-theme-toggle relative w-10 h-10 flex items-center justify-center rounded-xl transition-all duration-300 overflow-hidden"
                        :class="scrolled ? 'text-gray-600 hover:bg-orange-50 dark:hover:bg-white/10' : 'text-gray-700 hover:bg-white/20 dark:hover:bg-white/10'"
                        type="button" aria-label="Alternar tema claro/escuro" title="Alternar tema">
                        <i data-lucide="sun" class="w-5 h-5" aria-hidden="true"></i>
                        <i data-lucide="moon" class="w-5 h-5" aria-hidden="true"></i>
                    </button>
                    <a href="<?= BASE_URL ?>login"
                        class="inline-flex items-center gap-2 px-4 py-2 font-semibold rounded-lg transition-all duration-300 group"
                        :class="scrolled ? 'text-gray-600 dark:text-gray-300 hover:text-primary hover:bg-orange-50 dark:hover:bg-white/10' : 'text-gray-700 dark:text-gray-300 hover:text-primary hover:bg-white/20 dark:hover:bg-white/10'"
                        aria-label="Entrar na sua conta">
                        <i data-lucide="user" class="text-sm transition-transform group-hover:scale-110"
                            aria-hidden="true"></i>
                        <span>Login</span>
                    </a>
                    <a href="<?= BASE_URL ?>login?tab=register"
                        class="inline-flex items-center gap-2 px-7 py-2.5 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-full shadow-lg shadow-orange-500/25 hover:shadow-xl hover:shadow-orange-500/40 hover:scale-105 active:scale-95 transition-all duration-300 group"
                        aria-label="Criar conta grátis">
                        <span>Criar conta grátis</span>
                        <i data-lucide="arrow-right" class="text-sm transition-transform group-hover:translate-x-1"
                            aria-hidden="true"></i>
                    </a>
                </div>

                <!-- Mobile Menu Button Premium -->
                <button @click="mobileMenuOpen = !mobileMenuOpen"
                    class="lg:hidden relative p-2.5 hover:text-primary hover:bg-orange-50 dark:hover:bg-white/10 rounded-xl transition-all duration-300"
                    :class="scrolled ? 'text-gray-700 dark:text-gray-300' : 'text-gray-700 dark:text-gray-300'"
                    type="button" aria-label="Abrir menu de navegação" aria-expanded="false"
                    :aria-expanded="mobileMenuOpen">
                    <i data-lucide="menu" class="text-2xl" x-show="!mobileMenuOpen" aria-hidden="true"></i>
                    <i data-lucide="x" class="text-2xl" x-show="mobileMenuOpen" x-cloak aria-hidden="true"></i>
                </button>
            </div>
        </div>
        <!-- Scroll Progress Bar -->
        <div class="absolute bottom-0 left-0 w-full h-[3px] bg-transparent">
            <div id="scrollProgressBar"
                class="h-full bg-gradient-to-r from-primary via-orange-500 to-orange-600 transition-none"
                style="width: 0%; will-change: width;"></div>
        </div>
    </header>

    <script>
        (function() {
            const bar = document.getElementById('scrollProgressBar');
            let ticking = false;
            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(function() {
                        const scrollTop = window.scrollY;
                        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
                        const progress = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
                        bar.style.width = progress + '%';
                        ticking = false;
                    });
                    ticking = true;
                }
            }, {
                passive: true
            });
        })();
    </script>

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
        class="fixed top-0 right-0 bottom-0 w-80 max-w-full bg-white dark:bg-[#1c2c3c] shadow-2xl z-[70] lg:hidden overflow-y-auto"
        x-cloak role="dialog" aria-modal="true" aria-label="Menu de navegação mobile">
        <div class="p-6">
            <!-- Header do Menu -->
            <div class="flex items-center justify-between mb-8">
                <!-- Mobile Theme Toggle -->
                <button id="landingThemeToggleMobile"
                    class="lk-theme-toggle relative w-10 h-10 flex items-center justify-center rounded-xl text-gray-600 dark:text-gray-300 hover:bg-orange-50 dark:hover:bg-white/10 transition-all duration-300 overflow-hidden"
                    type="button" aria-label="Alternar tema claro/escuro" title="Alternar tema">
                    <i data-lucide="sun" class="w-5 h-5" aria-hidden="true"></i>
                    <i data-lucide="moon" class="w-5 h-5" aria-hidden="true"></i>
                </button>
                <button @click="mobileMenuOpen = false"
                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/10 rounded-lg transition-colors"
                    type="button" aria-label="Fechar menu">
                    <i data-lucide="x" class="text-2xl" aria-hidden="true"></i>
                </button>
            </div>

            <!-- Navegação -->
            <nav class="flex flex-col gap-4 mb-6" role="navigation" aria-label="Menu mobile">
                <a href="<?= BASE_URL ?>#como-funciona" @click="mobileMenuOpen = false"
                    class="text-gray-700 dark:text-gray-200 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                    Como Funciona
                </a>
                <a href="<?= BASE_URL ?>#funcionalidades" @click="mobileMenuOpen = false"
                    class="text-gray-700 dark:text-gray-200 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                    Funcionalidades
                </a>
                <a href="<?= BASE_URL ?>#planos" @click="mobileMenuOpen = false"
                    class="text-gray-700 dark:text-gray-200 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                    Planos
                </a>
                <a href="<?= BASE_URL ?>#faq" @click="mobileMenuOpen = false"
                    class="text-gray-700 dark:text-gray-200 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                    FAQ
                </a>
                <a href="<?= BASE_URL ?>#contato" @click="mobileMenuOpen = false"
                    class="text-gray-700 dark:text-gray-200 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                    Contato
                </a>

                <!-- Aprenda (mobile accordion) -->
                <div x-data="{ aprendaMobileOpen: false }">
                    <button @click="aprendaMobileOpen = !aprendaMobileOpen"
                        class="w-full flex items-center justify-between text-gray-700 dark:text-gray-200 hover:text-primary font-medium py-3 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                        <span>Aprenda</span>
                        <i data-lucide="chevron-down" class="w-4 h-4 transition-transform duration-200"
                            :class="aprendaMobileOpen ? 'rotate-180' : ''" aria-hidden="true"></i>
                    </button>
                    <div x-show="aprendaMobileOpen" x-collapse class="ml-4 flex flex-col gap-1 mt-1">
                        <?php foreach ($headerBlogCategorias as $cat): ?>
                            <a href="<?= BASE_URL ?>blog/categoria/<?= htmlspecialchars($cat->slug) ?>"
                                @click="mobileMenuOpen = false"
                                class="text-sm text-gray-600 dark:text-gray-400 hover:text-primary py-2 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                                <?= htmlspecialchars($cat->nome) ?>
                            </a>
                        <?php endforeach; ?>
                        <a href="<?= BASE_URL ?>blog" @click="mobileMenuOpen = false"
                            class="text-sm text-primary font-semibold py-2 px-4 rounded-lg hover:bg-orange-50 dark:hover:bg-white/10 transition-colors">
                            Ver todos os artigos
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Botões de Ação -->
            <div class="flex flex-col gap-3 pt-4 border-t border-gray-100 dark:border-white/10">
                <a href="<?= BASE_URL ?>login"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 text-gray-700 dark:text-gray-200 hover:text-primary font-semibold border-2 border-gray-200 dark:border-white/20 rounded-xl hover:border-primary transition-all duration-300"
                    aria-label="Entrar">
                    <i data-lucide="user" aria-hidden="true"></i>
                    <span>Login</span>
                </a>
                <a href="<?= BASE_URL ?>login?tab=register"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-gradient-to-r from-primary via-orange-500 to-orange-600 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:scale-105 transition-all duration-300"
                    aria-label="Criar conta grátis">
                    <span>Criar conta grátis</span>
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
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