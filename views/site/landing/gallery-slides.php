<?php

$landingMockupBaseUrl = rtrim(BASE_URL, '/') . '/assets/img/mockups';

$buildLandingThemeSlide = static function (string $slug, string $eyebrow, string $title, string $desc) use ($landingMockupBaseUrl): array {
    return [
        'src' => $landingMockupBaseUrl . '/' . $slug . '-light.png',
        'darkSrc' => $landingMockupBaseUrl . '/' . $slug . '-dark.png',
        'eyebrow' => $eyebrow,
        'title' => $title,
        'desc' => $desc,
    ];
};

$landingGallerySlides = [
    $buildLandingThemeSlide(
        'dashboard',
        'Visão geral',
        'Dashboard',
        'Saldo, receitas, despesas e alertas em uma única tela para decidir rápido.'
    ),
    $buildLandingThemeSlide(
        'contas',
        'Contas',
        'Saldos organizados',
        'Banco, carteira e reserva separados para você saber onde o dinheiro está.'
    ),
    $buildLandingThemeSlide(
        'transacoes',
        'Transações',
        'Lançamentos no radar',
        'Entradas e saídas organizadas para acompanhar tudo sem se perder no dia a dia.'
    ),
    $buildLandingThemeSlide(
        'relatorios',
        'Relatórios',
        'Clareza visual',
        'Gráficos e comparativos para entender o mês e corrigir rota.'
    ),
];

$landingGalleryCount = count($landingGallerySlides);
$landingGalleryGridClass = match (true) {
    $landingGalleryCount <= 1 => 'grid-cols-1',
    $landingGalleryCount === 2 => 'sm:grid-cols-2',
    $landingGalleryCount === 3 => 'sm:grid-cols-2 lg:grid-cols-3',
    $landingGalleryCount === 4 => 'sm:grid-cols-2 lg:grid-cols-4',
    default => 'sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5',
};
