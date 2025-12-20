<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>

<style>
/* ==================== MODERN DASHBOARD ==================== */
.modern-dashboard {
    width: 100%;
    max-width: 100%;
}

/* KPI Grid */
.kpi-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    margin-bottom: 24px;
}

@media (min-width: 640px) {
    .kpi-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .kpi-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 24px;
        margin-bottom: 32px;
    }
}

/* Modern KPI Card */
.modern-kpi {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    -webkit-backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 24px;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    cursor: default;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.modern-kpi::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, 
        color-mix(in srgb, var(--color-primary) 12%, transparent) 0%,
        transparent 65%);
    opacity: 0;
    transition: opacity 0.5s ease;
    pointer-events: none;
}

.modern-kpi:hover::before {
    opacity: 1;
}

@media (min-width: 1024px) {
    .modern-kpi:hover {
        transform: translateY(-8px) scale(1.02);
        box-shadow: 0 24px 48px rgba(230, 126, 34, 0.12), 0 8px 16px rgba(0, 0, 0, 0.08);
        border-color: color-mix(in srgb, var(--color-primary) 40%, transparent);
    }
}

.modern-kpi:active {
    transform: scale(0.98);
}

.kpi-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.kpi-icon {
    width: 56px;
    height: 56px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    position: relative;
    transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
}

.modern-kpi:hover .kpi-icon {
    transform: scale(1.15) rotate(-8deg);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
}

.kpi-icon::before {
    content: '';
    position: absolute;
    inset: -3px;
    border-radius: 20px;
    padding: 2px;
    background: linear-gradient(135deg, currentColor, transparent);
    -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: xor;
    mask-composite: exclude;
    opacity: 0.4;
}

