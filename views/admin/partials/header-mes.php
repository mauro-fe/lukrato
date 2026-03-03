<?php
// CSS: public/assets/css/layout/header-month-picker.css (carregado via header.php)
// JS:  resources/js/admin/global/month-picker.js (carregado via Vite bundle)

use Application\Lib\Auth;

$headerMesUser = $currentUser ?? Auth::user();
$showHeaderMesCTA = !($headerMesUser && method_exists($headerMesUser, 'isPro') && $headerMesUser->isPro());

?>

<header class="dash-lk-header" data-aos="fade-up">
    <div class="header-left">
        <div class="month-selector">
            <div class="lk-period">
                <button class="month-nav-btn" id="prevMonth" type="button" aria-label="Mês anterior">
                    <i data-lucide="chevron-left"></i>
                </button>
                <button class="month-dropdown-btn" id="monthDropdownBtn" type="button" data-bs-toggle="modal"
                    data-bs-target="#monthModal" aria-haspopup="true" aria-expanded="false">
                    <span id="currentMonthText">Carregando...</span>
                    <i data-lucide="chevron-down"></i>
                </button>

                <div class="month-display">
                    <div class="month-dropdown" id="monthDropdown" role="menu"></div>
                </div>

                <button class="month-nav-btn" id="nextMonth" type="button" aria-label="Próximo mês">
                    <i data-lucide="chevron-right"></i>
                </button>
            </div>
            <div class="lk-year-picker" id="yearPicker" aria-hidden="true">
                <button class="month-nav-btn" id="prevYearBtn" type="button" aria-label="Ano anterior">
                    <i data-lucide="chevron-left"></i>
                </button>
                <button class="month-dropdown-btn year-btn" id="yearDropdownBtn" type="button" aria-haspopup="true"
                    aria-expanded="false">
                    <span id="currentYearText"><?= date('Y') ?></span>
                    <i data-lucide="chevron-down"></i>
                </button>
                <button class="month-nav-btn" id="nextYearBtn" type="button" aria-label="Próximo ano">
                    <i data-lucide="chevron-right"></i>
                </button>
            </div>
        </div>

</header>