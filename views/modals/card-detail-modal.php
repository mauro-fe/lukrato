<!-- Modal de Detalhes do Cartão -->
<template id="cardDetailModalTemplate">
    <div class="card-detail-modal">
        <div class="card-detail-header">
            <div class="card-detail-header-content">
                <div class="card-detail-title-area">
                    <div class="card-detail-icon" data-color>
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div class="card-detail-info">
                        <h2 data-cartao-nome></h2>
                        <p data-periodo></p>
                    </div>
                </div>
                <button class="card-detail-close" onclick="window.LK_CardDetail?.close?.()">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="card-detail-stats-grid">
                <div class="stat-box">
                    <span class="stat-box-label">FATURA</span>
                    <span class="stat-box-value" data-fatura-total></span>
                </div>
                <div class="stat-box">
                    <span class="stat-box-label">LIMITE</span>
                    <span class="stat-box-value" data-limite></span>
                </div>
                <div class="stat-box">
                    <span class="stat-box-label">DISPONÍVEL</span>
                    <span class="stat-box-value" data-disponivel></span>
                </div>
                <div class="stat-box">
                    <span class="stat-box-label">UTILIZAÇÃO</span>
                    <span class="stat-box-value" data-utilizacao></span>
                </div>
            </div>
        </div>

        <div class="card-detail-body">
            <!-- Fatura do Mês -->
            <div class="detail-section">
                <div class="detail-section-header">
                    <h3><i class="fas fa-list"></i> Lançamentos do Mês</h3>
                    <span class="section-badge" data-lancamentos-count></span>
                </div>

                <div class="lancamentos-list-clean" data-lancamentos-list></div>

                <div class="summary-boxes">
                    <div class="summary-box">
                        <span class="summary-label">À Vista</span>
                        <span class="summary-value" data-a-vista></span>
                    </div>
                    <div class="summary-box">
                        <span class="summary-label">Parcelado</span>
                        <span class="summary-value" data-parcelado></span>
                    </div>
                    <div class="summary-box highlight">
                        <span class="summary-label">TOTAL</span>
                        <span class="summary-value" data-total></span>
                    </div>
                </div>

                <div class="comparison-box" data-comparison style="display: none;"></div>
            </div>

            <!-- Evolução Mensal e Impacto Futuro lado a lado -->
            <div class="detail-section-grid">
                <div class="detail-section detail-section-grid-item">
                    <div class="detail-section-header">
                        <i class="fas fa-chart-line"></i>
                        <h3>Evolução Mensal</h3>
                        <span class="tendencia-indicator" data-tendencia></span>
                    </div>

                    <div class="detail-chart-container">
                        <canvas id="evolutionChart"></canvas>
                    </div>

                    <p style="text-align: center; margin-top: 1rem; color: var(--color-text-muted);">
                        Média dos últimos 6 meses: <strong data-media></strong>
                    </p>

                </div>
                <!-- Impacto Futuro -->
                <div class="detail-section detail-section-grid-item">
                    <div class="detail-section-header">
                        <i class="fas fa-crystal-ball"></i>
                        <h3>Impacto Futuro</h3>
                    </div>

                    <div class="detail-chart-container">
                        <canvas id="impactChart"></canvas>
                    </div>


                </div>
            </div>
            <!-- Parcelamentos Ativos -->
            <div class="detail-section">
                <div class="detail-section-header">
                    <i class="fas fa-calendar-check"></i>
                    <h3>Parcelamentos Ativos</h3>
                    <span class="badge" data-comprometido style="display: none;"></span>
                </div>

                <div data-parcelamentos-content></div>
            </div>
            <div class="insights-section" data-insights style="display: none;"></div>

        </div>
    </div>
</template>