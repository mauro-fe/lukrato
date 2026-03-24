import{b as F,c as w,l as v,e as x,d as _}from"./api-Dkfcp6ON.js";import{e as c,d as M}from"./utils-Bj4jxwhy.js";const f=F(),H={"fa-bullhorn":"megaphone","fa-bell":"bell","fa-paper-plane":"send","fa-envelope":"mail","fa-crown":"crown","fa-star":"star","fa-gift":"gift","fa-rocket":"rocket","fa-tag":"tag","fa-info-circle":"info","fa-exclamation-triangle":"triangle-alert","fa-check-circle":"circle-check","fa-users":"users","fa-chart-line":"line-chart"};let B=1,g=1,S=!1,$=0,b=0;function N(a){return H[a]||String(a||"").replace("fa-","")}function r(){typeof lucide<"u"&&lucide.createIcons()}function R(a,t=140){const e=String(a||"").trim();return e.length<=t?e:`${e.slice(0,t-1).trimEnd()}...`}function k(a){const t=document.querySelector('.btn-refresh[data-action="loadCampaigns"]');t&&(t.disabled=a,t.classList.toggle("is-loading",a),t.innerHTML=a?'<i data-lucide="loader-2" class="icon-spin"></i>':'<i data-lucide="refresh-cw"></i>',r())}function I(){const a=document.getElementById("scheduleEnabled"),t=document.getElementById("scheduleDateTimeGroup"),e=document.getElementById("scheduledAt"),s=document.getElementById("btnSend");if(!a||!t||!e||!s)return;const i=a.checked;t.style.display=i?"block":"none",e.required=i,i||(e.value=""),s.innerHTML=i?'<i data-lucide="calendar-clock"></i> Agendar Campanha':'<i data-lucide="send"></i> Enviar Campanha',r()}function E(a){const t=document.getElementById("historySummary");if(!t)return;const e=Number(a?.total||0),s=Number(a?.current_page||1),i=Number(a?.total_pages||1);if(e<=0){t.textContent="Acompanhe status, entrega e segmentacao das ultimas campanhas.";return}t.textContent=`${e.toLocaleString("pt-BR")} campanha(s) registradas • pagina ${s} de ${i}.`}async function y(){const a=document.getElementById("recipientCount");if(!a)return;const t=++$;a.textContent="...",a.closest(".preview-count")?.classList.remove("is-error");try{const e=await _(`${f}api/campaigns/preview`,{plan:document.getElementById("filterPlan")?.value||"",status:document.getElementById("filterStatus")?.value||"",days_inactive:document.getElementById("filterDaysInactive")?.value||""});if(t!==$)return;const s=e?.data??e;if(e?.success===!1){a.textContent="?",a.closest(".preview-count")?.classList.add("is-error");return}a.textContent=Number(s?.count||0).toLocaleString("pt-BR")}catch(e){if(t!==$)return;v("[Communications] Erro ao carregar preview",e,"Falha ao calcular destinatarios"),a.textContent="?",a.closest(".preview-count")?.classList.add("is-error")}}function D(a){const t=document.getElementById("campaignsList");t&&(t.innerHTML=`
        <div class="empty-state">
            <div class="empty-state-icon">
                <i data-lucide="megaphone"></i>
            </div>
            <h3>Nenhuma campanha criada</h3>
            <p>${c(a)}</p>
        </div>
    `,r())}function K(a){const t=document.getElementById("campaignsList");if(t){if(!a||a.length===0){D("Crie sua primeira campanha para se comunicar com seus usuarios.");return}t.innerHTML=a.map(e=>{const s=e.status_badge||{},i=e.status==="sending"?" icon-spin":"",n=R(e.message,132);let d="";e.was_scheduled&&e.sent_at?d=`
                <div class="campaign-timeline">
                    <span class="timeline-step"><i data-lucide="calendar-clock"></i> Agendada ${e.scheduled_at}</span>
                    <span class="timeline-arrow"><i data-lucide="arrow-right"></i></span>
                    <span class="timeline-step done"><i data-lucide="circle-check"></i> Enviada ${e.sent_at}</span>
                </div>`:e.was_scheduled&&e.status==="cancelled"&&(d=`
                <div class="campaign-timeline">
                    <span class="timeline-step"><i data-lucide="calendar-clock"></i> Era ${e.scheduled_at}</span>
                    <span class="timeline-arrow"><i data-lucide="arrow-right"></i></span>
                    <span class="timeline-step cancelled"><i data-lucide="ban"></i> Cancelada</span>
                </div>`);let o="";if(e.send_email&&(e.emails_sent>0||e.emails_failed>0)){const l=Number(e.emails_sent||0)+Number(e.emails_failed||0);o=`
                <div class="campaign-email-progress">
                    <div class="progress-bar-mini">
                        <div class="progress-fill success" style="width: ${l>0?Math.round(Number(e.emails_sent||0)/l*100):0}%"></div>
                    </div>
                    <span class="progress-label">
                        <i data-lucide="mail"></i>
                        ${Number(e.emails_sent||0).toLocaleString("pt-BR")} enviados${e.emails_failed>0?` • ${Number(e.emails_failed).toLocaleString("pt-BR")} falharam`:""}
                    </span>
                </div>`}return`
            <div class="campaign-card" data-action="showCampaignDetail" data-campaign-id="${e.id}" style="--campaign-color: ${e.color}">
                <div class="campaign-card-header">
                    <div class="campaign-icon" style="background-color: ${e.color}15; color: ${e.color}">
                        <i data-lucide="${N(e.icon)}"></i>
                    </div>
                    <div class="campaign-info">
                        <h4 class="campaign-title">${c(e.title)}</h4>
                        <p class="campaign-excerpt">${c(n)}</p>
                        <div class="campaign-meta">
                            <span><i data-lucide="users"></i> ${Number(e.total_recipients||0).toLocaleString("pt-BR")}</span>
                            <span><i data-lucide="eye"></i> ${Number(e.read_rate||0)}%</span>
                            <span><i data-lucide="shield"></i> ${c(e.creator_name||"Sistema")}</span>
                            <span><i data-lucide="calendar"></i> ${c(e.created_at||"-")}</span>
                        </div>
                    </div>
                    <div class="campaign-status-col">
                        <div class="campaign-status-badge" style="background-color: ${s.color}15; color: ${s.color}; border-color: ${s.color}30">
                            ${s.icon?`<i data-lucide="${s.icon}" class="${i}"></i>`:""}
                            <span>${c(s.label||"Sem status")}</span>
                        </div>
                        ${e.is_scheduled?`<button class="btn-cancel-schedule" data-action="cancelScheduled" data-campaign-id="${e.id}" title="Cancelar agendamento"><i data-lucide="x-circle"></i> Cancelar</button>`:""}
                    </div>
                </div>
                <div class="campaign-card-footer">
                    ${d}
                    ${o}
                    <div class="campaign-tags">
                        <span class="tag"><i data-lucide="filter"></i> ${c(e.filters_description||"Sem filtros")}</span>
                        <span class="tag"><i data-lucide="radio-tower"></i> ${c(e.channels_description||"Sem canais")}</span>
                    </div>
                </div>
            </div>`}).join(""),r()}}function A(a){const t=document.getElementById("paginationContainer"),e=document.getElementById("pageInfo"),s=document.getElementById("btnPrevPage"),i=document.getElementById("btnNextPage");if(!(!t||!e||!s||!i)){if(g=Number(a?.total_pages||1),g<=1){t.style.display="none";return}t.style.display="flex",e.textContent=`Pagina ${a.current_page} de ${g}`,s.disabled=a.current_page<=1,i.disabled=a.current_page>=g}}async function h(a=1){const t=document.getElementById("campaignsList");if(!t)return;const e=++b;k(!0),t.innerHTML=`
        <div class="lk-loading-state">
            <i data-lucide="loader-2" class="icon-spin"></i>
            <span>Carregando campanhas...</span>
        </div>
    `,r();try{const s=await _(`${f}api/campaigns`,{page:a,per_page:10});if(e!==b)return;if(s?.success===!1){const n=s?.message||"Erro ao carregar campanhas.";t.innerHTML=`
                <div class="empty-state">
                    <i data-lucide="circle-alert"></i>
                    <span>${c(n)}</span>
                </div>
            `,E(null),r();return}const i=s?.data??s;K(i?.campaigns||[]),A(i?.pagination||null),E(i?.pagination||null),B=a}catch(s){if(e!==b)return;v("[Communications] Erro ao carregar campanhas",s,"Falha ao carregar campanhas"),t.innerHTML=`
            <div class="empty-state">
                <i data-lucide="circle-alert"></i>
                <span>Erro ao carregar campanhas</span>
            </div>
        `,E(null),r()}finally{e===b&&k(!1)}}function q(a){const t=B+a;t>=1&&t<=g&&h(t)}async function O(a){const t=document.getElementById("campaignDetailModal"),e=document.getElementById("campaignDetailBody");if(!t||!e)return;const s=new bootstrap.Modal(t);e.innerHTML=`
        <div class="text-center py-4">
            <i data-lucide="loader-2" class="icon-spin"></i>
        </div>
    `,s.show(),r();try{const i=await _(`${f}api/campaigns/${a}`);if(i?.success===!1){e.innerHTML='<div class="text-danger">Erro ao carregar detalhes</div>';return}const n=i?.data??i;let d=`
            <div class="detail-timeline">
                <div class="timeline-item active">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Criada</span>
                        <span class="timeline-date">${c(n.created_at||"-")}</span>
                    </div>
                </div>`;if(n.scheduled_at){const l=["scheduled","sending","sent","partial"].includes(n.status);d+=`
                <div class="timeline-item ${l?"active":"cancelled"}">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Agendada</span>
                        <span class="timeline-date">${c(n.scheduled_at)}</span>
                    </div>
                </div>`}n.sent_at?d+=`
                <div class="timeline-item active done">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Enviada</span>
                        <span class="timeline-date">${c(n.sent_at)}</span>
                    </div>
                </div>`:n.status==="cancelled"&&(d+=`
                <div class="timeline-item cancelled">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <span class="timeline-label">Cancelada</span>
                    </div>
                </div>`),d+="</div>";let o="";if(n.send_email&&(n.emails_sent>0||n.emails_failed>0)){const l=Number(n.emails_sent||0)+Number(n.emails_failed||0),u=l>0?Math.round(Number(n.emails_sent||0)/l*100):0;o=`
                <div class="detail-email-progress">
                    <div class="progress-header">
                        <span><i data-lucide="mail"></i> Progresso de E-mails</span>
                        <span>${u}% sucesso</span>
                    </div>
                    <div class="progress-bar-detail">
                        <div class="progress-fill success" style="width: ${u}%"></div>
                    </div>
                    <div class="progress-legend">
                        <span class="legend-success"><i data-lucide="circle-check"></i> ${Number(n.emails_sent||0).toLocaleString("pt-BR")} enviados</span>
                        ${n.emails_failed>0?`<span class="legend-fail"><i data-lucide="circle-x"></i> ${Number(n.emails_failed).toLocaleString("pt-BR")} falharam</span>`:""}
                    </div>
                </div>`}e.innerHTML=`
            <div class="campaign-detail">
                <div class="detail-header" style="border-left: 4px solid ${n.color}">
                    <i data-lucide="${N(n.icon)}" style="color: ${n.color}"></i>
                    <div>
                        <h4>${c(n.title)}</h4>
                        <span class="detail-creator">Por ${c(n.creator?.nome||"Sistema")} em ${c(n.created_at||"-")}</span>
                    </div>
                    <div class="detail-status-badge" style="background-color: ${n.status_badge.color}15; color: ${n.status_badge.color}; border-color: ${n.status_badge.color}30">
                        ${n.status_badge.icon?`<i data-lucide="${n.status_badge.icon}"></i>`:""}
                        ${c(n.status_badge.label)}
                    </div>
                </div>

                ${d}

                <div class="detail-message">
                    <strong>Mensagem:</strong>
                    <p>${c(n.message).replace(/\n/g,"<br>")}</p>
                    ${n.link?`<a href="${c(n.link)}" target="_blank" rel="noopener noreferrer" class="detail-cta">${c(n.link_text||"Ver link")} <i data-lucide="external-link"></i></a>`:""}
                </div>

                <div class="detail-stats">
                    <div class="stat">
                        <span class="stat-label">Destinatarios</span>
                        <span class="stat-value">${Number(n.total_recipients||0).toLocaleString("pt-BR")}</span>
                    </div>
                    <div class="stat">
                        <span class="stat-label">Notificacoes Lidas</span>
                        <span class="stat-value">${Number(n.notifications_read||0).toLocaleString("pt-BR")} <small>(${Number(n.read_rate||0)}%)</small></span>
                    </div>
                    ${n.send_email?`
                        <div class="stat">
                            <span class="stat-label">E-mails OK</span>
                            <span class="stat-value">${Number(n.emails_sent||0).toLocaleString("pt-BR")}</span>
                        </div>
                        <div class="stat">
                            <span class="stat-label">E-mails Falhos</span>
                            <span class="stat-value ${n.emails_failed>0?"text-danger":""}">${Number(n.emails_failed||0).toLocaleString("pt-BR")}</span>
                        </div>
                    `:""}
                </div>

                ${o}

                <div class="detail-meta">
                    <span><i data-lucide="filter"></i> ${c(n.filters_description||"Sem filtros")}</span>
                    <span><i data-lucide="radio-tower"></i> ${c(n.channels_description||"Sem canais")}</span>
                </div>
            </div>
        `,r()}catch(i){v("[Communications] Erro ao carregar detalhes da campanha",i,"Falha ao carregar detalhes"),e.innerHTML='<div class="text-danger">Erro ao carregar detalhes</div>'}}async function j(a){a.preventDefault();const t=document.getElementById("campaignForm"),e=document.getElementById("btnSend");if(!t||!e)return;const s=e.innerHTML,i=document.getElementById("campaignTitle")?.value.trim()||"",n=document.getElementById("campaignMessage")?.value.trim()||"",d=document.getElementById("sendNotification")?.checked===!0,o=document.getElementById("sendEmail")?.checked===!0,l=document.getElementById("scheduleEnabled")?.checked===!0,u=l&&document.getElementById("scheduledAt")?.value||null;if(!i||!n){LKFeedback.warning("Preencha o titulo e a mensagem da campanha.");return}if(!d&&!o){LKFeedback.warning("Escolha pelo menos um canal de envio (Notificacao ou E-mail).");return}if(l&&!u){LKFeedback.warning("Selecione a data e hora para o agendamento.");return}const L=document.getElementById("recipientCount")?.textContent||"0",C=[d?"Notificacao":"",o?"E-mail":""].filter(Boolean).join(" + "),P=l?`Campanha sera agendada para ${new Date(u).toLocaleString("pt-BR")}.
Destinatarios estimados: ${L}. Canais: ${C}`:`Voce esta prestes a enviar uma campanha para ${L} usuarios. Canais: ${C}`;if((await LKFeedback.confirm(P,{title:l?"Confirmar agendamento?":"Confirmar envio?",icon:"question",confirmButtonText:l?"Sim, agendar!":"Sim, enviar!",cancelButtonText:"Cancelar"})).isConfirmed){e.disabled=!0,e.innerHTML=l?'<i data-lucide="loader-2" class="icon-spin"></i> Agendando...':'<i data-lucide="loader-2" class="icon-spin"></i> Enviando...',r();try{const m=await w(`${f}api/campaigns`,{title:i,message:n,type:document.getElementById("campaignType")?.value||"promo",link:document.getElementById("campaignLink")?.value||null,link_text:document.getElementById("campaignLinkText")?.value||null,send_notification:d,send_email:o,cupom_id:document.getElementById("campaignCupom")?.value||null,scheduled_at:u||null,filters:{plan:document.getElementById("filterPlan")?.value||"",status:document.getElementById("filterStatus")?.value||"",days_inactive:document.getElementById("filterDaysInactive")?.value||null}});if(m?.success===!1){LKFeedback.error(m?.message||"Ocorreu um erro ao enviar a campanha.");return}const p=m?.data??m;p?.scheduled_at?LKFeedback.success(`Campanha agendada para ${p.scheduled_at}.`,{toast:!0}):LKFeedback.success(`${Number(p?.total_recipients||0).toLocaleString("pt-BR")} usuarios receberao sua mensagem.${p?.emails_sent>0?` E-mails enviados: ${p.emails_sent}`:""}${p?.emails_failed>0?` E-mails com falha: ${p.emails_failed}`:""}`,{toast:!0}),t.reset(),document.getElementById("titleCount").textContent="0",document.getElementById("sendNotification").checked=!0,document.getElementById("scheduleEnabled").checked=!1,I(),await h(1),await y()}catch(m){v("[Communications] Erro ao enviar campanha",m,"Falha ao enviar campanha"),LKFeedback.error(x(m,"Ocorreu um erro de conexao. Tente novamente."))}finally{e.disabled=!1,e.innerHTML=s,r()}}}async function G(a){if((await LKFeedback.confirm("Deseja cancelar esta campanha agendada?",{title:"Cancelar agendamento?",icon:"warning",confirmButtonText:"Sim, cancelar",cancelButtonText:"Nao"})).isConfirmed)try{const e=await w(`${f}api/campaigns/${a}/cancel`);if(e?.success===!1){LKFeedback.error(e?.message||"Erro ao cancelar campanha.");return}LKFeedback.success("Campanha agendada cancelada.",{toast:!0}),await h(B)}catch(e){v("[Communications] Erro ao cancelar campanha",e,"Falha ao cancelar campanha"),LKFeedback.error(x(e,"Erro de conexao ao cancelar campanha."))}}function V(){const a=document.getElementById("campaignTitle"),t=document.getElementById("titleCount");a?.addEventListener("input",()=>{t&&(t.textContent=String(a.value.length))}),document.querySelectorAll(".filter-input").forEach(e=>{e.addEventListener("change",U)}),document.getElementById("scheduleEnabled")?.addEventListener("change",I),document.getElementById("campaignForm")?.addEventListener("submit",j)}document.addEventListener("click",a=>{const t=a.target.closest("[data-action]");if(t)switch(t.dataset.action){case"updatePreview":y();break;case"loadCampaigns":h();break;case"changePage":q(parseInt(t.dataset.delta,10));break;case"showCampaignDetail":O(parseInt(t.dataset.campaignId,10));break;case"cancelScheduled":a.stopPropagation(),G(parseInt(t.dataset.campaignId,10));break}});function T(){S||!document.getElementById("campaignForm")||!document.getElementById("campaignsList")||(S=!0,V(),I(),h(),y())}const U=M(()=>{y()},300);document.addEventListener("DOMContentLoaded",T);document.readyState!=="loading"&&T();
