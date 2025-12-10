<?php
$pageTitle = $pageTitle ?? 'Lukrato';
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Design system -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/variables.css">

    <!-- CSS da landing -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/site/landing.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>

    <header class="lk-site-header">
        <div class="lk-site-header-inner">

            <!-- Logo -->
            <a href="<?= BASE_URL ?>/" class="lk-site-logo">
                <img src="<?= BASE_URL ?>/assets/img/logo.png" alt="Lukrato" loading="lazy">
            </a>

            <!-- Menu desktop + mobile -->
            <div class="lk-site-menu">

                <!-- Navegação principal -->
                <nav class="lk-site-nav" aria-label="Navegação principal">
                    <a href="<?= BASE_URL ?>/funcionalidades" class="lk-site-nav-link">Funcionalidades</a>
                    <a href="<?= BASE_URL ?>/beneficios" class="lk-site-nav-link">Benefícios</a>
                    <a href="<?= BASE_URL ?>/planos" class="lk-site-nav-link">Planos</a>
                    <a href="<?= BASE_URL ?>/contato" class="lk-site-nav-link">Contato</a>
                </nav>

                <!-- Ações -->
                <div class="lk-site-actions">
                    <a href="<?= BASE_URL ?>/login" class="lk-site-login">
                        <i class="fa-regular fa-user"></i>
                        <span>Login</span>
                    </a>
                    <a href="<?= BASE_URL ?>/register" class="lk-site-cta">Começar grátis</a>
                </div>
            </div>

            <!-- Menu mobile hambúrguer -->
            <button class="lk-site-burger" type="button" aria-label="Abrir menu">
                <i class="fa-solid fa-bars"></i>
            </button>

        </div>
    </header>

    <main class="lk-site-main">