/**
 * ============================================================================
 * LUKRATO — SysAdmin Communications Page (Vite Module)
 * ============================================================================
 * Extraído de views/admin/sysadmin/communications.php
 *
 * Campaign CRUD, preview, pagination, detail modal.
 * ============================================================================
 */

// ================================================
// COMMUNICATIONS PAGE JS
// ================================================

// FA → Lucide icon mapping for dynamic icons
const faToLucide = {
    'fa-bullhorn': 'megaphone',
    'fa-bell': 'bell',
    'fa-paper-plane': 'send',
    'fa-envelope': 'mail',
    'fa-crown': 'crown',
    'fa-star': 'star',
    'fa-gift': 'gift',
    'fa-rocket': 'rocket',
    'fa-tag': 'tag',
    'fa-info-circle': 'info',
    'fa-exclamation-triangle': 'triangle-alert',
    'fa-check-circle': 'circle-check',
    'fa-users': 'users',
    'fa-chart-line': 'line-chart'
};

function lucideIcon(faClass) {
    return faToLucide[faClass] || faClass.replace('fa-', '');
}

const BASE = (() => {
    const meta = document.querySelector('meta[name="base-url"]')?.content || '';
    return meta.replace(/\/?$/, '/');
})();
let currentPage = 1;
let totalPages = 1;
let debounceTimer = null;

// Inicialização
document.addEventListener('DOMContentLoaded', () => {
    loadCampaigns();
    setupFormListeners();
    updatePreview();
});

