import { apiGet } from '../shared/api.js';

function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(() => {
        const originalIcon = button.innerHTML;
        button.innerHTML = '<i data-lucide="check"></i>';
        button.classList.add('copied');
        window.lucide?.createIcons?.();

        window.setTimeout(() => {
            button.innerHTML = originalIcon;
            button.classList.remove('copied');
            window.lucide?.createIcons?.();
        }, 2000);
    }).catch((error) => {
        console.error('Erro ao copiar:', error);
    });
}

async function loadReferralStats(context) {
    try {
        const response = await apiGet(`${context.API}referral/stats`);
        const stats = response?.data;
        if (!stats) {
            return;
        }

        const codeInput = document.getElementById('referral-code');
        const linkInput = document.getElementById('referral-link');
        if (codeInput) codeInput.value = stats.referral_code || '';
        if (linkInput) linkInput.value = stats.referral_link || '';

        const statTotal = document.getElementById('stat-total');
        const statCompleted = document.getElementById('stat-completed');
        const statDays = document.getElementById('stat-days');
        const limitCurrent = document.getElementById('limit-current');
        const limitMax = document.getElementById('limit-max');
        const barFill = document.getElementById('limit-bar-fill');
        const barHint = document.getElementById('limit-bar-hint');

        if (statTotal) statTotal.textContent = stats.total_indicacoes || 0;
        if (statCompleted) statCompleted.textContent = stats.indicacoes_completadas || 0;
        if (statDays) statDays.textContent = stats.dias_ganhos || 0;

        const current = stats.indicacoes_mes || 0;
        const max = stats.limite_mensal || 5;
        const remaining = stats.indicacoes_restantes ?? max;
        const percentage = Math.min((current / max) * 100, 100);

        if (limitCurrent) limitCurrent.textContent = current;
        if (limitMax) limitMax.textContent = max;

        if (barFill) {
            barFill.style.width = `${percentage}%`;
            if (percentage >= 100) {
                barFill.classList.add('full');
                barFill.classList.remove('warning');
            } else if (percentage >= 80) {
                barFill.classList.add('warning');
                barFill.classList.remove('full');
            } else {
                barFill.classList.remove('warning', 'full');
            }
        }

        if (barHint) {
            if (remaining === 0) {
                barHint.textContent = 'Limite atingido! Renova no proximo mes';
                barHint.classList.add('limit-reached');
            } else if (remaining === 1) {
                barHint.textContent = 'Ultima indicacao disponivel este mes';
                barHint.classList.remove('limit-reached');
            } else {
                barHint.textContent = `Voce pode indicar mais ${remaining} amigos este mes`;
                barHint.classList.remove('limit-reached');
            }
        }
    } catch (error) {
        console.error('Erro ao carregar estatisticas de indicacao:', error);
    }
}

export function initConfigReferral(context) {
    document.getElementById('btn-copy-code')?.addEventListener('click', () => {
        const code = document.getElementById('referral-code')?.value;
        const button = document.getElementById('btn-copy-code');
        if (code && button) {
            copyToClipboard(code, button);
        }
    });

    document.getElementById('btn-copy-link')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        const button = document.getElementById('btn-copy-link');
        if (link && button) {
            copyToClipboard(link, button);
        }
    });

    document.getElementById('btn-share-whatsapp')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        if (!link) {
            return;
        }

        const text = encodeURIComponent(`Use meu codigo e ganhe 7 dias de PRO gratis no Lukrato!\n\n${link}`);
        window.open(`https://wa.me/?text=${text}`, '_blank');
    });

    document.getElementById('btn-share-telegram')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        if (!link) {
            return;
        }

        const text = encodeURIComponent('Use meu codigo e ganhe 7 dias de PRO gratis no Lukrato!');
        window.open(`https://t.me/share/url?url=${encodeURIComponent(link)}&text=${text}`, '_blank');
    });

    document.getElementById('btn-share-instagram')?.addEventListener('click', () => {
        const link = document.getElementById('referral-link')?.value;
        if (!link) {
            return;
        }

        navigator.clipboard.writeText(link).then(() => {
            if (window.Swal) {
                Swal.fire({
                    icon: 'success',
                    title: 'Link copiado!',
                    text: 'Cole nos seus Stories ou Direct do Instagram.',
                    confirmButtonColor: '#e67e22',
                    timer: 3000,
                    timerProgressBar: true,
                });
            }
        });
    });

    if (document.getElementById('referral-stats')) {
        void loadReferralStats(context);
    }
}