.kpi-icon.balance {
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    color: white;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

[data-theme="dark"] .kpi-icon.balance {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    box-shadow: 0 4px 16px rgba(37, 99, 235, 0.4);
}

.kpi-icon.income {
    background: linear-gradient(135deg, #10b981, #34d399);
    color: white;
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.3);
}

[data-theme="dark"] .kpi-icon.income {
    background: linear-gradient(135deg, #059669, #10b981);
    box-shadow: 0 4px 16px rgba(5, 150, 105, 0.4);
}

.kpi-icon.expense {
    background: linear-gradient(135deg, #ef4444, #f87171);
    color: white;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
}

[data-theme="dark"] .kpi-icon.expense {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    box-shadow: 0 4px 16px rgba(220, 38, 38, 0.4);
}

.kpi-label {
    font-size: 12px;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.8px;
    opacity: 0.8;
}

.kpi-value {
    font-size: 28px;
    font-weight: 800;
    color: var(--color-text);
    font-variant-numeric: tabular-nums;
    line-height: 1.2;
    background: linear-gradient(135deg, var(--color-text), color-mix(in srgb, var(--color-text) 70%, var(--color-primary)));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

@media (min-width: 1024px) {
    .kpi-value {
        font-size: 32px;
    }
}

.kpi-value.income {
    color: var(--color-success);
}

.kpi-value.expense {
    color: var(--color-danger);
}

.kpi-value.loading::after {
    content: '';
    display: inline-block;
    width: 14px;
    height: 14px;
    border: 2px solid currentColor;
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
    margin-left: 8px;
    opacity: 0.6;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Chart Section */
.chart-section {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    -webkit-backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.chart-section:hover {
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    border-color: color-mix(in srgb, var(--color-primary) 20%, var(--glass-border));
}

@media (min-width: 1024px) {
    .chart-section {
        padding: 32px;
        margin-bottom: 32px;
    }
}

.chart-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 24px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.chart-title::before {
    content: '';
    width: 5px;
    height: 28px;
    background: linear-gradient(180deg, #e67e22, #f39c12);
    border-radius: 6px;
    box-shadow: 0 2px 8px rgba(230, 126, 34, 0.3);
}

.chart-wrapper {
    position: relative;
    width: 100%;
    height: 280px;
}

@media (min-width: 640px) {
    .chart-wrapper {
        height: 320px;
    }
}

@media (min-width: 1024px) {
    .chart-wrapper {
        height: 380px;
    }
}

.chart-loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--glass-bg);
    border-radius: 12px;
}

.chart-loading::after {
    content: '';
    width: 48px;
    height: 48px;
    border: 4px solid var(--glass-border);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

/* Table Section */
.table-section {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    -webkit-backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
}

@media (min-width: 1024px) {
    .table-section {
        padding: 32px;
    }
}

.table-title {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.table-title::before {
    content: '';
    width: 4px;
    height: 24px;
    background: linear-gradient(180deg, var(--color-primary), var(--color-secondary));
    border-radius: 4px;
}

/* MOBILE: Cards */
.transactions-cards {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.table-wrapper {
    display: none;
}

.modern-table {
    display: none;
}

.transaction-card {
    background: var(--glass-bg);
    backdrop-filter: var(--glass-backdrop);
    -webkit-backdrop-filter: var(--glass-backdrop);
    border: 1px solid var(--glass-border);
    border-radius: 14px;
    padding: 16px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    cursor: pointer;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
}

.transaction-card:hover {
    background: color-mix(in srgb, var(--color-primary) 5%, var(--glass-bg));
    border-color: color-mix(in srgb, var(--color-primary) 30%, var(--glass-border));
    transform: translateX(-4px) translateY(-2px);
    box-shadow: 0 8px 20px rgba(230, 126, 34, 0.12);
}

.transaction-card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.transaction-date {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.transaction-value {
    font-size: 18px;
    font-weight: 800;
}

.transaction-value.receita {
    background: linear-gradient(135deg, #10b981, #34d399);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.transaction-value.despesa {
    background: linear-gradient(135deg, #ef4444, #f87171);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.transaction-card-body {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.transaction-info-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.transaction-label {
    font-size: 11px;
    font-weight: 700;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 70px;
}

.transaction-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
}

.transaction-badge.tipo-receita {
    background: color-mix(in srgb, var(--color-success) 15%, transparent);
    color: var(--color-success);
    border: 1px solid color-mix(in srgb, var(--color-success) 30%, transparent);
}

.transaction-badge.tipo-despesa {
    background: color-mix(in srgb, var(--color-danger) 15%, transparent);
    color: var(--color-danger);
    border: 1px solid color-mix(in srgb, var(--color-danger) 30%, transparent);
}

.transaction-text {
    font-size: 13px;
    color: var(--color-text);
    flex: 1;
}

.transaction-description {
    font-size: 13px;
    color: var(--color-text-muted);
    font-style: italic;
}

.transaction-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid var(--glass-border);
}

.transaction-btn {
    flex: 1;
    padding: 8px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 600;
    border: none;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
}

.transaction-btn-edit {
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.15), rgba(52, 152, 219, 0.08));
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.25);
}

.transaction-btn-edit:hover {
    background: linear-gradient(135deg, rgba(52, 152, 219, 0.25), rgba(52, 152, 219, 0.15));
    transform: translateY(-2px);
}

.transaction-btn-delete {
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.15), rgba(231, 76, 60, 0.08));
    color: var(--color-danger);
    border: 1px solid rgba(231, 76, 60, 0.25);
}

.transaction-btn-delete:hover {
    background: linear-gradient(135deg, rgba(231, 76, 60, 0.25), rgba(231, 76, 60, 0.15));
    transform: translateY(-2px);
}

/* DESKTOP: Tabela */
@media (min-width: 768px) {
    .transactions-cards {
        display: none !important;
    }

    .table-wrapper {
        display: block !important;
    }

    .modern-table {
        display: table !important;
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: var(--glass-bg);
        border-radius: 16px;
        overflow: hidden;
    }

    .modern-table thead th {
        padding: 16px;
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        color: var(--color-text-muted);
        text-transform: uppercase;
        letter-spacing: 0.8px;
        border-bottom: 2px solid var(--glass-border);
        background: color-mix(in srgb, var(--color-surface) 50%, transparent);
        white-space: nowrap;
    }

    .modern-table thead th:first-child {
        border-radius: 12px 0 0 0;
    }

    .modern-table thead th:last-child {
        border-radius: 0 12px 0 0;
    }

    .modern-table tbody td {
        padding: 16px;
        color: var(--color-text);
        font-size: 14px;
        border-bottom: 1px solid var(--glass-border);
        transition: background 0.2s ease;
    }

    .modern-table tbody tr:hover td {
        background: color-mix(in srgb, var(--color-primary) 5%, transparent);
    }

    .modern-table tbody tr:last-child td {
        border-bottom: none;
    }

    .modern-table tbody tr:last-child td:first-child {
        border-radius: 0 0 0 12px;
    }

    .modern-table tbody tr:last-child td:last-child {
        border-radius: 0 0 12px 0;
    }
    
    /* Badges de Tipo */
    .badge-tipo {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-tipo.receita {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.08));
        color: #10b981;
        border: 1px solid rgba(16, 185, 129, 0.25);
    }
    
    .badge-tipo.despesa {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(239, 68, 68, 0.08));
        color: #ef4444;
        border: 1px solid rgba(239, 68, 68, 0.25);
    }
    
    .badge-tipo.transferencia {
        background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(59, 130, 246, 0.08));
        color: #3b82f6;
        border: 1px solid rgba(59, 130, 246, 0.25);
    }
    
    /* Valores coloridos */
    .valor-cell {
        font-weight: 700;
        font-size: 15px;
    }
    
    .valor-cell.receita {
        color: #10b981;
    }
    
    .valor-cell.despesa {
        color: #ef4444;
    }
    
    /* Botões de Ação */
    .actions-cell {
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    
    .lk-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        padding: 8px 12px;
        border-radius: 8px;
        border: none;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }
    
    .lk-btn i {
        font-size: 14px;
    }
    
    .lk-btn.danger {
        background: #ef4444;
        color: #ffffff;
        border: 1px solid #dc2626;
    }
    
    .lk-btn.danger:hover {
        background: #dc2626;
        border-color: #b91c1c;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }
    
    .lk-btn.danger:active {
        transform: translateY(0);
    }
    
    /* Placeholder para categoria vazia */
    .categoria-empty {
        color: var(--color-text-muted);
        font-style: italic;
        opacity: 0.6;
    }
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 56px;
    color: var(--color-text-muted);
    opacity: 0.3;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--color-text);
    margin: 0 0 8px 0;
}

.empty-state p {
    font-size: 14px;
    color: var(--color-text-muted);
    margin: 0;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

/* ==================== GAMIFICAÇÃO ==================== */
.gamification-section {
    background: linear-gradient(135deg,
        color-mix(in srgb, var(--color-primary) 12%, var(--color-surface)),
        color-mix(in srgb, var(--color-secondary) 8%, var(--color-surface)));
    backdrop-filter: blur(20px);
    border: 1px solid color-mix(in srgb, var(--color-primary) 15%, var(--glass-border));
    border-radius: 20px;
    padding: 24px;
    margin-bottom: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(230, 126, 34, 0.08);
}

.gamification-section::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -30%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, 
        color-mix(in srgb, var(--color-primary) 15%, transparent),
        transparent 70%);
    border-radius: 50%;
    animation: float 8s ease-in-out infinite;
}