// Setup listeners do formulário
function setupFormListeners() {
    // Contador de caracteres do título
    const titleInput = document.getElementById('campaignTitle');
    const titleCount = document.getElementById('titleCount');
    titleInput?.addEventListener('input', () => {
        titleCount.textContent = titleInput.value.length;
    });

    // Debounce nos filtros para atualizar preview
    document.querySelectorAll('.filter-input').forEach(el => {
        el.addEventListener('change', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updatePreview, 300);
        });
    });

    // Toggle de agendamento
    const scheduleCheckbox = document.getElementById('scheduleEnabled');
    const scheduleDateGroup = document.getElementById('scheduleDateTimeGroup');
    const btnSend = document.getElementById('btnSend');

    scheduleCheckbox?.addEventListener('change', () => {
        const isScheduled = scheduleCheckbox.checked;
        scheduleDateGroup.style.display = isScheduled ? 'block' : 'none';
        if (isScheduled) {
            btnSend.innerHTML = '<i data-lucide="calendar-clock"></i> Agendar Campanha';
            document.getElementById('scheduledAt').required = true;
        } else {
            btnSend.innerHTML = '<i data-lucide="send"></i> Enviar Campanha';
            document.getElementById('scheduledAt').required = false;
            document.getElementById('scheduledAt').value = '';
        }
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    // Form submit
    document.getElementById('campaignForm')?.addEventListener('submit', handleFormSubmit);
}

// Atualizar preview (contar destinatários)
async function updatePreview() {
    const previewCount = document.getElementById('recipientCount');
    previewCount.textContent = '...';

    try {
        const params = new URLSearchParams({
            plan: document.getElementById('filterPlan').value,
            status: document.getElementById('filterStatus').value,
            days_inactive: document.getElementById('filterDaysInactive').value || '',
        });

        const response = await fetch(`${BASE}api/campaigns/preview?${params}`, {
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            previewCount.textContent = data.data.count.toLocaleString('pt-BR');
        } else {
            previewCount.textContent = '?';
        }
    } catch (error) {
        console.error('Erro ao carregar preview:', error);
        previewCount.textContent = '?';
    }
}

// Carregar campanhas
async function loadCampaigns(page = 1) {
    const list = document.getElementById('campaignsList');
    list.innerHTML = `
        <div class="lk-loading-state">
            <i data-lucide="loader-2"></i>
            <span>Carregando campanhas...</span>
        </div>
    `;

    try {
        const response = await fetch(`${BASE}api/campaigns?page=${page}&per_page=10`, {
            headers: {
                'Accept': 'application/json',
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            renderCampaigns(data.data.campaigns);
            updatePagination(data.data.pagination);
            currentPage = page;
        } else {
            list.innerHTML = `
                <div class="empty-state">
                    <i data-lucide="circle-alert"></i>
                    <span>${data.message || 'Erro ao carregar campanhas'}</span>
                </div>
            `;
        }
    } catch (error) {
        console.error('Erro ao carregar campanhas:', error);
        list.innerHTML = `
            <div class="empty-state">
                <i data-lucide="circle-alert"></i>
                <span>Erro ao carregar campanhas</span>
            </div>
        `;
    }
}

// Renderizar lista de campanhas
function renderCampaigns(campaigns) {
    const list = document.getElementById('campaignsList');

    if (!campaigns || campaigns.length === 0) {
        list.innerHTML = `
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="megaphone"></i>
                </div>
                <h3>Nenhuma campanha criada</h3>
                <p>Crie sua primeira campanha para se comunicar com seus usuários.</p>
            </div>
        `;
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    list.innerHTML = campaigns.map(campaign => {
        const badge = campaign.status_badge;
        const isSpinning = campaign.status === 'sending' ? ' icon-spin' : '';

        // Timeline — mostra o fluxo agendamento → envio
        let timelineHtml = '';
        if (campaign.was_scheduled && campaign.sent_at) {
            timelineHtml = `
                <div class="campaign-timeline">
                    <span class="timeline-step"><i data-lucide="calendar-clock"></i> Agendada ${campaign.scheduled_at}</span>
                    <span class="timeline-arrow"><i data-lucide="arrow-right"></i></span>
                    <span class="timeline-step done"><i data-lucide="circle-check"></i> Enviada ${campaign.sent_at}</span>
                </div>`;
        } else if (campaign.was_scheduled && campaign.status === 'cancelled') {
            timelineHtml = `
                <div class="campaign-timeline">
                    <span class="timeline-step"><i data-lucide="calendar-clock"></i> Era ${campaign.scheduled_at}</span>
                    <span class="timeline-arrow"><i data-lucide="arrow-right"></i></span>
                    <span class="timeline-step cancelled"><i data-lucide="ban"></i> Cancelada</span>
                </div>`;
        }

        // Barra de progresso de emails
        let emailProgressHtml = '';
        if (campaign.send_email && (campaign.emails_sent > 0 || campaign.emails_failed > 0)) {
            const totalEmails = campaign.emails_sent + campaign.emails_failed;
            const successPct = totalEmails > 0 ? Math.round((campaign.emails_sent / totalEmails) * 100) : 0;
            emailProgressHtml = `
                <div class="campaign-email-progress">
                    <div class="progress-bar-mini">
                        <div class="progress-fill success" style="width: ${successPct}%"></div>
                    </div>
                    <span class="progress-label">
                        <i data-lucide="mail"></i> ${campaign.emails_sent} enviados${campaign.emails_failed > 0 ? ` · ${campaign.emails_failed} falharam` : ''}
                    </span>
                </div>`;
        }

        return `
        <div class="campaign-card" data-action="showCampaignDetail" data-campaign-id="${campaign.id}" style="--campaign-color: ${campaign.color}">
            <div class="campaign-card-header">
                <div class="campaign-icon" style="background-color: ${campaign.color}15; color: ${campaign.color}">
                    <i data-lucide="${lucideIcon(campaign.icon)}"></i>
                </div>
                <div class="campaign-info">
                    <h4 class="campaign-title">${escapeHtml(campaign.title)}</h4>
                    <div class="campaign-meta">
                        <span><i data-lucide="users"></i> ${campaign.total_recipients}</span>
                        <span><i data-lucide="eye"></i> ${campaign.read_rate}%</span>
                        <span><i data-lucide="calendar"></i> ${campaign.created_at}</span>
                    </div>
                </div>
                <div class="campaign-status-col">
                    <div class="campaign-status-badge" style="background-color: ${badge.color}15; color: ${badge.color}; border-color: ${badge.color}30">
                        ${badge.icon ? `<i data-lucide="${badge.icon}" class="${isSpinning}"></i>` : ''}
                        <span>${escapeHtml(badge.label)}</span>
                    </div>
                    ${campaign.is_scheduled ? `<button class="btn-cancel-schedule" data-action="cancelScheduled" data-campaign-id="${campaign.id}" title="Cancelar agendamento"><i data-lucide="x-circle"></i> Cancelar</button>` : ''}
                </div>
            </div>
            <div class="campaign-card-footer">
                ${timelineHtml}
                ${emailProgressHtml}
                <div class="campaign-tags">
                    <span class="tag"><i data-lucide="filter"></i> ${escapeHtml(campaign.filters_description)}</span>
                    <span class="tag"><i data-lucide="radio-tower"></i> ${escapeHtml(campaign.channels_description)}</span>
                </div>
            </div>
        </div>`;
    }).join('');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

// Atualizar paginação
function updatePagination(pagination) {
    const container = document.getElementById('paginationContainer');
    const pageInfo = document.getElementById('pageInfo');
    const btnPrev = document.getElementById('btnPrevPage');
    const btnNext = document.getElementById('btnNextPage');

    totalPages = pagination.total_pages;

    if (totalPages <= 1) {
        container.style.display = 'none';
        return;
    }

    container.style.display = 'flex';
    pageInfo.textContent = `Página ${pagination.current_page} de ${totalPages}`;
    btnPrev.disabled = pagination.current_page <= 1;
    btnNext.disabled = pagination.current_page >= totalPages;
}

// Mudar página
function changePage(delta) {
    const newPage = currentPage + delta;
    if (newPage >= 1 && newPage <= totalPages) {
        loadCampaigns(newPage);
    }
}

// Mostrar detalhes da campanha
async function showCampaignDetail(id) {
    const modal = new bootstrap.Modal(document.getElementById('campaignDetailModal'));
    const body = document.getElementById('campaignDetailBody');

    body.innerHTML = `
        <div class="text-center py-4">
            <i data-lucide="loader-2" class="icon-spin"></i>
        </div>
    `;
    modal.show();

    try {
        const response = await fetch(`${BASE}api/campaigns/${id}`, {
            headers: {
                'Accept': 'application/json'
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            const c = data.data;

            // Timeline steps
            let timelineHtml = `
                <div class="detail-timeline">
                    <div class="timeline-item active">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <span class="timeline-label">Criada</span>
                            <span class="timeline-date">${c.created_at}</span>
                        </div>
                    </div>`;

            if (c.scheduled_at) {
                const isScheduleActive = ['scheduled', 'sending', 'sent', 'partial'].includes(c.status);
                timelineHtml += `
                    <div class="timeline-item ${isScheduleActive ? 'active' : 'cancelled'}">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <span class="timeline-label">Agendada</span>
                            <span class="timeline-date">${c.scheduled_at}</span>
                        </div>
                    </div>`;
            }

            if (c.sent_at) {
                timelineHtml += `
                    <div class="timeline-item active done">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <span class="timeline-label">Enviada</span>
                            <span class="timeline-date">${c.sent_at}</span>
                        </div>
                    </div>`;
            } else if (c.status === 'cancelled') {
                timelineHtml += `
                    <div class="timeline-item cancelled">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <span class="timeline-label">Cancelada</span>
                        </div>
                    </div>`;
            }

            timelineHtml += '</div>';

            // Email progress bar for detail
            let emailDetailHtml = '';
            if (c.send_email && (c.emails_sent > 0 || c.emails_failed > 0)) {
                const totalEmails = c.emails_sent + c.emails_failed;
                const successPct = totalEmails > 0 ? Math.round((c.emails_sent / totalEmails) * 100) : 0;
                emailDetailHtml = `
                    <div class="detail-email-progress">
                        <div class="progress-header">
                            <span><i data-lucide="mail"></i> Progresso de E-mails</span>
                            <span>${successPct}% sucesso</span>
                        </div>
                        <div class="progress-bar-detail">
                            <div class="progress-fill success" style="width: ${successPct}%"></div>
                        </div>
                        <div class="progress-legend">
                            <span class="legend-success"><i data-lucide="circle-check"></i> ${c.emails_sent} enviados</span>
                            ${c.emails_failed > 0 ? `<span class="legend-fail"><i data-lucide="circle-x"></i> ${c.emails_failed} falharam</span>` : ''}
                        </div>
                    </div>`;
            }

            body.innerHTML = `
                <div class="campaign-detail">
                    <div class="detail-header" style="border-left: 4px solid ${c.color}">
                        <i data-lucide="${lucideIcon(c.icon)}" style="color: ${c.color}"></i>
                        <div>
                            <h4>${escapeHtml(c.title)}</h4>
                            <span class="detail-creator">Por ${escapeHtml(c.creator.nome)} em ${c.created_at}</span>
                        </div>
                        <div class="detail-status-badge" style="background-color: ${c.status_badge.color}15; color: ${c.status_badge.color}; border-color: ${c.status_badge.color}30">
                            ${c.status_badge.icon ? `<i data-lucide="${c.status_badge.icon}"></i>` : ''}
                            ${escapeHtml(c.status_badge.label)}
                        </div>
                    </div>

                    ${timelineHtml}

                    <div class="detail-message">
                        <strong>Mensagem:</strong>
                        <p>${escapeHtml(c.message).replace(/\n/g, '<br>')}</p>
                        ${c.link ? `<a href="${escapeHtml(c.link)}" target="_blank" class="detail-cta">${escapeHtml(c.link_text || 'Ver link')} <i data-lucide="external-link"></i></a>` : ''}
                    </div>

                    <div class="detail-stats">
                        <div class="stat">
                            <span class="stat-label">Destinatários</span>
                            <span class="stat-value">${c.total_recipients}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">Notificações Lidas</span>
                            <span class="stat-value">${c.notifications_read} <small>(${c.read_rate}%)</small></span>
                        </div>
                        ${c.send_email ? `
                        <div class="stat">
                            <span class="stat-label">E-mails OK</span>
                            <span class="stat-value">${c.emails_sent}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">E-mails Falhos</span>
                            <span class="stat-value ${c.emails_failed > 0 ? 'text-danger' : ''}">${c.emails_failed}</span>
                        </div>
                        ` : ''}
                    </div>

                    ${emailDetailHtml}

                    <div class="detail-meta">
                        <span><i data-lucide="filter"></i> ${c.filters_description}</span>
                        <span><i data-lucide="radio-tower"></i> ${c.channels_description}</span>
                    </div>
                </div>
            `;

            // Renderizar ícones Lucide no modal dinâmico
            if (typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            body.innerHTML = `<div class="text-danger">Erro ao carregar detalhes</div>`;
        }
    } catch (error) {
        console.error('Erro:', error);
        body.innerHTML = `<div class="text-danger">Erro ao carregar detalhes</div>`;
    }
}

// Enviar campanha
async function handleFormSubmit(e) {
    e.preventDefault();

    const btn = document.getElementById('btnSend');
    const originalText = btn.innerHTML;

    // Validações
    const title = document.getElementById('campaignTitle').value.trim();
    const message = document.getElementById('campaignMessage').value.trim();
    const sendNotification = document.getElementById('sendNotification').checked;
    const sendEmail = document.getElementById('sendEmail').checked;

    if (!title || !message) {
        LKFeedback.warning('Preencha o título e a mensagem da campanha.');
        return;
    }

    if (!sendNotification && !sendEmail) {
        LKFeedback.warning('Escolha pelo menos um canal de envio (Notificação ou E-mail).');
        return;
    }

    // Verificar agendamento
    const isScheduled = document.getElementById('scheduleEnabled').checked;
    const scheduledAt = isScheduled ? document.getElementById('scheduledAt').value : null;

    if (isScheduled && !scheduledAt) {
        LKFeedback.warning('Selecione a data e hora para o agendamento.');
        return;
    }

    // Confirmar envio
    const recipientCount = document.getElementById('recipientCount').textContent;
    const channelText = [sendNotification ? 'Notificação' : '', sendEmail ? 'E-mail' : ''].filter(Boolean).join(' + ');
    const confirmMsg = isScheduled
        ? `Campanha será agendada para ${new Date(scheduledAt).toLocaleString('pt-BR')}.\nDestinatários estimados: ${recipientCount}. Canais: ${channelText}`
        : `Você está prestes a enviar uma campanha para ${recipientCount} usuários. Canais: ${channelText}`;
    const result = await LKFeedback.confirm(confirmMsg, {
        title: isScheduled ? 'Confirmar agendamento?' : 'Confirmar envio?',
        icon: 'question',
        confirmButtonText: isScheduled ? 'Sim, agendar!' : 'Sim, enviar!',
        cancelButtonText: 'Cancelar'
    });

    if (!result.isConfirmed) return;

    // Enviar
    btn.disabled = true;
    btn.innerHTML = isScheduled
        ? '<i data-lucide="loader-2" class="icon-spin"></i> Agendando...'
        : '<i data-lucide="loader-2" class="icon-spin"></i> Enviando...';
    try {
        const payload = {
            title: title,
            message: message,
            type: document.getElementById('campaignType').value,
            link: document.getElementById('campaignLink').value || null,
            link_text: document.getElementById('campaignLinkText').value || null,
            send_notification: sendNotification,
            send_email: sendEmail,
            cupom_id: document.getElementById('campaignCupom')?.value || null,
            scheduled_at: scheduledAt || null,
            filters: {
                plan: document.getElementById('filterPlan').value,
                status: document.getElementById('filterStatus').value,
                days_inactive: document.getElementById('filterDaysInactive').value || null
            }
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

        const response = await fetch(`${BASE}api/campaigns`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin',
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (data.success) {
            if (data.data.scheduled_at) {
                LKFeedback.success(`Campanha agendada para ${data.data.scheduled_at}.`, { toast: true });
            } else {
                LKFeedback.success(`${data.data.total_recipients} usuários receberão sua mensagem.${data.data.emails_sent > 0 ? ` E-mails enviados: ${data.data.emails_sent}` : ''}${data.data.emails_failed > 0 ? ` E-mails com falha: ${data.data.emails_failed}` : ''}`, { toast: true });
            }

            // Limpar formulário
            document.getElementById('campaignForm').reset();
            document.getElementById('titleCount').textContent = '0';
            document.getElementById('sendNotification').checked = true;
            document.getElementById('scheduleEnabled').checked = false;
            document.getElementById('scheduleDateTimeGroup').style.display = 'none';
            btn.innerHTML = '<i data-lucide="send"></i> Enviar Campanha';

            // Atualizar lista
            loadCampaigns();
            updatePreview();
        } else {
            LKFeedback.error(data.message || 'Ocorreu um erro ao enviar a campanha.');
        }
    } catch (error) {
        console.error('Erro:', error);
        LKFeedback.error('Ocorreu um erro de conexão. Tente novamente.');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// Helper: escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ============================================================================
// CANCELAMENTO DE CAMPANHA AGENDADA
// ============================================================================

async function cancelScheduled(id) {
    const result = await LKFeedback.confirm('Deseja cancelar esta campanha agendada?', {
        title: 'Cancelar agendamento?',
        icon: 'warning',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Não'
    });

    if (!result.isConfirmed) return;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch(`${BASE}api/campaigns/${id}/cancel`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken
            },
            credentials: 'same-origin'
        });

        const data = await response.json();

        if (data.success) {
            LKFeedback.success('Campanha agendada cancelada.', { toast: true });
            loadCampaigns();
        } else {
            LKFeedback.error(data.message || 'Erro ao cancelar campanha.');
        }
    } catch (error) {
        console.error('Erro:', error);
        LKFeedback.error('Erro de conexão ao cancelar campanha.');
    }
}

// ============================================================================
// EVENT DELEGATION (substitui onclick handlers em módulos Vite)
// ============================================================================

document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-action]');
    if (!el) return;

    const action = el.dataset.action;

    switch (action) {
        case 'updatePreview': updatePreview(); break;
        case 'loadCampaigns': loadCampaigns(); break;
        case 'changePage': changePage(parseInt(el.dataset.delta)); break;
        case 'showCampaignDetail': showCampaignDetail(parseInt(el.dataset.campaignId)); break;
        case 'cancelScheduled':
            e.stopPropagation();
            cancelScheduled(parseInt(el.dataset.campaignId));
            break;
    }
});
