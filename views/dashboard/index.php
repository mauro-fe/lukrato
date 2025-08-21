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

<main class="main-content max-height-vh-100 h-100 border-radius-lg">

    <div class="navbar navbar-main d-flex">
        <div class="container" style="padding:20px;color:#eaeaea;">
            <h2 style="margin-bottom:20px;">Dashboard</h2>

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