@keyframes float {
    0%, 100% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(-20px, -20px) scale(1.1); }
}

.gamification-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 24px;
    position: relative;
    z-index: 1;
}

.gamification-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 18px;
    font-weight: 700;
    color: var(--color-text);
}

.gamification-title i {
    font-size: 24px;
    color: var(--color-primary);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
}

.level-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    background: linear-gradient(135deg, #f59e0b, #f97316, #fb923c);
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    color: white;
    box-shadow: 0 4px 16px rgba(245, 158, 11, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    position: relative;
    z-index: 1;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.level-badge i {
    font-size: 16px;
    filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.2));
}

.gamification-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 16px;
    position: relative;
    z-index: 1;
}

@media (min-width: 640px) {
    .gamification-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .gamification-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 20px;
    }
}

/* Streak Card */
.streak-card {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.06));
    border: 2px solid rgba(239, 68, 68, 0.25);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(239, 68, 68, 0.15);
}

.streak-card::before {
    content: '🔥';
    position: absolute;
    font-size: 140px;
    opacity: 0.06;
    top: -30px;
    right: -30px;
    transform: rotate(15deg);
    animation: float 3s ease-in-out infinite;
}

.streak-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(239, 68, 68, 0.25);
    border-color: rgba(239, 68, 68, 0.4);
}

.streak-icon {
    font-size: 48px;
    margin-bottom: 12px;
    display: inline-block;
    animation: flame 1.5s ease-in-out infinite;
}

@keyframes flame {
    0%, 100% { transform: scale(1) rotate(-5deg); }
    50% { transform: scale(1.1) rotate(5deg); }
}

.streak-number {
    font-size: 42px;
    font-weight: 900;
    background: linear-gradient(135deg, #ef4444, #f97316);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    line-height: 1;
    margin-bottom: 8px;
    filter: drop-shadow(0 2px 4px rgba(239, 68, 68, 0.2));
}

.streak-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Progress Card */
.progress-card {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(37, 99, 235, 0.06));
    border: 2px solid rgba(59, 130, 246, 0.25);
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.15);
    transition: all 0.3s ease;
}

