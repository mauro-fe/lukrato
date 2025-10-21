<style>
    /* Header / Month selector */
    .dashboard-page .dash-lk-header {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: var(--spacing-5);
    }

    .dashboard-page .dash-lk-header .month-selector {
        display: flex;
        align-items: center;
        gap: var(--spacing-2);
        flex-wrap: wrap;
    }

    .lk-period {
        gap: 0 !important;
    }

    .dash-lk-header .month-nav-btn,
    .dash-lk-header .month-dropdown-btn {
        background: none;
        border: 0;
        cursor: pointer;
        border-radius: var(--radius-sm);
        transition: var(--transition-fast);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--branco);
    }

    .dash-lk-header .month-nav-btn {
        width: 32px;
        height: 32px;
    }

    .dash-lk-header .month-nav-btn:hover,
    .dash-lk-header .month-dropdown-btn:hover {
        background-color: var(--glass-bg);
    }

    .dash-lk-header .month-display {
        position: relative;
    }

    .dash-lk-header .month-dropdown-btn {
        gap: var(--spacing-2);
        font-weight: 600;
        min-width: 160px;
        color: var(--color-primary);
    }

    /* Month dropdown */
    .dash-lk-header .month-dropdown {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        width: min(260px, calc(100vw - 48px));
        background: var(--azul);
        border: 1px solid var(--glass-border);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        z-index: 1000;
        max-height: 320px;
        overflow-y: auto;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-8px);
        transition: var(--transition-fast);
        padding: 6px;
    }

    .dash-lk-header .month-dropdown.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .dash-lk-header .month-dropdown .year-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        padding: 4px 0 8px;
    }

    .dash-lk-header .month-dropdown .year-label {
        grid-column: 1 / -1;
        font-weight: 700;
        color: var(--claro);
        padding: 6px 8px;
        opacity: 0.85;
    }

    .dash-lk-header .month-dropdown .m-btn {
        background: none;
        border: 1px solid var(--glass-border);
        color: var(--branco);
        padding: 8px 10px;
        border-radius: var(--radius-sm);
        cursor: pointer;
        transition: var(--transition-fast);
        text-align: center;
        font-size: var(--font-size-sm);
    }

    .dash-lk-header .month-dropdown .m-btn:hover {
        background-color: var(--glass-bg);
    }

    .dash-lk-header .month-dropdown .m-btn.is-current {
        border-color: var(--laranja);
        box-shadow: 0 0 0 1px var(--laranja) inset;
    }
</style>

<header class="dash-lk-header" data-aos="fade-up">
    <div class="header-left">
        <div class="month-selector">
            <div class="lk-period">
                <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="month-dropdown-btn" id="monthDropdownBtn" type="button" data-bs-toggle="modal"
                    data-bs-target="#monthModal" aria-haspopup="true" aria-expanded="false">
                    <span id="currentMonthText">Carregando...</span>
                    <i class="fas fa-chevron-down"></i>
                </button>

                <div class="month-display">
                    <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                </div>

                <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Próximo mês">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
    </div>
</header>