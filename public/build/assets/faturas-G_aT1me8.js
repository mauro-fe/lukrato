import{a as V,g as U,j as y,e as h}from"./api-CiEmwEpk.js";import{r as $}from"./ui-H2yoVZe7.js";import{c as H,p as j,f as J}from"./ui-preferences-BzA64XRO.js";function G(a){return{house:"#f97316",utensils:"#ef4444",car:"#3b82f6",lightbulb:"#eab308","heart-pulse":"#ef4444","graduation-cap":"#6366f1",shirt:"#ec4899",clapperboard:"#a855f7","credit-card":"#0ea5e9",smartphone:"#6366f1","shopping-cart":"#f97316",coins:"#eab308",briefcase:"#3b82f6",laptop:"#06b6d4","trending-up":"#22c55e",gift:"#ec4899",banknote:"#22c55e",trophy:"#f59e0b",wallet:"#14b8a6",tag:"#94a3b8","pie-chart":"#8b5cf6","piggy-bank":"#ec4899",plane:"#0ea5e9","gamepad-2":"#a855f7",baby:"#f472b6",dog:"#92400e",wrench:"#64748b",church:"#6366f1",dumbbell:"#ef4444",music:"#a855f7","book-open":"#3b82f6",scissors:"#ec4899","building-2":"#64748b",landmark:"#3b82f6",receipt:"#14b8a6"}[a]||"#f97316"}const b={BASE_URL:(window.BASE_URL||"/").replace(/\/?$/,"/"),ENDPOINTS:{parcelamentos:"api/faturas",categorias:"api/categorias",contas:"api/contas",cartoes:"api/cartoes"},TIMEOUTS:{alert:5e3,successMessage:2e3}},n={};function _(a){const e=document.getElementById(a);return e?(e.parentElement!==document.body&&document.body.appendChild(e),e):null}function K(){n.loadingEl=document.getElementById("loadingParcelamentos"),n.containerEl=document.getElementById("parcelamentosContainer"),n.emptyStateEl=document.getElementById("emptyState"),n.filtroStatus=document.getElementById("filtroStatus"),n.filtroCartao=document.getElementById("filtroCartao"),n.filtroAno=document.getElementById("filtroAno"),n.filtroMes=document.getElementById("filtroMes"),n.btnFiltrar=document.getElementById("btnFiltrar"),n.btnLimparFiltros=document.getElementById("btnLimparFiltros"),n.filtersContainer=document.querySelector(".filters-modern"),n.filtersBody=document.getElementById("filtersBody"),n.toggleFilters=document.getElementById("toggleFilters"),n.activeFilters=document.getElementById("activeFilters"),n.modalDetalhes=_("modalDetalhesParcelamento"),n.modalPagarFatura=_("modalPagarFatura"),n.modalEditarItemFatura=_("modalEditarItemFatura"),n.detalhesContent=document.getElementById("detalhesParcelamentoContent")}const l={parcelamentos:[],cartoes:[],faturaAtual:null,sortColumn:"data_compra",sortDirection:"asc",filtros:{status:"",cartao_id:"",ano:new Date().getFullYear(),mes:""},modalDetalhesInstance:null,anosCarregados:!1},m={},c={formatMoney(a){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(a||0)},formatDate(a){return a?new Date(a+"T00:00:00").toLocaleDateString("pt-BR"):""},parseMoney(a){return a&&parseFloat(a.replace(/[^\d,]/g,"").replace(",","."))||0},showAlert(a,e,t="danger"){a&&(a.className=`alert alert-${t}`,a.textContent=e,a.style.display="block",setTimeout(()=>{a.style.display="none"},b.TIMEOUTS.alert))},getCSRFToken(){return U()},escapeHtml(a){if(!a)return"";const e=document.createElement("div");return e.textContent=a,e.innerHTML},buildUrl(a,e={}){const t=a.startsWith("http")?a:b.BASE_URL+a.replace(/^\//,""),o=Object.entries(e).filter(([r,s])=>s!=null&&s!=="").map(([r,s])=>`${r}=${encodeURIComponent(s)}`);return o.length>0?`${t}?${o.join("&")}`:t},async apiRequest(a,e={}){const t=a.startsWith("http")?a:b.BASE_URL+a.replace(/^\//,"");try{return await V(t,{...e,headers:{"X-CSRF-Token":this.getCSRFToken(),...e.headers}})}catch(o){throw console.error("Erro na requisição:",o),o}},debounce(a,e){let t;return function(...r){const s=()=>{clearTimeout(t),a(...r)};clearTimeout(t),t=setTimeout(s,e)}},calcularDiferencaDias(a,e){const t=new Date(a+"T00:00:00"),o=new Date(e+"T00:00:00");return Math.floor((t-o)/(1e3*60*60*24))}},X={async listarParcelamentos(a={}){const e={status:a.status,cartao_id:a.cartao_id,ano:a.ano,mes:a.mes},t=c.buildUrl(b.ENDPOINTS.parcelamentos,e);return await c.apiRequest(t)},async listarCartoes(){return await c.apiRequest(b.ENDPOINTS.cartoes)},async buscarParcelamento(a){const e=parseInt(a,10);if(isNaN(e))throw new Error("ID inválido");return await c.apiRequest(`${b.ENDPOINTS.parcelamentos}/${e}`)},async criarParcelamento(a){return await c.apiRequest(b.ENDPOINTS.parcelamentos,{method:"POST",body:JSON.stringify(a)})},async cancelarParcelamento(a){return await c.apiRequest(`${b.ENDPOINTS.parcelamentos}/${a}`,{method:"DELETE"})},async toggleItemFatura(a,e,t){return await c.apiRequest(`${b.ENDPOINTS.parcelamentos}/${a}/itens/${e}/toggle`,{method:"POST",body:JSON.stringify({pago:t})})},async pagarFaturaCompleta(a,e,t,o=null){const r={mes:e,ano:t};return o&&(r.conta_id=o),await c.apiRequest(`${b.ENDPOINTS.cartoes}/${a}/fatura/pagar`,{method:"POST",body:JSON.stringify(r)})},async listarContas(){return await c.apiRequest(`${b.ENDPOINTS.contas}?with_balances=1`)}};m.API=X;const O=(a,e=0,t=100)=>Math.min(t,Math.max(e,Number(a)||0)),g=(a,e="")=>c.escapeHtml(String(a??e)),Y=(a,e=0)=>`${(Number(a)||0).toLocaleString("pt-BR",{minimumFractionDigits:e,maximumFractionDigits:e})}%`,A=(a,e)=>`data-lk-tooltip-title="${g(a)}" data-lk-tooltip="${g(e)}"`,k=/(#[0-9a-fA-F]{3,8}|rgba?\([^)]+\)|hsla?\([^)]+\))/,z=()=>window.LK?.getBase?.()||"/",W={renderParcelamentos(a){if(!Array.isArray(a)||a.length===0){this.showEmpty();return}n.emptyStateEl.style.display="none",n.containerEl.style.display="grid";const e=document.createDocumentFragment();a.forEach(t=>{const o=this.createParcelamentoCard(t);e.appendChild(o)}),n.containerEl.innerHTML="",n.containerEl.appendChild(e),$()},createParcelamentoCard(a){const e=a.progresso||0,t=a.parcelas_pendentes||0,o=a.parcelas_pagas||0,r=o+t,s=this.getDueMeta(a),i=this.getStatusMeta(a.status,e,s),d=document.createElement("div");d.className=`parcelamento-card surface-card surface-card--interactive surface-card--clip status-${a.status}`,d.dataset.id=a.id,d.style.setProperty("--fatura-accent",this.getAccentColorSolid(a.cartao));const f=this.getStatusBadge(a.status,e,s),u=a.mes_referencia||"",v=a.ano_referencia||"";return d.innerHTML=this.createCardHTML({parc:a,statusBadge:f,mes:u,ano:v,itensPendentes:t,itensPagos:o,totalItens:r,progresso:e,dueMeta:s,statusMeta:i}),this.attachCardEventListeners(d,a.id),d},attachCardEventListeners(a,e){const t=a.querySelector('[data-action="view"]');t&&t.addEventListener("click",()=>this.showDetalhes(e))},getAccentColorSolid(a){const t={visa:"#1A1F71",mastercard:"#EB001B",elo:"#FFCB05",amex:"#006FCF",diners:"#0079BE",discover:"#FF6000",hipercard:"#B11116"}[a?.bandeira?.toLowerCase()]||"#3b82f6",o=String(a?.cor_cartao||a?.conta?.instituicao_financeira?.cor_primaria||t).trim();return o?/gradient/i.test(o)?o.match(k)?.[1]||t:/^var\(/i.test(o)||k.test(o)?o:t:t},getBandeiraIcon(a){return{visa:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><text x="24" y="20" text-anchor="middle" font-size="12" font-weight="bold" fill="#fff" font-family="sans-serif">VISA</text></svg>',mastercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71" opacity="0"/><circle cx="19" cy="16" r="10" fill="#EB001B" opacity=".85"/><circle cx="29" cy="16" r="10" fill="#F79E1B" opacity=".85"/></svg>',elo:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#000"/><text x="24" y="20" text-anchor="middle" font-size="13" font-weight="bold" fill="#FFCB05" font-family="sans-serif">elo</text></svg>',amex:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#006FCF"/><text x="24" y="20" text-anchor="middle" font-size="9" font-weight="bold" fill="#fff" font-family="sans-serif">AMEX</text></svg>',hipercard:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#B11116"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">HIPER</text></svg>',diners:'<svg viewBox="0 0 48 32" width="32" height="22" fill="none"><rect width="48" height="32" rx="4" fill="#0079BE"/><text x="24" y="20" text-anchor="middle" font-size="8" font-weight="bold" fill="#fff" font-family="sans-serif">DINERS</text></svg>'}[a]||'<i data-lucide="credit-card"></i>'},getDueMeta(a){let e=a.data_vencimento;if(!e&&a.cartao?.dia_vencimento&&a.descricao){const f=a.descricao.match(/(\d{1,2})\/(\d{4})/);if(f){const u=f[1].padStart(2,"0"),v=f[2],w=String(a.cartao.dia_vencimento).padStart(2,"0");e=`${v}-${u}-${w}`}}if(!e)return{hasDate:!1,label:"A definir",helper:"Sem data de vencimento informada",detailClass:"",isVencida:!1,isProxima:!1};const t=c.formatDate(e),o=new Date;o.setHours(0,0,0,0);const r=new Date(`${e}T00:00:00`),s=a.status!=="paga"&&a.status!=="concluido"&&a.status!=="cancelado",i=s&&r<o,d=s&&!i&&r-o<=4320*60*1e3;return{hasDate:!0,raw:e,label:t,helper:i?"Vencimento expirado":d?"Vence em breve":"Dentro do prazo",detailClass:i?"is-danger":d?"is-warning":"",isVencida:i,isProxima:d}},getStatusMeta(a,e=null,t=null){const o=O(e);return a==="cancelado"?{badgeClass:"badge-cancelado",progressClass:"is-muted",icon:"ban",label:"Cancelada",shortLabel:"Cancelada",hint:"Sem cobranca ativa",tooltip:"Esta fatura foi cancelada e nao entra mais no acompanhamento ativo."}:o>=100||a==="paga"||a==="concluido"?{badgeClass:"badge-paga",progressClass:"is-safe",icon:"circle-check",label:"Paga",shortLabel:"Liquidada",hint:"Pagamento concluido",tooltip:"O valor desta fatura ja foi quitado integralmente."}:t?.isVencida?{badgeClass:"badge-alerta",progressClass:"is-danger",icon:"triangle-alert",label:"Vencida",shortLabel:"Em atraso",hint:"Regularize esta fatura",tooltip:"A fatura passou do vencimento e merece prioridade para evitar juros."}:t?.isProxima?{badgeClass:"badge-alerta",progressClass:"is-warning",icon:"clock-3",label:"Vence em breve",shortLabel:"Vence logo",hint:"Priorize o pagamento",tooltip:"O vencimento esta proximo. Vale organizar o pagamento desta fatura."}:o>0?{badgeClass:"badge-parcial",progressClass:"is-warning",icon:"loader-2",label:"Pagamento parcial",shortLabel:"Parcial",hint:"Parte do valor ja foi paga",tooltip:"A fatura segue aberta, mas ja possui pagamentos registrados."}:{badgeClass:"badge-pendente",progressClass:"is-safe",icon:"clock-3",label:"Pendente",shortLabel:"No prazo",hint:"Aguardando pagamento",tooltip:"A fatura segue aberta e ainda esta dentro do prazo normal de pagamento."}},getResumoPrincipal(a,e,t,o,r,s){const i=a.total_estornos&&a.total_estornos>0,d=s>0?`${r} de ${s} itens pagos`:"Sem itens consolidados",f=e.hasDate&&e.helper!=="Dentro do prazo"?`<span class="fatura-card-due-tag ${e.detailClass}">${g(e.helper)}</span>`:"";return`
            <div class="fatura-card-main">
                <span class="resumo-label">Valor total da fatura</span>
                <strong class="resumo-valor">${c.formatMoney(a.valor_total)}</strong>
                <div class="fatura-card-due-line ${e.detailClass}">
                    <span class="fatura-card-due-copy">Vencimento ${g(e.label)}</span>
                    ${f}
                </div>
                ${i?`
                    <p class="fatura-card-note">
                        Inclui ${c.formatMoney(a.total_estornos)} em estornos no fechamento.
                    </p>
                `:""}
            </div>

            <div class="fatura-card-details">
                <div class="fatura-card-detail ${e.detailClass}" ${A("Vencimento",e.hasDate?`Data prevista para pagamento desta fatura: ${e.label}.`:"A fatura ainda nao possui data de vencimento consolidada.")}>
                    <span class="fatura-card-detail-label">Vencimento</span>
                    <strong class="fatura-card-detail-value">${g(e.label)}</strong>
                    <span class="fatura-card-detail-meta">${g(e.helper)}</span>
                </div>

                <div class="fatura-card-detail ${t.progressClass}" ${A("Progresso de pagamento",s>0?`${r} de ${s} itens ja foram pagos nesta fatura.`:"Ainda nao existem itens suficientes para calcular o progresso de pagamento.")}>
                    <span class="fatura-card-detail-label">Pagamento</span>
                    <strong class="fatura-card-detail-value">${s>0?`${r}/${s}`:"--"}</strong>
                    <span class="fatura-card-detail-meta">${g(d)}</span>
                </div>
            </div>
        `},getProgressoSection(a,e,t,o,r){const s=O(o),i=s>0?Math.max(s,8):0;return a===0?`
                <div class="parc-progress-section is-empty">
                    <div class="parc-progress-header">
                        <span class="parc-progress-text">Sem itens suficientes para medir o pagamento</span>
                        <span class="parc-progress-percent">--</span>
                    </div>
                    <div class="parc-progress-bar">
                        <div class="parc-progress-fill ${r.progressClass}" style="width: 0%"></div>
                    </div>
                </div>
            `:`
            <div class="parc-progress-section ${r.progressClass}">
                <div class="parc-progress-header">
                    <span class="parc-progress-text">Pagamento ${Y(s)}</span>
                    <span class="parc-progress-percent">${g(r.shortLabel)}</span>
                </div>
                <div class="parc-progress-bar">
                    <div class="parc-progress-fill ${r.progressClass}" style="width: ${i}%"></div>
                </div>
                <div class="parc-progress-foot">
                    <span>${t} de ${a} itens pagos</span>
                    <span>${e} em aberto</span>
                </div>
            </div>
        `},getStatusBadge(a,e=null,t=null){const o=this.getStatusMeta(a,e,t);return`
            <span
                class="parc-card-badge ${o.badgeClass}"
                ${A(o.label,o.tooltip)}>
                <i data-lucide="${o.icon}" style="width:12px;height:12px"></i>
                ${g(o.label)}
            </span>
        `},createCardHTML({parc:a,statusBadge:e,mes:t,ano:o,itensPendentes:r,itensPagos:s,totalItens:i,progresso:d,dueMeta:f,statusMeta:u}){const v=this.getResumoPrincipal(a,f,u,r,s,i),w=this.getProgressoSection(i,r,s,d,u),E=Number.parseInt(String(a.cartao?.id??a.cartao_id??0),10)||0,x=E>0?`${z()}importacoes?import_target=cartao&source_type=ofx&cartao_id=${E}`:`${z()}importacoes?import_target=cartao&source_type=ofx`,F=a.cartao&&(a.cartao.nome||a.cartao.bandeira)||"Cartao",C=a.cartao?.conta?.instituicao_financeira?.nome||"Sem instituicao",p=a.cartao?.ultimos_digitos?`Final ${a.cartao.ultimos_digitos}`:"",I=this.getAccentColorSolid(a.cartao),B=a.cartao?.bandeira?.toLowerCase()||"outros",D=this.getBandeiraIcon(B),P=t&&o?`${t}/${o}`:"Fatura atual",M=[g(C),p?g(p):""].filter(Boolean).join(" - ");return`
            <div class="fatura-card-shell" style="--fatura-accent:${I};">
                <div class="fatura-card-top">
                    <div class="fatura-card-media">
                        <div class="fatura-card-brand" aria-hidden="true">
                            ${D}
                        </div>
                    </div>

                    <div class="fatura-card-head">
                        <div class="fatura-card-title-wrap">
                            <span class="fatura-card-title">${g(F)}</span>
                            <span class="fatura-card-subtitle">${g(C)}</span>
                        </div>
                        <div class="fatura-card-meta">
                            <span class="fatura-card-period" ${A("Periodo da fatura","Competencia consolidada desta fatura para acompanhar fechamento e vencimento.")}>
                                <i data-lucide="calendar-days"></i>
                                <span>${g(P)}</span>
                            </span>
                            ${e}
                        </div>
                    </div>
                </div>

                <div class="fatura-list-info">
                    <span class="list-cartao-nome">${g(F)}</span>
                    <span class="list-periodo">${g(P)}</span>
                    <span class="list-cartao-numero">${M}</span>
                </div>

                <div class="fatura-resumo-principal">${v}</div>
                ${w}
                <div class="fatura-status-col">${e}</div>
                <div class="parc-card-actions">
                    <a
                        class="parc-btn parc-btn-import"
                        href="${g(x)}"
                        data-no-transition="true"
                        title="Importar OFX desta fatura/cartão"
                    >
                        <i data-lucide="upload"></i>
                        <span>Importar OFX</span>
                    </a>
                    <button class="parc-btn parc-btn-view" data-action="view" data-id="${a.id}">
                        <i data-lucide="eye"></i>
                        <span>Ver detalhes</span>
                    </button>
                </div>
            </div>
        `}},Q={async showDetalhes(a){try{const e=await m.API.buscarParcelamento(a),t=y(e,null);if(!t){l.modalDetalhesInstance&&l.modalDetalhesInstance.hide();return}l.faturaAtual=t;const o=n.modalDetalhes;if(o&&t.cartao){const r=this.getAccentColorSolid(t.cartao),s=o.querySelector(".modal-content");s&&s.style.setProperty("--card-accent",r)}n.detalhesContent.innerHTML=this.renderDetalhes(t),$(),this.attachDetalhesEventListeners(t.id),document.activeElement?.blur(),l.modalDetalhesInstance.show()}catch(e){if(console.error("Erro ao abrir detalhes:",e),e.message&&e.message.includes("404")){l.modalDetalhesInstance&&l.modalDetalhesInstance.hide();return}Swal.fire({icon:"error",title:"Erro",text:h(e,"Não foi possível carregar os detalhes da fatura")})}},attachDetalhesEventListeners(a){n.detalhesContent.querySelectorAll(".th-sortable").forEach(s=>{s.addEventListener("click",()=>{const i=s.dataset.sort;l.sortColumn===i?l.sortDirection=l.sortDirection==="asc"?"desc":"asc":(l.sortColumn=i,l.sortDirection="asc"),l.faturaAtual&&(n.detalhesContent.innerHTML=this.renderDetalhes(l.faturaAtual),$(),this.attachDetalhesEventListeners(a))})}),n.detalhesContent.querySelectorAll(".btn-pagar, .btn-desfazer").forEach(s=>{s.addEventListener("click",async i=>{const d=parseInt(i.currentTarget.dataset.lancamentoId,10),f=i.currentTarget.dataset.pago==="true";await this.toggleParcelaPaga(a,d,!f)})}),n.detalhesContent.querySelectorAll(".btn-editar").forEach(s=>{s.addEventListener("click",async i=>{const d=parseInt(i.currentTarget.dataset.lancamentoId,10),f=i.currentTarget.dataset.descricao||"",u=parseFloat(i.currentTarget.dataset.valor)||0;await this.editarItemFatura(a,d,f,u)})}),n.detalhesContent.querySelectorAll(".btn-excluir").forEach(s=>{s.addEventListener("click",async i=>{const d=parseInt(i.currentTarget.dataset.lancamentoId,10),f=i.currentTarget.dataset.ehParcelado==="true",u=parseInt(i.currentTarget.dataset.totalParcelas)||1;await this.excluirItemFatura(a,d,f,u)})})},renderDetalhes(a){const e=a.progresso||0,{valorPago:t,valorRestante:o}=this.calcularValores(a),r=a.parcelas_pendentes>0&&o>0;return`
            ${this.renderDetalhesHeader(a,r,o)}
            ${this.renderDetalhesGrid(a,e)}
            ${this.renderDetalhesProgresso(a,e,t,o)}
            ${this.renderParcelasTabela(a)}
        `},calcularValores(a){let e=0,t=a.valor_total;return a.parcelas&&a.parcelas.length>0&&(e=a.parcelas.filter(o=>o.pago).reduce((o,r)=>o+parseFloat(r.valor_parcela||r.valor||0),0),t=a.parcelas.filter(o=>!o.pago).reduce((o,r)=>o+parseFloat(r.valor_parcela||r.valor||0),0)),{valorPago:e,valorRestante:t}},renderDetalhesHeader(a,e,t){let o="/";a.data_vencimento?o=c.formatDate(a.data_vencimento):a.mes_referencia&&a.ano_referencia&&(o=`${this.getNomeMes(a.mes_referencia)}/${a.ano_referencia}`),a.parcelas_pagas>0;const r=a.parcelas_pendentes===0&&a.parcelas_pagas>0;return`
            <div class="detalhes-header">
                <div class="detalhes-header-content" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                    <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                        <span style="color: #9ca3af; font-size: 0.875rem; font-weight: 500;">Vencimento</span>
                        <h3 class="detalhes-title" style="margin: 0;">${o}</h3>
                    </div>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
                        ${e?`
                            <button class="btn-pagar-fatura" 
                                    onclick="window.abrirModalPagarFatura(${a.id}, ${t})"
                                    style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="credit-card"></i>
                                <span class="btn-text-desktop">Pagar Fatura</span>
                                <span class="btn-text-mobile">Pagar</span>
                            </button>
                        `:""}
                        ${r?`
                            <button class="btn-reverter-fatura" 
                                    onclick="window.reverterPagamentoFaturaGlobal(${a.id})"
                                    style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.75rem 1.25rem; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; transition: all 0.2s;">
                                <i data-lucide="undo-2"></i>
                                <span class="btn-text-desktop">Reverter Pagamento</span>
                                <span class="btn-text-mobile">Reverter</span>
                            </button>
                        `:""}
                    </div>
                </div>
            </div>
        `},renderDetalhesGrid(a,e){const t=a.parcelas_pagas+a.parcelas_pendentes,o=a.total_estornos&&a.total_estornos>0;return`
            <div class="detalhes-grid">
                <div class="detalhes-item">
                    <span class="detalhes-label">💵 Valor Total a Pagar</span>
                    <span class="detalhes-value detalhes-value-highlight">${c.formatMoney(a.valor_total)}</span>
                </div>
                ${o?`
                <div class="detalhes-item">
                    <span class="detalhes-label">↩️ Estornos/Créditos</span>
                    <span class="detalhes-value" style="color: #10b981;">- ${c.formatMoney(a.total_estornos)}</span>
                </div>
                `:""}
                <div class="detalhes-item">
                    <span class="detalhes-label">📦 Itens</span>
                    <span class="detalhes-value">${t} itens</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">📊 Tipo</span>
                    <span class="detalhes-value">💸 Despesas${o?" + ↩️ Estornos":""}</span>
                </div>
                <div class="detalhes-item">
                    <span class="detalhes-label">🎯 Status</span>
                    <span class="detalhes-value">${this.getStatusBadge(a.status,e)}</span>
                </div>
                ${a.cartao?`
                    <div class="detalhes-item">
                        <span class="detalhes-label">💳 Cartão</span>
                        <span class="detalhes-value">${a.cartao.bandeira} ${a.cartao.nome?"- "+c.escapeHtml(a.cartao.nome):""}</span>
                    </div>
                `:""}
            </div>
        `},renderDetalhesProgresso(a,e,t,o){const r=a.parcelas_pagas+a.parcelas_pendentes;return`
            <div class="detalhes-progresso">
                <div class="progresso-info">
                    <span><strong>${a.parcelas_pagas}</strong> de <strong>${r}</strong> itens pagos</span>
                    <span class="progresso-percent"><strong>${Math.round(e)}%</strong></span>
                </div>
                <div class="progresso-barra">
                    <div class="progresso-fill" style="width: ${e}%"></div>
                </div>
                <div class="progresso-valores">
                    <span class="valor-pago">✅ Pago: ${c.formatMoney(t)}</span>
                    <span class="valor-restante">⏳ Restante: ${c.formatMoney(o)}</span>
                </div>
            </div>
        `},renderParcelasTabela(a){const e=r=>l.sortColumn===r?l.sortDirection==="asc"?'<i data-lucide="arrow-up" class="sort-icon active"></i>':'<i data-lucide="arrow-down" class="sort-icon active"></i>':'<i data-lucide="arrow-up-down" class="sort-icon"></i>',t=this.sortParcelas(a.parcelas||[]);let o=`
            <h4 class="parcelas-titulo">📋 Lista de Itens</h4>
            
            <!-- Tabela Desktop -->
            <div class="parcelas-container parcelas-desktop">
                <table class="parcelas-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="th-sortable" data-sort="descricao">Descrição ${e("descricao")}</th>
                            <th class="th-sortable" data-sort="data_compra">Data Compra ${e("data_compra")}</th>
                            <th class="th-sortable" data-sort="valor">Valor ${e("valor")}</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
        `;return t.length>0?t.forEach((r,s)=>{o+=this.renderParcelaRow(r,s,a.descricao)}):o+=`
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">
                        <p style="color: #6b7280;">Nenhuma parcela encontrada</p>
                    </td>
                </tr>
            `,o+=`
                    </tbody>
                </table>
            </div>
            
            <!-- Cards Mobile -->
            <div class="parcelas-container parcelas-mobile">
        `,a.parcelas&&a.parcelas.length>0?this.sortParcelas(a.parcelas).forEach((s,i)=>{o+=this.renderParcelaCard(s,i,a.descricao)}):o+=`
                <div class="parcela-card-empty">
                    <p>Nenhuma parcela encontrada</p>
                </div>
            `,o+="</div>",o},sortParcelas(a){if(!a||a.length===0)return[];const e=[...a],t=l.sortDirection==="asc"?1:-1,o=l.sortColumn;return e.sort((r,s)=>{if(o==="descricao"){const i=(r.descricao||"").toLowerCase(),d=(s.descricao||"").toLowerCase();return i.localeCompare(d)*t}if(o==="data_compra"){const i=r.data_compra||"0000-00-00",d=s.data_compra||"0000-00-00";return i.localeCompare(d)*t}if(o==="valor"){const i=parseFloat(r.valor_parcela||r.valor||0),d=parseFloat(s.valor_parcela||s.valor||0);return(i-d)*t}return 0}),e},renderParcelaCard(a,e,t){const o=a.pago,r=a.tipo==="estorno",s=o?"parcela-paga":"parcela-pendente",i=o?"✅ Paga":"⏳ Pendente",d=o?"parcela-card-paga":"",f=`${this.getNomeMes(a.mes_referencia)}/${a.ano_referencia}`,u=`parcela-card-${a.id||e}`;let v=a.descricao||t;v=v.replace(/\s*\(\d+\/\d+\)\s*$/,"");let w="";if(a.categoria){const E=a.categoria.icone||"tag",x=a.categoria.nome||a.categoria;w=`<i data-lucide="${E}" style="width:14px;height:14px;display:inline-block;vertical-align:middle;color:${G(E)}"></i> ${c.escapeHtml(x)}`}return r?`
                <div class="parcela-card" id="${u}" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(16, 185, 129, 0.05) 100%); border-color: rgba(16, 185, 129, 0.4);">
                    <div class="parcela-card-header">
                        <span class="parcela-numero" style="color: #10b981;">↩️ Estorno</span>
                        <span class="parcela-paga" style="background: #10b981;">✅ Creditado</span>
                    </div>
                    <div class="parcela-card-body">
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Descrição</span>
                            <span class="parcela-card-value" style="color: #10b981;">${c.escapeHtml(v)}</span>
                        </div>
                        <div class="parcela-card-info">
                            <span class="parcela-card-label">Crédito na Fatura</span>
                            <span class="parcela-card-value parcela-valor" style="color: #10b981; font-weight: 600;">
                                - ${c.formatMoney(Math.abs(a.valor_parcela))}
                            </span>
                        </div>
                    </div>
                </div>
            `:`
            <div class="parcela-card ${d}" id="${u}">
                <div class="parcela-card-header">
                    <span class="parcela-numero">${a.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);margin-right:3px;"></i> Recorrente':`${a.numero_parcela||e+1}/${a.total_parcelas||1}`}</span>
                    <span class="${s}">${i}</span>
                </div>
                <div class="parcela-card-body">
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Descrição</span>
                        <span class="parcela-card-value">${c.escapeHtml(v)}${a.recorrente?' <span class="badge-recorrente" title="Assinatura recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</span>
                    </div>
                    ${a.data_compra?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Compra</span>
                        <span class="parcela-card-value"><i data-lucide="shopping-cart" style="margin-right: 4px; font-size: 0.75rem;"></i>${c.formatDate(a.data_compra)}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Valor</span>
                        <span class="parcela-card-value parcela-valor">${c.formatMoney(a.valor_parcela)}</span>
                    </div>
                </div>
                
                <!-- Detalhes expandíveis -->
                <div class="parcela-card-detalhes" id="detalhes-${u}" style="display: none;">
                    ${w?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Categoria</span>
                        <span class="parcela-card-value">${w}</span>
                    </div>
                    `:""}
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Mês/Ano</span>
                        <span class="parcela-card-value">${f}</span>
                    </div>
                    ${o&&a.data_pagamento?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">Data Pagamento</span>
                        <span class="parcela-card-value">${a.data_pagamento}</span>
                    </div>
                    `:""}
                    ${a.id?`
                    <div class="parcela-card-info">
                        <span class="parcela-card-label">ID do Item</span>
                        <span class="parcela-card-value">#${a.id}</span>
                    </div>
                    `:""}
                </div>
                
                <div class="parcela-card-footer">
                  
                    ${this.renderParcelaButton(a,o)}
                </div>
            </div>
        `},renderParcelaRow(a,e,t){const o=a.pago,r=a.tipo==="estorno",s=o?"tr-paga":"";`${this.getNomeMes(a.mes_referencia)}${a.ano_referencia}`,this.getDataPagamentoInfo(a);let i=a.descricao||t;i=i.replace(/\s*\(\d+\/\d+\)\s*$/,""),a.categoria&&(i=a.categoria.nome||a.categoria);const d=a.data_compra?c.formatDate(a.data_compra):"-";return r?`
                <tr class="tr-estorno" style="background: rgba(16, 185, 129, 0.1);">
                    <td data-label="#">
                        <span class="parcela-numero" style="color: #10b981;">↩️</span>
                    </td>
                    <td data-label="Descrição" class="td-descricao">
                        <div class="parcela-desc" style="color: #10b981;">${c.escapeHtml(i)}</div>
                    </td>
                    <td data-label="Data Compra">
                        <span style="color: #10b981; font-size: 0.85rem;">${d}</span>
                    </td>
                    <td data-label="Valor">
                        <span class="parcela-valor" style="color: #10b981; font-weight: 600;">
                            - ${c.formatMoney(Math.abs(a.valor_parcela))}
                        </span>
                    </td>
                    <td data-label="Ação" class="td-acoes">
                        <span style="color: #10b981; font-size: 0.85rem;">Estorno aplicado</span>
                    </td>
                </tr>
            `:`
            <tr class="${s}">
                <td data-label="#">
                    <span class="parcela-numero">${a.recorrente?'<i data-lucide="refresh-cw" style="width:12px;height:12px;display:inline-block;vertical-align:middle;color:var(--primary, #e67e22);"></i>':`${a.numero_parcela}/${a.total_parcelas}`}</span>
                </td>
                <td data-label="Descrição" class="td-descricao">
                    <div class="parcela-desc">${c.escapeHtml(i)}${a.recorrente?' <span class="badge-recorrente" style="display:inline-flex;align-items:center;background:rgba(230,126,34,0.15);border-radius:6px;padding:1px 6px;margin-left:6px;"><i data-lucide="refresh-cw" style="width:12px;height:12px;color:var(--primary, #e67e22);"></i></span>':""}</div>
                </td>
                <td data-label="Data Compra">
                    <span style="font-size: 0.85rem; color: #9ca3af;">${d}</span>
                </td>
                <td data-label="Valor">
                    <span class="parcela-valor">${c.formatMoney(a.valor_parcela)}</span>
                </td>
                <td data-label="Ação" class="td-acoes">
                    ${this.renderParcelaButton(a,o)}
                </td>
            </tr>
        `},getDataPagamentoInfo(a){return!a.pago||!a.data_pagamento?"":`<small style="color: #10b981; display: block; margin-top: 3px;">✅ Pago em ${a.data_pagamento}</small>`},renderParcelaButton(a,e){if(e)return`
                <div class="btn-group-parcela">
                    <span class="badge-pago" style="background: rgba(16, 185, 129, 0.15); color: #10b981; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: 500;">
                        <i data-lucide="check"></i> Pago
                    </span>
                </div>
            `;{const t=a.total_parcelas>1;return`
                <div class="btn-group-parcela">
                    <button class="btn-toggle-parcela btn-editar" 
                        data-lancamento-id="${a.id}"
                        data-descricao="${c.escapeHtml(a.descricao||"")}"
                        data-valor="${a.valor_parcela||0}"
                        title="Editar item">
                        <i data-lucide="pencil"></i>
                    </button>
                    <button class="btn-toggle-parcela btn-excluir" 
                        data-lancamento-id="${a.id}"
                        data-eh-parcelado="${t}"
                        data-total-parcelas="${a.total_parcelas||1}"
                        title="Excluir item">
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            `}},getNomeMes(a){return["Jan","Fev","Mar","Abr","Mai","Jun","Jul","Ago","Set","Out","Nov","Dez"][a-1]||a},mostrarDetalhesParcela(a,e){const t=a.pago,o=t?"✅":"⏳",r=t?"Paga":"Pendente",s=t?"#10b981":"#f59e0b",i=`${this.getNomeMesCompleto(a.mes_referencia)}/${a.ano_referencia}`;let d="";t&&a.data_pagamento&&(d=`
                <div class="detalhes-item">
                    <span class="detalhes-label">Data de Pagamento</span>
                    <span class="detalhes-value">${c.formatDate(a.data_pagamento)}</span>
                </div>
            `),Swal.fire({title:`${o} Detalhes da Parcela`,html:`
                <div style="text-align: left;">
                    <div style="background: ${s}15; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; border-left: 4px solid ${s};">
                        <div style="font-size: 0.875rem; color: #6b7280; margin-bottom: 0.5rem;">Status</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: ${s};">${r}</div>
                    </div>
                    
                    <div class="detalhes-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                        <div class="detalhes-item">
                            <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Parcela</span>
                            <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${a.numero_parcela}/${a.total_parcelas}</span>
                        </div>
                        
                        <div class="detalhes-item">
                            <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Valor</span>
                            <span class="detalhes-value" style="display: block; font-weight: 600; color: ${s};">${c.formatMoney(a.valor)}</span>
                        </div>
                    </div>

                    <div class="detalhes-item" style="margin-bottom: 1rem;">
                        <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Descrição</span>
                        <span class="detalhes-value" style="display: block; font-weight: 500; color: #1f2937;">${c.escapeHtml(e)}</span>
                    </div>

                    <div class="detalhes-item" style="margin-bottom: 1rem;">
                        <span class="detalhes-label" style="display: block; font-size: 0.875rem; color: #6b7280; margin-bottom: 0.25rem;">Mês de Referência</span>
                        <span class="detalhes-value" style="display: block; font-weight: 600; color: #1f2937;">${i}</span>
                    </div>

                    ${d}
                </div>
            `,icon:!1,confirmButtonText:"Fechar",confirmButtonColor:"#6366f1",width:"500px"})},getNomeMesCompleto(a){return["Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"][a-1]||a}},Z={async toggleParcelaPaga(a,e,t){try{const o=t?"pagar":"desfazer pagamento";if(!(await Swal.fire({title:t?"Marcar como pago?":"Desfazer pagamento?",text:`Deseja realmente ${o} este item?`,icon:"question",showCancelButton:!0,confirmButtonColor:t?"#10b981":"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:t?"Sim, marcar como pago":"Sim, desfazer",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Processando...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await m.API.toggleItemFatura(a,e,t),await Swal.fire({icon:"success",title:"Sucesso!",text:t?"Item marcado como pago":"Pagamento desfeito",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}}),await m.App.carregarParcelamentos(),setTimeout(()=>{this.showDetalhes(a)},100)}catch(o){console.error("Erro ao alternar status:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Erro ao processar operação"),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")}})}},async editarItemFatura(a,e,t,o){const r=n.modalEditarItemFatura||document.getElementById("modalEditarItemFatura");if(!r){console.error("Modal de edição não encontrado");return}document.getElementById("editItemFaturaId").value=a,document.getElementById("editItemId").value=e,document.getElementById("editItemDescricao").value=t,document.getElementById("editItemValor").value=o.toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2}),bootstrap.Modal.getOrCreateInstance(r,{backdrop:!0,keyboard:!0,focus:!0}).show()},async salvarItemFatura(){const a=document.getElementById("editItemFaturaId").value,e=document.getElementById("editItemId").value,t=document.getElementById("editItemDescricao").value.trim(),o=document.getElementById("editItemValor").value;if(!t){Swal.fire({icon:"warning",title:"Atenção",text:"Informe a descrição do item.",timer:2e3,showConfirmButton:!1});return}const r=parseFloat(o.replace(/\./g,"").replace(",","."))||0;if(r<=0){Swal.fire({icon:"warning",title:"Atenção",text:"Informe um valor válido.",timer:2e3,showConfirmButton:!1});return}try{const s=n.modalEditarItemFatura||document.getElementById("modalEditarItemFatura"),i=bootstrap.Modal.getInstance(s);i&&i.hide(),Swal.fire({title:"Atualizando item...",html:"Aguarde enquanto salvamos as alterações.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading()}}),await c.apiRequest(`api/faturas/${a}/itens/${e}`,{method:"PUT",body:JSON.stringify({descricao:t,valor:r})}),await Swal.fire({icon:"success",title:"Item Atualizado!",text:"O item foi atualizado com sucesso.",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1}),await m.App.carregarParcelamentos(),setTimeout(()=>{this.showDetalhes(a)},100)}catch(s){console.error("Erro ao editar item:",s),Swal.fire({icon:"error",title:"Erro",text:h(s,"Não foi possível atualizar o item."),heightAuto:!1})}},async excluirItemFatura(a,e,t,o){try{let r="Excluir Item?",s="Deseja realmente excluir este item da fatura?",i="Sim, excluir item";if(t&&o>1){const{value:u}=await Swal.fire({title:"O que deseja excluir?",html:`
                        <p>Este item faz parte de um parcelamento de <strong>${o}x</strong>.</p>
                        <p style="margin-top: 1rem;">Escolha uma opção:</p>
                    `,icon:"question",input:"radio",inputOptions:{item:"Apenas esta parcela",parcelamento:`Todo o parcelamento (${o} parcelas)`},inputValue:"item",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:"Continuar",cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const v=document.querySelector(".swal2-container");v&&(v.style.zIndex="99999")}});if(!u)return;if(u==="parcelamento")return await this.excluirParcelamentoCompleto(a,e,o)}if(!(await Swal.fire({title:r,text:s,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:i,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")}})).isConfirmed)return;Swal.fire({title:"Excluindo...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")},customClass:{container:"swal-above-modal"}}),await c.apiRequest(`api/faturas/${a}/itens/${e}`,{method:"DELETE"}),await Swal.fire({icon:"success",title:"Excluído!",text:"Item removido da fatura.",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const u=document.querySelector(".swal2-container");u&&(u.style.zIndex="99999")}}),await m.App.carregarParcelamentos(),l.parcelamentos.some(u=>u.id===a)?setTimeout(()=>{this.showDetalhes(a)},100):l.modalDetalhesInstance&&l.modalDetalhesInstance.hide()}catch(r){console.error("Erro ao excluir item:",r),Swal.fire({icon:"error",title:"Erro",text:h(r,"Não foi possível excluir o item."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})}},async excluirParcelamentoCompleto(a,e,t){if((await Swal.fire({title:"Excluir Parcelamento Completo?",html:`
                <p>Deseja realmente excluir <strong>todas as ${t} parcelas</strong> deste parcelamento?</p>
                <p style="color: #ef4444; margin-top: 1rem;"><i data-lucide="triangle-alert"></i> Esta ação não pode ser desfeita!</p>
            `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:`Sim, excluir ${t} parcelas`,cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999"),$()}})).isConfirmed){Swal.fire({title:"Excluindo parcelamento...",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const r=document.querySelector(".swal2-container");r&&(r.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});try{const r=await c.apiRequest(`api/faturas/${a}/itens/${e}/parcelamento`,{method:"DELETE"});await Swal.fire({icon:"success",title:"Parcelamento Excluído!",text:r.message||`${t} parcelas removidas.`,timer:b.TIMEOUTS.successMessage,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const i=document.querySelector(".swal2-container");i&&(i.style.zIndex="99999")}}),await m.App.carregarParcelamentos(),l.parcelamentos.some(i=>i.id===a)?setTimeout(()=>{this.showDetalhes(a)},100):l.modalDetalhesInstance&&l.modalDetalhesInstance.hide()}catch(r){console.error("Erro ao excluir parcelamento:",r),Swal.fire({icon:"error",title:"Erro",text:h(r,"Não foi possível excluir o parcelamento."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const s=document.querySelector(".swal2-container");s&&(s.style.zIndex="99999")}})}}},async pagarFaturaCompleta(a,e){try{Swal.fire({title:"Carregando...",html:"Buscando informações da fatura e contas disponíveis.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const[t,o]=await Promise.all([m.API.buscarParcelamento(a),m.API.listarContas()]),r=y(t,null),s=y(o,[]);if(!r?.cartao)throw new Error("Dados da fatura incompletos");const i=r.cartao.id,d=r.cartao.conta_id||null,u=(r.descricao||"").match(/(\d+)\/(\d+)/),v=u?u[1]:null,w=u?u[2]:null;if(!v||!w)throw new Error("Não foi possível identificar o mês/ano da fatura");let E="";if(Array.isArray(s)&&s.length>0)s.forEach(p=>{const I=p.saldoAtual??p.saldo_atual??p.saldo??0,B=c.formatMoney(I),D=p.id===d,P=I>=e,M=P?"color: #059669;":"color: #dc2626;";E+=`<option value="${p.id}" ${D?"selected":""} ${P?"":'style="color: #dc2626;"'}>
                        ${c.escapeHtml(p.nome)} - ${B}${D?" (vinculada ao cartão)":""}
                    </option>`});else throw new Error("Nenhuma conta disponível para débito");const x=await Swal.fire({title:"Pagar Fatura Completa?",html:`
                    <p>Deseja realmente pagar todos os itens pendentes desta fatura?</p>
                    <div style="margin: 1.5rem 0; padding: 1rem; background: #f0fdf4; border-radius: 8px; border-left: 4px solid #10b981;">
                        <div style="font-size: 0.875rem; color: #047857; margin-bottom: 0.5rem;">Valor Total:</div>
                        <div style="font-size: 1.5rem; font-weight: bold; color: #059669;">${c.formatMoney(e)}</div>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="display: block; text-align: left; margin-bottom: 0.5rem; color: #374151; font-weight: 500;">
                            <i data-lucide="landmark"></i> Conta para débito:
                        </label>
                        <select id="swalContaSelect" class="swal2-select" style="width: 100%; padding: 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.875rem;">
                            ${E}
                        </select>
                    </div>
                    <p style="color: #6b7280; font-size: 0.875rem;">O valor será debitado da conta selecionada.</p>
                `,icon:"question",showCancelButton:!0,confirmButtonColor:"#10b981",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="check"></i> Sim, pagar tudo',cancelButtonText:"Cancelar",heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999"),$()},preConfirm:()=>{const p=document.getElementById("swalContaSelect"),I=p?parseInt(p.value):null;return I?{contaId:I}:(Swal.showValidationMessage("Selecione uma conta para débito"),!1)}});if(!x.isConfirmed)return;const F=x.value.contaId;Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento de todos os itens.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>{Swal.showLoading();const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999")},customClass:{container:"swal-above-modal"}});const C=await m.API.pagarFaturaCompleta(i,parseInt(v),parseInt(w),F);if(!C.success)throw new Error(C.message||"Erro ao processar pagamento");await Swal.fire({icon:"success",title:"Fatura Paga!",html:`
                    <p>${C.message||"Fatura paga com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor debitado:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(y(C,{})?.valor_pago||e)}
                        </div>
                    </div>
                    <div style="color: #059669;">
                        <i data-lucide="circle-check" style="font-size: 2rem;"></i>
                    </div>
                `,timer:3e3,showConfirmButton:!1,heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const p=document.querySelector(".swal2-container");p&&(p.style.zIndex="99999"),$()}}),await m.App.carregarParcelamentos(),l.modalDetalhesInstance.hide()}catch(t){console.error("Erro ao pagar fatura completa:",t),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:h(t,"Não foi possível processar o pagamento. Tente novamente."),heightAuto:!1,customClass:{container:"swal-above-modal"},didOpen:()=>{const o=document.querySelector(".swal2-container");o&&(o.style.zIndex="99999")}})}}},S={showLoading(){n.loadingEl.style.display="flex",n.containerEl.style.display="none",n.emptyStateEl.style.display="none"},hideLoading(){n.loadingEl.style.display="none"},showEmpty(){n.containerEl.style.display="none",n.emptyStateEl.style.display="block"},...W,...Q,...Z};m.UI=S;const N={cleanupModalArtifacts(){document.querySelectorAll(".modal.show").length>0||(document.querySelectorAll(".modal-backdrop").forEach(e=>{e.remove()}),document.body.classList.remove("modal-open"),document.body.style.removeProperty("overflow"),document.body.style.removeProperty("padding-right"))},async init(){try{this.initModal(),this.initViewToggle(),this.aplicarFiltrosURL(),await this.carregarCartoes(),await this.carregarParcelamentos(),this.attachEventListeners()}catch(a){console.error("❌ Erro ao inicializar:",a),Swal.fire({icon:"error",title:"Erro de Inicialização",text:"Não foi possível carregar a página. Tente recarregar."})}},initViewToggle(){const a=document.querySelector(".view-toggle"),e=n.containerEl;if(!a||!e)return;const t=a.querySelectorAll(".view-btn"),o=localStorage.getItem("faturas_view_mode")||"grid";o==="list"&&e.classList.add("list-view"),this.updateViewToggleState(t,o);const r=document.getElementById("faturasListHeader");o==="list"&&r&&r.classList.add("visible"),t.forEach(s=>{s.addEventListener("click",()=>{const i=s.dataset.view;i==="list"?(e.classList.add("list-view"),r&&r.classList.add("visible")):(e.classList.remove("list-view"),r&&r.classList.remove("visible")),localStorage.setItem("faturas_view_mode",i),this.updateViewToggleState(t,i)})})},updateViewToggleState(a,e){a.forEach(t=>{t.dataset.view===e?t.classList.add("active"):t.classList.remove("active")})},initModal(){n.modalDetalhes&&(l.modalDetalhesInstance=bootstrap.Modal.getOrCreateInstance(n.modalDetalhes,{backdrop:!0,keyboard:!0,focus:!0}),n.modalDetalhes.addEventListener("show.bs.modal",()=>{document.activeElement?.blur()}),n.modalDetalhes.addEventListener("hidden.bs.modal",()=>{document.activeElement?.blur(),l.faturaAtual=null,this.cleanupModalArtifacts()}),n.modalDetalhes.addEventListener("click",a=>{const e=a.target.closest(".btn-ver-detalhes-parcela");if(e){a.preventDefault();const t=JSON.parse(e.dataset.parcela),o=e.dataset.descricao;this.mostrarDetalhesParcela(t,o)}}))},aplicarFiltrosURL(){const a=new URLSearchParams(window.location.search);if(a.has("cartao_id")&&(l.filtros.cartao_id=a.get("cartao_id"),n.filtroCartao&&(n.filtroCartao.value=l.filtros.cartao_id)),a.has("mes")&&a.has("ano")&&(l.filtros.mes=parseInt(a.get("mes"),10),l.filtros.ano=parseInt(a.get("ano"),10),window.monthPicker)){const e=new Date(l.filtros.ano,l.filtros.mes-1);window.monthPicker.setDate(e)}a.has("status")&&(l.filtros.status=a.get("status"),n.filtroStatus&&(n.filtroStatus.value=l.filtros.status))},async carregarCartoes(){try{const a=await m.API.listarCartoes(),e=y(a,[]);l.cartoes=Array.isArray(e)?e:[],this.preencherSelectCartoes(),this.sincronizarFiltrosComSelects()}catch(a){console.error("❌ Erro ao carregar cartões:",a)}},sincronizarFiltrosComSelects(){n.filtroStatus&&l.filtros.status&&(n.filtroStatus.value=l.filtros.status),n.filtroCartao&&l.filtros.cartao_id&&(n.filtroCartao.value=l.filtros.cartao_id),n.filtroAno&&l.filtros.ano&&(n.filtroAno.value=l.filtros.ano),n.filtroMes&&l.filtros.mes&&(n.filtroMes.value=l.filtros.mes)},preencherSelectCartoes(){n.filtroCartao&&(n.filtroCartao.innerHTML='<option value="">Todos os cartões</option>',l.cartoes.forEach(a=>{const e=document.createElement("option");e.value=a.id;const t=a.nome_cartao||a.nome||a.bandeira||"Cartão",o=a.ultimos_digitos?` •••• ${a.ultimos_digitos}`:"";e.textContent=t+o,n.filtroCartao.appendChild(e)}))},preencherSelectAnos(a=[]){if(!n.filtroAno)return;const e=n.filtroAno.value,t=new Date().getFullYear();if(n.filtroAno.innerHTML='<option value="">Todos os anos</option>',a.length>0){const o=[...a].sort((r,s)=>r-s);o.includes(t)||(o.push(t),o.sort((r,s)=>r-s)),o.forEach(r=>{const s=document.createElement("option");s.value=r,s.textContent=r,n.filtroAno.appendChild(s)})}else{const o=document.createElement("option");o.value=t,o.textContent=t,n.filtroAno.appendChild(o)}e?n.filtroAno.value=e:(n.filtroAno.value=t,l.filtros.ano=t),this.sincronizarFiltrosComSelects()},extrairAnosDisponiveis(a){const e=new Set;return a.forEach(t=>{const r=(t.descricao||"").match(/(\d{1,2})\/(\d{4})/);if(r&&e.add(parseInt(r[2],10)),t.data_vencimento){const s=new Date(t.data_vencimento).getFullYear();e.add(s)}}),Array.from(e)},async carregarParcelamentos(){m.UI.showLoading();try{const a=await m.API.listarParcelamentos({status:l.filtros.status||"",cartao_id:l.filtros.cartao_id||"",mes:l.filtros.mes||"",ano:l.filtros.ano||""}),e=y(a,{});let t=e?.faturas||[];if(l.parcelamentos=t,!l.anosCarregados){const o=e?.anos_disponiveis||this.extrairAnosDisponiveis(t);this.preencherSelectAnos(o),l.anosCarregados=!0}m.UI.renderParcelamentos(t)}catch(a){console.error("❌ Erro ao carregar parcelamentos:",a),m.UI.showEmpty(),Swal.fire({icon:"error",title:"Erro ao Carregar",text:h(a,"Não foi possível carregar os parcelamentos")})}finally{m.UI.hideLoading()}},async cancelarParcelamento(a){try{await m.API.cancelarParcelamento(a),await Swal.fire({icon:"success",title:"Cancelado!",text:"Parcelamento cancelado com sucesso",timer:b.TIMEOUTS.successMessage,showConfirmButton:!1}),await this.carregarParcelamentos()}catch(e){console.error("Erro ao cancelar:",e),Swal.fire({icon:"error",title:"Erro ao Cancelar",text:h(e,"Não foi possível cancelar o parcelamento")})}},attachEventListeners(){n.toggleFilters&&n.toggleFilters.addEventListener("click",o=>{o.stopPropagation(),this.toggleFilters()});const a=document.querySelector(".filters-header");a&&a.addEventListener("click",()=>{this.toggleFilters()}),n.btnFiltrar&&n.btnFiltrar.addEventListener("click",()=>{this.aplicarFiltros()}),n.btnLimparFiltros&&n.btnLimparFiltros.addEventListener("click",()=>{this.limparFiltros()}),[n.filtroStatus,n.filtroCartao,n.filtroAno,n.filtroMes].forEach(o=>{o&&o.addEventListener("keypress",r=>{r.key==="Enter"&&this.aplicarFiltros()})});const e=document.getElementById("btnSalvarItemFatura");e&&e.addEventListener("click",()=>{m.UI.salvarItemFatura()});const t=document.getElementById("formEditarItemFatura");t&&t.addEventListener("submit",o=>{o.preventDefault(),m.UI.salvarItemFatura()})},toggleFilters(){n.filtersContainer&&n.filtersContainer.classList.toggle("collapsed")},aplicarFiltros(){l.filtros.status=n.filtroStatus?.value||"",l.filtros.cartao_id=n.filtroCartao?.value||"",l.filtros.ano=n.filtroAno?.value||"",l.filtros.mes=n.filtroMes?.value||"",this.atualizarBadgesFiltros(),this.carregarParcelamentos()},limparFiltros(){n.filtroStatus&&(n.filtroStatus.value=""),n.filtroCartao&&(n.filtroCartao.value=""),n.filtroAno&&(n.filtroAno.value=""),n.filtroMes&&(n.filtroMes.value=""),l.filtros={status:"",cartao_id:"",ano:"",mes:""},this.atualizarBadgesFiltros(),this.carregarParcelamentos()},atualizarBadgesFiltros(){if(!n.activeFilters)return;const a=[],e=["","Janeiro","Fevereiro","Março","Abril","Maio","Junho","Julho","Agosto","Setembro","Outubro","Novembro","Dezembro"];if(l.filtros.status){const t={pendente:"⏳ Pendente",parcial:"🔄 Parcial",paga:"✅ Paga",cancelado:"❌ Cancelado"};a.push({key:"status",label:t[l.filtros.status]||l.filtros.status})}if(l.filtros.cartao_id){const t=l.cartoes.find(r=>r.id==l.filtros.cartao_id),o=t?t.nome_cartao||t.nome:"Cartão";a.push({key:"cartao_id",label:`💳 ${o}`})}l.filtros.ano&&a.push({key:"ano",label:`📅 ${l.filtros.ano}`}),l.filtros.mes&&a.push({key:"mes",label:`📆 ${e[l.filtros.mes]}`}),a.length>0?(n.activeFilters.style.display="flex",n.activeFilters.innerHTML=a.map(t=>`
                <span class="filter-badge">
                    ${t.label}
                    <button class="filter-badge-remove" data-filter="${t.key}" title="Remover filtro">
                        <i data-lucide="x"></i>
                    </button>
                </span>
            `).join(""),window.lucide&&lucide.createIcons(),n.activeFilters.querySelectorAll(".filter-badge-remove").forEach(t=>{t.addEventListener("click",o=>{const r=o.currentTarget.dataset.filter;this.removerFiltro(r)})})):(n.activeFilters.style.display="none",n.activeFilters.innerHTML="")},removerFiltro(a){l.filtros[a]="";const e={status:n.filtroStatus,cartao_id:n.filtroCartao,ano:n.filtroAno,mes:n.filtroMes};e[a]&&(e[a].value=""),this.atualizarBadgesFiltros(),this.carregarParcelamentos()}};m.App=N;const L={instance:null,faturaId:null,valorTotal:null,cartaoId:null,mes:null,ano:null,contas:[],contaPadraoId:null,init(){const a=n.modalPagarFatura||document.getElementById("modalPagarFatura");a&&(this.instance=bootstrap.Modal.getOrCreateInstance(a,{backdrop:!0,keyboard:!0,focus:!0}),this.attachEvents())},attachEvents(){document.getElementById("btnPagarTotal")?.addEventListener("click",()=>{this.instance.hide(),m.UI.pagarFaturaCompleta(this.faturaId,this.valorTotal)}),document.getElementById("btnPagarParcial")?.addEventListener("click",()=>{this.mostrarFormularioParcial()}),document.getElementById("btnVoltarEscolha")?.addEventListener("click",()=>{this.mostrarEscolha()}),document.getElementById("btnConfirmarPagamento")?.addEventListener("click",()=>{this.confirmarPagamentoParcial()});const a=document.getElementById("valorPagamentoParcial");a&&(a.addEventListener("input",e=>{let t=e.target.value.replace(/\D/g,"");if(t===""){e.target.value="";return}t=(parseInt(t)/100).toFixed(2),e.target.value=parseFloat(t).toLocaleString("pt-BR",{minimumFractionDigits:2,maximumFractionDigits:2})}),a.addEventListener("focus",e=>{e.target.select()})),document.querySelectorAll(".btn-opcao-pagamento").forEach(e=>{e.addEventListener("mouseenter",()=>{e.style.transform="translateY(-2px)",e.style.boxShadow="0 8px 25px rgba(0,0,0,0.2)"}),e.addEventListener("mouseleave",()=>{e.style.transform="translateY(0)",e.style.boxShadow="none"})})},async abrir(a,e){this.faturaId=a,this.valorTotal=e,document.getElementById("pagarFaturaId").value=a,document.getElementById("pagarFaturaValorTotal").value=e,document.getElementById("valorTotalDisplay").textContent=c.formatMoney(e),document.getElementById("valorTotalInfo").textContent=`Valor total da fatura: ${c.formatMoney(e)}`,document.getElementById("valorPagamentoParcial").value=c.formatMoney(e).replace("R$ ",""),this.mostrarEscolha(),await this.carregarDados(),this.instance.show()},async carregarDados(){try{const[a,e]=await Promise.all([m.API.buscarParcelamento(this.faturaId),m.API.listarContas()]),t=y(a,null);if(this.contas=y(e,[]),!t?.cartao)throw new Error("Dados da fatura incompletos");this.cartaoId=t.cartao.id,this.contaPadraoId=t.cartao.conta_id||null;const r=(t.descricao||"").match(/(\d+)\/(\d+)/);this.mes=r?parseInt(r[1]):null,this.ano=r?parseInt(r[2]):null,this.popularSelectContas()}catch(a){console.error("Erro ao carregar dados:",a),Swal.fire({icon:"error",title:"Erro",text:h(a,"Erro ao carregar dados da fatura.")})}},popularSelectContas(){const a=document.getElementById("contaPagamentoFatura");if(a){if(a.innerHTML="",!Array.isArray(this.contas)||this.contas.length===0){a.innerHTML='<option value="">Nenhuma conta disponível</option>';return}this.contas.forEach(e=>{const t=e.saldoAtual??e.saldo_atual??e.saldo??0,o=c.formatMoney(t),r=e.id===this.contaPadraoId,s=document.createElement("option");s.value=e.id,s.textContent=`${e.nome} - ${o}${r?" (vinculada ao cartão)":""}`,r&&(s.selected=!0),a.appendChild(s)})}},mostrarEscolha(){document.getElementById("pagarFaturaEscolha").style.display="block",document.getElementById("pagarFaturaFormParcial").style.display="none",document.getElementById("pagarFaturaFooter").style.display="none"},mostrarFormularioParcial(){document.getElementById("pagarFaturaEscolha").style.display="none",document.getElementById("pagarFaturaFormParcial").style.display="block",document.getElementById("pagarFaturaFooter").style.display="flex",setTimeout(()=>{const a=document.getElementById("valorPagamentoParcial");a&&(a.focus(),a.select())},100)},async confirmarPagamentoParcial(){const a=document.getElementById("valorPagamentoParcial").value,e=document.getElementById("contaPagamentoFatura").value,t=parseFloat(a.replace(/\./g,"").replace(",","."))||0;if(t<=0){Swal.fire({icon:"warning",title:"Valor inválido",text:"Digite um valor válido para o pagamento.",timer:2e3,showConfirmButton:!1});return}if(t>this.valorTotal){Swal.fire({icon:"warning",title:"Valor inválido",text:`O valor não pode ser maior que ${c.formatMoney(this.valorTotal)}`,timer:2e3,showConfirmButton:!1});return}if(!e){Swal.fire({icon:"warning",title:"Conta não selecionada",text:"Selecione uma conta para débito.",timer:2e3,showConfirmButton:!1});return}if(!this.cartaoId||!this.mes||!this.ano){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos. Tente novamente."});return}this.instance.hide(),Swal.fire({title:"Processando pagamento...",html:"Aguarde enquanto processamos o pagamento.",allowOutsideClick:!1,heightAuto:!1,didOpen:()=>Swal.showLoading()});try{const o=await c.apiRequest(`api/cartoes/${this.cartaoId}/fatura/pagar`,{method:"POST",body:JSON.stringify({mes:this.mes,ano:this.ano,conta_id:parseInt(e),valor_parcial:t})});if(!o.success)throw new Error(h(o,"Erro ao processar pagamento"));await Swal.fire({icon:"success",title:"Pagamento Realizado!",html:`
                    <p>${o.message||"Pagamento efetuado com sucesso!"}</p>
                    <div style="margin: 1rem 0; padding: 0.75rem; background: #f0fdf4; border-radius: 8px;">
                        <div style="font-size: 0.875rem; color: #047857;">Valor pago:</div>
                        <div style="font-size: 1.25rem; font-weight: bold; color: #059669;">
                            ${c.formatMoney(t)}
                        </div>
                    </div>
                `,timer:3e3,showConfirmButton:!1}),await m.App.carregarParcelamentos(),l.modalDetalhesInstance&&l.modalDetalhesInstance.hide()}catch(o){console.error("Erro ao pagar fatura:",o),Swal.fire({icon:"error",title:"Erro ao pagar fatura",text:h(o,"Não foi possível processar o pagamento. Tente novamente.")})}}};async function aa(a){const e=l.faturaAtual;if(!e||!e.cartao||!e.mes_referencia||!e.ano_referencia){Swal.fire({icon:"error",title:"Erro",text:"Dados da fatura incompletos para reverter o pagamento."});return}if((await Swal.fire({title:"Desfazer Pagamento?",html:`
            <p>Você está prestes a <strong>reverter o pagamento</strong> de todos os itens desta fatura.</p>
            <div style="margin: 1rem 0; padding: 0.75rem; background: #fef3c7; border-radius: 8px; border-left: 4px solid #f59e0b;">
                <p style="margin: 0; color: #92400e; font-size: 0.875rem;">
                    <i data-lucide="triangle-alert"></i> 
                    O lançamento de pagamento será excluído e o valor voltará para a conta.
                </p>
            </div>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#f59e0b",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="undo-2"></i> Sim, reverter',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{Swal.fire({title:"Revertendo pagamento...",html:"Aguarde enquanto processamos a reversão.",allowOutsideClick:!1,didOpen:()=>Swal.showLoading()});const o=e.cartao.id,r=e.mes_referencia,s=e.ano_referencia,i=await c.apiRequest(`api/cartoes/${o}/fatura/desfazer-pagamento`,{method:"POST",body:JSON.stringify({mes:r,ano:s})});if(i.success)await Swal.fire({icon:"success",title:"Pagamento Revertido!",html:`
                    <p>${i.message||"O pagamento foi revertido com sucesso."}</p>
                    <p style="color: #059669; margin-top: 0.5rem;">
                        <i data-lucide="circle-check"></i> 
                        ${y(i,{})?.itens_revertidos||0} item(s) voltou(aram) para pendente.
                    </p>
                `,timer:3e3,showConfirmButton:!1,didOpen:()=>{window.lucide&&lucide.createIcons()}}),l.modalDetalhesInstance&&l.modalDetalhesInstance.hide(),await m.App.carregarParcelamentos();else throw new Error(h(i,"Erro ao reverter pagamento"))}catch(o){console.error("Erro ao reverter pagamento:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Não foi possível reverter o pagamento.")})}}async function ea(a){if((await Swal.fire({title:"Excluir Fatura?",html:`
            <p>Você está prestes a excluir esta fatura e <strong>todos os seus itens pendentes</strong>.</p>
            <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:"Cancelar",didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{const t=await c.apiRequest(`api/faturas/${a}`,{method:"DELETE"});if(t.success)Swal.fire({icon:"success",title:"Fatura Excluída!",text:"A fatura foi excluída com sucesso.",timer:2e3,showConfirmButton:!1}),l.modalDetalhesInstance&&l.modalDetalhesInstance.hide(),m.App.carregarParcelamentos();else throw new Error(h(t,"Erro ao excluir fatura"))}catch(t){console.error("Erro ao excluir fatura:",t),Swal.fire({icon:"error",title:"Erro",text:h(t,"Não foi possível excluir a fatura.")})}}async function ta(a,e){if((await Swal.fire({title:"Excluir Item?",html:`
            <p>Você está prestes a excluir este item da fatura.</p>
            <p style="color: #ef4444; font-weight: 500;">Esta ação não pode ser desfeita!</p>
        `,icon:"warning",showCancelButton:!0,confirmButtonColor:"#ef4444",cancelButtonColor:"#6b7280",confirmButtonText:'<i data-lucide="trash-2"></i> Sim, excluir',cancelButtonText:"Cancelar",customClass:{container:"swal-above-modal"},didOpen:()=>{window.lucide&&lucide.createIcons()}})).isConfirmed)try{const o=await c.apiRequest(`api/faturas/${a}/itens/${e}`,{method:"DELETE"});if(o.success)Swal.fire({icon:"success",title:"Item Excluído!",text:"O item foi excluído com sucesso.",timer:2e3,showConfirmButton:!1,customClass:{container:"swal-above-modal"}}),m.App.carregarParcelamentos(),l.faturaAtual&&setTimeout(()=>{m.UI.abrirDetalhes(a)},500);else throw new Error(h(o,"Erro ao excluir item"))}catch(o){console.error("Erro ao excluir item:",o),Swal.fire({icon:"error",title:"Erro",text:h(o,"Não foi possível excluir o item."),customClass:{container:"swal-above-modal"}})}}m.ModalPagarFatura=L;const oa={toggleFaturasHero:"faturasHero",toggleFaturasFiltros:"faturasFilters",toggleFaturasViewToggle:"faturasViewToggle"},R={toggleFaturasHero:!0,toggleFaturasFiltros:!0,toggleFaturasViewToggle:!0},ra={...R,toggleFaturasFiltros:!1,toggleFaturasViewToggle:!1};async function sa(){return J("faturas")}async function na(a){await j("faturas",a)}const T=H({storageKey:"lk_faturas_prefs",sectionMap:oa,completeDefaults:R,essentialDefaults:ra,loadPreferences:sa,savePreferences:na,modal:{overlayId:"faturasCustomizeModalOverlay",openButtonId:"btnCustomizeFaturas",closeButtonId:"btnCloseCustomizeFaturas",saveButtonId:"btnSaveCustomizeFaturas",presetEssentialButtonId:"btnPresetEssencialFaturas",presetCompleteButtonId:"btnPresetCompletoFaturas"}});function q(){T.init()}m.Customize={init:q,open:T.open,close:T.close};window.abrirModalPagarFatura=(a,e)=>L.abrir(a,e);window.reverterPagamentoFaturaGlobal=aa;window.excluirFaturaGlobal=ea;window.excluirItemFaturaGlobal=ta;window.pagarFaturaCompletaGlobal=(a,e)=>S.pagarFaturaCompleta(a,e);window.FaturasModule={toggleCardDetalhes:a=>S.showDetalhes(a),excluirItemFatura:(...a)=>S.excluirItemFatura(...a),editarItemFatura:(...a)=>S.editarItemFatura(...a),toggleParcelaPaga:(...a)=>S.toggleParcelaPaga(...a)};window.__LK_PARCELAMENTOS_LOADER__||(window.__LK_PARCELAMENTOS_LOADER__=!0,document.addEventListener("DOMContentLoaded",()=>{K(),q(),N.init(),L.init()}));