.progress-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.2);
}

.progress-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.progress-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--color-text);
    display: flex;
    align-items: center;
    gap: 8px;
}

.progress-title i {
    color: #3b82f6;
}

.progress-percentage {
    font-size: 24px;
    font-weight: 900;
    background: linear-gradient(135deg, #3b82f6, #60a5fa);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.progress-bar-container {
    height: 12px;
    background: rgba(59, 130, 246, 0.1);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 8px;
    position: relative;
}

.progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #60a5fa, #3b82f6);
    background-size: 200% 100%;
    border-radius: 10px;
    transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
    animation: gradientShift 3s ease infinite;
}

@keyframes gradientShift {
    0%, 100% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
}

.progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    animation: shimmer 2s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

.progress-text {
    font-size: 12px;
    color: var(--color-text-muted);
    text-align: center;
}

/* Badges Card */
.badges-card {
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.1), rgba(147, 51, 234, 0.05));
    border: 2px solid rgba(168, 85, 247, 0.2);
    border-radius: 16px;
    padding: 20px;
}

.badges-title {
    font-size: 14px;
    font-weight: 700;
    color: var(--color-text);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.badges-title i {
    color: #a855f7;
}

.badges-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
}

.badge-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 6px;
    padding: 12px 8px;
    background: rgba(168, 85, 247, 0.05);
    border-radius: 12px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
}

.badge-item:hover {
    transform: translateY(-2px);
    background: rgba(168, 85, 247, 0.1);
}

.badge-item.locked {
    opacity: 0.4;
    filter: grayscale(100%);
}

.badge-item.unlocked {
    animation: badgeUnlock 0.6s ease-out;
}

@keyframes badgeUnlock {
    0% { transform: scale(0.5) rotate(0deg); opacity: 0; }
    50% { transform: scale(1.2) rotate(10deg); }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

.badge-icon {
    font-size: 28px;
    line-height: 1;
}

.badge-name {
    font-size: 10px;
    font-weight: 600;
    color: var(--color-text);
    text-align: center;
    line-height: 1.2;
}

.badge-item .badge-check {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 16px;
    height: 16px;
    background: #22c55e;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    color: white;
}

/* Stats Row */
.stats-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
    margin-top: 20px;
    position: relative;
    z-index: 1;
}

@media (min-width: 640px) {
    .stats-row {
        grid-template-columns: repeat(4, 1fr);
    }
}

.stat-mini {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid var(--glass-border);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    transition: all 0.2s ease;
}

.stat-mini:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.08);
}

.stat-mini-value {
    font-size: 24px;
    font-weight: 800;
    color: var(--color-text);
    line-height: 1;
    margin-bottom: 6px;
}

