<?php

use Application\Lib\Helpers;
use Carbon\Carbon;

// Formatter de dinheiro (fallback se $fmt não vier do controller)
$fmt = $fmt ?? fn($v) => Helpers::formatMoneyBRL((float)($v ?? 0));

// Formatter de data dd/mm/yyyy (fallback se $fmtDate não vier do controller)
$fmtDate = $fmtDate ?? function ($date) {
    if ($date instanceof \DateTimeInterface) return $date->format('d/m/Y');
    if (!$date) return '—';
    try {
        return Carbon::parse($date)->format('d/m/Y');
    } catch (\Throwable $e) {
        return '—';
    }
};

// Garante arrays pro gráfico (evita erros no json_encode/array_map)
$labels = is_array($labels ?? null) ? array_values($labels) : [];
$data   = is_array($data   ?? null) ? array_map('floatval', $data) : [];
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lukrato</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --laranja-vibrante: #E67E22;
            --azul-noite: #2C3E50;
            --verde-claro: #2ECC71;
            --cinza-neutro: #BDC3C7;
            --amarelo-forte: #F39C12;
            --branco: #FFFFFF;
            --fundo-escuro: #092741;
            --fundo-claro: #F8F9FA;
            --texto-principal: #FFFFFF;
            --texto-secundario: #BDC3C7;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--fundo-escuro) 0%, #0B2A42 100%);
            color: var(--texto-principal);
            min-height: 100vh;
            line-height: 1.6;
        }

        .header {
            background: linear-gradient(135deg, var(--azul-noite) 0%, #34495e 100%);
            box-shadow: 0 8px 32px rgba(44, 62, 80, 0.3);
            padding: 1.5rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 2rem;
            font-weight: bold;
            color: var(--branco);
        }

        .logo i {
            background: linear-gradient(135deg, var(--laranja-vibrante), var(--amarelo-forte));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.25rem;
        }

        .header-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: var(--branco);
            font-weight: 500;
        }

        .main-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .welcome-section {
            margin-bottom: 2rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--branco);
            margin-bottom: 0.5rem;
        }

        .welcome-subtitle {
            color: var(--texto-secundario);
            font-size: 1.1rem;
        }

        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .kpi-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .kpi-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            background: rgba(255, 255, 255, 0.12);
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--laranja-vibrante), var(--amarelo-forte));
        }

        .kpi-card.receitas::before {
            background: linear-gradient(90deg, var(--verde-claro), #27AE60);
        }

        .kpi-card.despesas::before {
            background: linear-gradient(90deg, #E74C3C, #C0392B);
        }

        .kpi-card.saldo::before {
            background: linear-gradient(90deg, var(--azul-noite), #34495E);
        }

        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }

        .kpi-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--branco);
        }

        .kpi-icon.receitas {
            background: linear-gradient(135deg, var(--verde-claro), #27AE60);
        }

        .kpi-icon.despesas {
            background: linear-gradient(135deg, #E74C3C, #C0392B);
        }

        .kpi-icon.saldo {
            background: linear-gradient(135deg, var(--azul-noite), #34495E);
        }

        .kpi-label {
            font-size: 0.95rem;
            color: var(--texto-secundario);
            margin-bottom: 0.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .kpi-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--branco);
            margin-bottom: 1rem;
            line-height: 1;
        }

        .kpi-change {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .kpi-change.positive {
            color: var(--verde-claro);
        }

        .kpi-change.negative {
            color: #E74C3C;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .chart-card,
        .summary-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--branco);
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .card-title i {
            color: var(--laranja-vibrante);
        }

        .chart-container {
            position: relative;
            height: 350px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .summary-item:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--laranja-vibrante);
        }

        .summary-item:last-child {
            margin-bottom: 0;
        }

        .summary-label {
            font-weight: 500;
            color: var(--texto-secundario);
        }

        .summary-value {
            font-weight: 700;
            font-size: 1.1rem;
        }

        .transactions-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .transactions-table th {
            text-align: left;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.05);
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--texto-secundario);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .transactions-table th:first-child {
            border-radius: 12px 0 0 0;
        }

        .transactions-table th:last-child {
            border-radius: 0 12px 0 0;
        }

        .transactions-table td {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }

        .transactions-table tr:hover td {
            background: rgba(255, 255, 255, 0.05);
        }

        .tipo-badge {
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tipo-receita {
            background: rgba(46, 204, 113, 0.1);
            color: var(--verde-claro);
            border: 2px solid rgba(46, 204, 113, 0.3);
        }

        .tipo-despesa {
            background: rgba(231, 76, 60, 0.1);
            color: #E74C3C;
            border: 2px solid rgba(231, 76, 60, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--texto-secundario);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: var(--cinza-neutro);
        }

        .empty-state h4 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: var(--branco);
        }

        .action-button {
            background: linear-gradient(135deg, var(--laranja-vibrante), var(--amarelo-forte));
            color: var(--branco);
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(230, 126, 34, 0.4);
        }

        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
            }

            .kpi-grid {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 1rem;
            }

            .logo {
                font-size: 1.5rem;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .kpi-card {
                padding: 1.5rem;
            }

            .chart-card,
            .summary-card,
            .transactions-card {
                padding: 1.5rem;
            }

            .transactions-table th,
            .transactions-table td {
                padding: 1rem 0.75rem;
                font-size: 0.9rem;
            }
        }

        .loading-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }
        }

        .fade-in {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
                <span>Lukrato</span>
            </div>
            <div class="header-info">
                <div class="user-info">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard Financeiro</span>
                </div>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="welcome-section fade-in">
            <h1 class="welcome-title">Visão Geral Financeira</h1>
            <p class="welcome-subtitle">Acompanhe seus resultados e tome decisões inteligentes</p>
        </div>

        <!-- KPIs -->
        <div class="kpi-grid">
            <div class="kpi-card receitas fade-in">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-label">Receitas do Mês</div>
                        <div class="kpi-value"><?= $fmt($receitasMes) ?></div>
                        <div class="kpi-change positive">
                            <i class="fas fa-trending-up"></i>
                            <span>Entradas do período</span>
                        </div>
                    </div>
                    <div class="kpi-icon receitas">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                </div>
            </div>

            <div class="kpi-card despesas fade-in">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-label">Despesas do Mês</div>
                        <div class="kpi-value"><?= $fmt($despesasMes) ?></div>
                        <div class="kpi-change negative">
                            <i class="fas fa-trending-down"></i>
                            <span>Saídas do período</span>
                        </div>
                    </div>
                    <div class="kpi-icon despesas">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                </div>
            </div>

            <div class="kpi-card saldo fade-in">
                <div class="kpi-header">
                    <div>
                        <div class="kpi-label">Saldo Total</div>
                        <div class="kpi-value"><?= $fmt($saldoTotal) ?></div>
                        <div class="kpi-change <?= ($saldoTotal >= 0) ? 'positive' : 'negative' ?>">
                            <i class="fas fa-<?= ($saldoTotal >= 0) ? 'check-circle' : 'exclamation-circle' ?>"></i>
                            <span><?= ($saldoTotal >= 0) ? 'Saldo positivo' : 'Saldo negativo' ?></span>
                        </div>
                    </div>
                    <div class="kpi-icon saldo">
                        <i class="fas fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos e Resumo -->
        <div class="dashboard-grid">
            <div class="chart-card fade-in">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-chart-area"></i>
                        Evolução Financeira
                    </h3>
                    <button class="action-button">
                        <i class="fas fa-download"></i>
                        Exportar
                    </button>
                </div>
                <div class="chart-container">
                    <canvas id="financialChart"></canvas>
                </div>
            </div>

            <div class="summary-card fade-in">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-calculator"></i>
                        Resumo Mensal
                    </h3>
                </div>
                <div class="summary-item">
                    <span class="summary-label">
                        <i class="fas fa-plus-circle" style="color: var(--verde-claro);"></i>
                        Total de Receitas
                    </span>
                    <span class="summary-value" style="color: var(--verde-claro);"><?= $fmt($receitasMes) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">
                        <i class="fas fa-minus-circle" style="color: #E74C3C;"></i>
                        Total de Despesas
                    </span>
                    <span class="summary-value" style="color: #E74C3C;"><?= $fmt($despesasMes) ?></span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">
                        <i class="fas fa-equals" style="color: var(--laranja-vibrante);"></i>
                        Resultado do Mês
                    </span>
                    <span class="summary-value" style="color: <?= (($receitasMes - $despesasMes) >= 0) ? 'var(--verde-claro)' : '#E74C3C' ?>;">
                        <?= $fmt($receitasMes - $despesasMes) ?>
                    </span>
                </div>
                <div class="summary-item">
                    <span class="summary-label">
                        <i class="fas fa-piggy-bank" style="color: var(--azul-noite);"></i>
                        Saldo Acumulado
                    </span>
                    <span class="summary-value" style="color: <?= ($saldoTotal >= 0) ? 'var(--verde-claro)' : '#E74C3C' ?>;">
                        <?= $fmt($saldoTotal) ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Últimos Lançamentos -->
        <div class="transactions-card fade-in">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-history"></i>
                    Últimos Lançamentos
                </h3>
                <button class="action-button">
                    <i class="fas fa-plus"></i>
                    Novo Lançamento
                </button>
            </div>

            <?php if (!empty($ultimos) && count($ultimos)): ?>
                <div style="overflow-x: auto;">
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-calendar-alt"></i> Data</th>
                                <th><i class="fas fa-tag"></i> Tipo</th>
                                <th><i class="fas fa-folder-open"></i> Categoria</th>
                                <th><i class="fas fa-dollar-sign"></i> Valor</th>
                                <th><i class="fas fa-comment-alt"></i> Descrição</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos as $l): ?>
                                <tr>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($fmtDate($l->data)) ?></td>
                                    <td>
                                        <span class="tipo-badge tipo-<?= $l->tipo ?>">
                                            <i class="fas fa-<?= ($l->tipo === 'receita') ? 'arrow-up' : 'arrow-down' ?>"></i>
                                            <?= htmlspecialchars(($l->tipo === 'receita') ? 'Receita' : 'Despesa') ?>
                                        </span>
                                    </td>
                                    <td style="font-weight: 500;"><?= htmlspecialchars($l->categoria->nome ?? '—') ?></td>
                                    <td style="font-weight: 700; color: <?= ($l->tipo === 'receita') ? 'var(--verde-claro)' : '#E74C3C' ?>">
                                        <?= $fmt($l->valor) ?>
                                    </td>
                                    <td style="color: var(--texto-secundario);"><?= htmlspecialchars($l->descricao ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h4>Nenhum lançamento encontrado</h4>
                    <p>Comece adicionando suas primeiras receitas e despesas para acompanhar sua evolução financeira.</p>
                    <button class="action-button" style="margin-top: 1rem;">
                        <i class="fas fa-plus"></i>
                        Adicionar Primeiro Lançamento
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Dados do gráfico vindos do PHP
        window.dashboardChart = {
            labels: <?= json_encode(array_values($labels), JSON_UNESCAPED_UNICODE) ?>,
            data: <?= json_encode(array_map('floatval', $data), JSON_UNESCAPED_UNICODE) ?>
        };

        // Configuração do gráfico
        const ctx = document.getElementById('financialChart').getContext('2d');

        // Criar gradiente para o gráfico
        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
        gradient.addColorStop(0, 'rgba(230, 126, 34, 0.3)');
        gradient.addColorStop(1, 'rgba(230, 126, 34, 0.05)');

        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: window.dashboardChart.labels.length ? window.dashboardChart.labels : ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'],
                datasets: [{
                    label: 'Saldo',
                    data: window.dashboardChart.data.length ? window.dashboardChart.data : [1000, 1500, 1200, 1800, 2200, 1900],
                    borderColor: '#E67E22',
                    backgroundColor: gradient,
                    borderWidth: 4,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#E67E22',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 3,
                    pointRadius: 8,
                    pointHoverRadius: 12,
                    pointHoverBackgroundColor: '#F39C12',
                    pointHoverBorderColor: '#FFFFFF',
                    pointHoverBorderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#2C3E50',
                        titleColor: '#FFFFFF',
                        bodyColor: '#FFFFFF',
                        cornerRadius: 12,
                        padding: 15,
                        displayColors: false
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(189, 195, 199, 0.2)',
                            borderColor: 'rgba(189, 195, 199, 0.3)'
                        },
                        ticks: {
                            color: '#7F8C8D',
                            font: {
                                weight: '500'
                            }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(189, 195, 199, 0.2)',
                            borderColor: 'rgba(189, 195, 199, 0.3)'
                        },
                        ticks: {
                            color: '#7F8C8D',
                            font: {
                                weight: '500'
                            },
                            callback: function(value) {
                                return 'R$ ' + value.toLocaleString('pt-BR', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                elements: {
                    point: {
                        hoverRadius: 12
                    }
                }
            }
        });

        // Animações de entrada
        document.addEventListener('DOMContentLoaded', function() {
            const fadeElements = document.querySelectorAll('.fade-in');
            fadeElements.forEach((element, index) => {
                element.style.animationDelay = `${index * 0.1}s`;
            });
        });

        // Efeitos interativos
        document.querySelectorAll('.action-button').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                // Efeito de ripple
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;

                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.style.position = 'absolute';
                ripple.style.borderRadius = '50%';
                ripple.style.background = 'rgba(255, 255, 255, 0.5)';
                ripple.style.transform = 'scale(0)';
                ripple.style.animation = 'ripple 0.6s linear';
                ripple.style.pointerEvents = 'none';

                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);

                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // CSS para animação de ripple
        const style = document.createElement