.stat-mini-label {
    font-size: 11px;
    font-weight: 600;
    color: var(--color-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
</style>

<section class="modern-dashboard">
    <?php include BASE_PATH . '/views/admin/partials/header_mes.php'; ?>

    <!-- Gamificação -->
    <section class="gamification-section" data-aos="fade-up" data-aos-duration="500">
        <div class="gamification-header">
            <div class="gamification-title">
                <i class="fas fa-trophy"></i>
                <span>Seu Progresso</span>
            </div>
            <div class="level-badge" id="userLevel">
                <i class="fas fa-star"></i>
                <span>Nível 1</span>
            </div>
        </div>

        <div class="gamification-grid">
            <!-- Streak -->
            <div class="streak-card">
                <div class="streak-icon">🔥</div>
                <div class="streak-number" id="streakDays">0</div>
                <div class="streak-label">Dias Consecutivos</div>
            </div>

            <!-- Progresso -->
            <div class="progress-card">
                <div class="progress-header">
                    <div class="progress-title">
                        <i class="fas fa-chart-line"></i>
                        <span>Organização</span>
                    </div>
                    <div class="progress-percentage" id="organizationPercentage">0%</div>
                </div>
                <div class="progress-bar-container">
                    <div class="progress-bar" id="organizationBar" style="width: 0%"></div>
                </div>
                <div class="progress-text" id="organizationText">Continue registrando seus lançamentos!</div>
            </div>

            <!-- Badges -->
            <div class="badges-card">
                <div class="badges-title">
                    <i class="fas fa-medal"></i>
                    <span>Conquistas</span>
                </div>
                <div class="badges-grid" id="badgesGrid">
                    <div class="badge-item locked" title="Primeiro Passo: Adicione seu primeiro lançamento">
                        <div class="badge-icon">🎯</div>
                        <div class="badge-name">Início</div>
                    </div>
                    <div class="badge-item locked" title="Organizador: Complete 7 dias consecutivos">
                        <div class="badge-icon">📊</div>
                        <div class="badge-name">7 Dias</div>
                    </div>
                    <div class="badge-item locked" title="Disciplinado: Complete 30 dias consecutivos">
                        <div class="badge-icon">💎</div>
                        <div class="badge-name">30 Dias</div>
                    </div>
                    <div class="badge-item locked" title="Economista: Economize 10% em um mês">
                        <div class="badge-icon">💰</div>
                        <div class="badge-name">Economia</div>
                    </div>
                    <div class="badge-item locked" title="Planejador: Use 5 categorias diferentes">
                        <div class="badge-icon">🎨</div>
                        <div class="badge-name">Diverso</div>
                    </div>
                    <div class="badge-item locked" title="Mestre: Alcance 100 lançamentos">
                        <div class="badge-icon">👑</div>
                        <div class="badge-name">Mestre</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mini Stats -->
        <div class="stats-row">
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalLancamentos">0</div>
                <div class="stat-mini-label">Lançamentos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="totalCategorias">0</div>
                <div class="stat-mini-label">Categorias</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="mesesAtivos">0</div>
                <div class="stat-mini-label">Meses Ativos</div>
            </div>
            <div class="stat-mini">
                <div class="stat-mini-value" id="pontosTotal">0</div>
                <div class="stat-mini-label">Pontos</div>
            </div>
        </div>
    </section>

    <!-- KPI Cards -->
    <section class="kpi-grid" role="region" aria-label="Indicadores principais">
        <div data-aos="fade-up" data-aos-duration="500">
            <div class="modern-kpi" id="saldoCard">
                <div class="kpi-header">
                    <div class="kpi-icon balance">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <span class="kpi-label">Saldo Atual</span>
                </div>
                <div class="kpi-value loading" id="saldoValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="100">
            <div class="modern-kpi" id="receitasCard">
                <div class="kpi-header">
                    <div class="kpi-icon income">
                        <i class="fas fa-arrow-up"></i>
                    </div>
                    <span class="kpi-label">Receitas do Mês</span>
                </div>
                <div class="kpi-value income loading" id="receitasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="200">
            <div class="modern-kpi" id="despesasCard">
                <div class="kpi-header">
                    <div class="kpi-icon expense">
                        <i class="fas fa-arrow-down"></i>
                    </div>
                    <span class="kpi-label">Despesas do Mês</span>
                </div>
                <div class="kpi-value expense loading" id="despesasValue">R$ 0,00</div>
            </div>
        </div>

        <div data-aos="fade-up" data-aos-duration="500" data-aos-delay="300">
            <div class="modern-kpi" id="saldoMesCard">
                <div class="kpi-header">
                    <div class="kpi-icon balance">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <span class="kpi-label">Saldo do Mês</span>
                </div>
                <div class="kpi-value loading" id="saldoMesValue">R$ 0,00</div>
            </div>
        </div>
    </section>

    <!-- Chart -->
    <section class="chart-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="chart-title">Evolução Financeira</h2>
        <div class="chart-wrapper">
            <div class="chart-loading" id="chartLoading"></div>
            <canvas id="evolutionChart" role="img" aria-label="Gráfico de evolução do saldo"></canvas>
        </div>
    </section>

    <!-- Table -->
    <section class="table-section" data-aos="fade-up" data-aos-duration="500">
        <h2 class="table-title">Últimos Lançamentos</h2>

        <div class="empty-state" id="emptyState" style="display:none;">
            <div class="empty-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <h3>Nenhum lançamento encontrado</h3>
            <p>Comece adicionando sua primeira transação para acompanhar suas finanças</p>
        </div>

        <!-- Cards Mobile -->
        <div class="transactions-cards" id="transactionsCards"></div>

        <!-- Tabela Desktop -->
        <div class="table-wrapper">
            <table class="modern-table" id="transactionsTable">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Categoria</th>
                        <th>Conta</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th style="text-align: right;">Ações</th>
                    </tr>
                </thead>
                <tbody id="transactionsTableBody"></tbody>
            </table>
        </div>
    </section>
</section>
