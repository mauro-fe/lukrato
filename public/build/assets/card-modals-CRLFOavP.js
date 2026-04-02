import{b as _,a as x,e as C}from"./api-CiEmwEpk.js";import{e as d}from"./utils-Bj4jxwhy.js";function s(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(Number(e)||0)}function p(e,a=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(e)||"").trim()||a}catch{return a}}const k={"check-circle":"circle-check","exclamation-triangle":"triangle-alert","arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","info-circle":"info","times-circle":"x-circle",lightbulb:"lightbulb","chart-line":"line-chart"},h=e=>{const a=e.replace(/^fa-/,"");return k[a]||a};function v(e){return!e||e.length===0?'<div class="empty-message"><i data-lucide="inbox"></i><p>Nenhum lançamento neste mês</p></div>':e.map(a=>`
        <div class="lancamento-row">
            <div class="lancamento-left">
                <div class="lancamento-category" style="background: ${a.categoria_cor}20; color: ${a.categoria_cor};">
                    ${d(a.categoria)}
                </div>
                <div class="lancamento-description">
                    ${d(a.descricao)}
                    ${a.eh_parcelado?`<span class="parcela-tag">${a.parcela_info}</span>`:""}
                </div>
                <div class="lancamento-date">${new Date(a.data.split(" ")[0]+"T00:00:00").toLocaleDateString("pt-BR")}</div>
            </div>
            <div class="lancamento-amount">${s(a.valor)}</div>
        </div>
    `).join("")}function f(e){return Math.abs(e.absoluta)<=1?"":`
        <span class="comparison-label">vs mês anterior</span>
        <span class="comparison-value ${e.absoluta>0?"negative":"positive"}">
            ${e.absoluta>0?"↑":"↓"} 
            ${s(Math.abs(e.absoluta))} 
            (${e.percentual>0?"+":""}${e.percentual.toFixed(1)}%)
        </span>
    `}function E(e){return`
        <table class="parcelamentos-table">
            <thead><tr><th>Compra</th><th>Categoria</th><th>Progresso</th><th>Valor/Mês</th><th>Restante</th><th>Término</th></tr></thead>
            <tbody>
                ${e.map(a=>{const r=(a.total_parcelas-a.parcelas_restantes)/a.total_parcelas*100;return`<tr>
                        <td><strong>${d(a.descricao)}</strong></td>
                        <td><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${a.categoria_cor};margin-right:.5rem;"></span>${d(a.categoria)}</td>
                        <td><div class="parcela-progress"><span style="font-size:.75rem;color:var(--color-text-muted);">${a.total_parcelas-a.parcelas_restantes}/${a.total_parcelas}</span><div class="parcela-bar"><div class="parcela-bar-fill" style="width:${r}%;background:${a.categoria_cor};"></div></div></div></td>
                        <td>${s(a.valor_parcela)}</td>
                        <td><strong>${s(a.valor_total_restante)}</strong></td>
                        <td style="font-size:.875rem;color:var(--color-text-muted);">${a.data_final}</td>
                    </tr>`}).join("")}
            </tbody>
        </table>
    `}function M(e){return e.map(a=>{const r=(a.total_parcelas-a.parcelas_restantes)/a.total_parcelas*100;return`
            <div class="parcelamento-card-mobile">
                <div class="parcelamento-card-header">
                    <div class="parcelamento-card-title">
                        <span class="categoria-dot" style="background:${a.categoria_cor};"></span>
                        <strong>${d(a.descricao)}</strong>
                    </div>
                    <button class="btn-ver-detalhes" onclick="this.closest('.parcelamento-card-mobile').classList.toggle('expanded')">
                        <i data-lucide="chevron-down"></i><span>Detalhes</span>
                    </button>
                </div>
                <div class="parcelamento-card-summary">
                    <span class="valor-mensal">${s(a.valor_parcela)}/mês</span>
                    <span class="parcelas-info">${a.total_parcelas-a.parcelas_restantes}/${a.total_parcelas} parcelas</span>
                </div>
                <div class="parcelamento-card-progress">
                    <div class="parcela-bar"><div class="parcela-bar-fill" style="width:${r}%;background:${a.categoria_cor};"></div></div>
                </div>
                <div class="parcelamento-card-details">
                    <div class="detail-row"><span class="detail-label">Categoria</span><span class="detail-value"><span class="categoria-dot" style="background:${a.categoria_cor};"></span>${d(a.categoria)}</span></div>
                    <div class="detail-row"><span class="detail-label">Valor por Parcela</span><span class="detail-value">${s(a.valor_parcela)}</span></div>
                    <div class="detail-row"><span class="detail-label">Total Restante</span><span class="detail-value highlight">${s(a.valor_total_restante)}</span></div>
                    <div class="detail-row"><span class="detail-label">Término Previsto</span><span class="detail-value">${a.data_final}</span></div>
                </div>
            </div>
        `}).join("")}function b(e){return!e||e.quantidade===0?'<div class="empty-message"><i data-lucide="circle-check"></i><p>Nenhum parcelamento ativo</p></div>':`
        <div class="parcelamentos-table-wrapper">${E(e.ativos)}</div>
        <div class="parcelamentos-mobile-list">${M(e.ativos)}</div>
    `}function y(e){if(!e)return"";const a=[];return e.tendencia&&a.push(`<div class="insight-card insight-${e.tendencia.type}"><div class="insight-icon"><i data-lucide="${h(e.tendencia.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Tendência</span><span class="insight-badge">${e.tendencia.variacao}</span></div><h4 class="insight-status">${e.tendencia.status}</h4><p class="insight-desc">${e.tendencia.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${e.tendencia.recomendacao}</p></div></div>`),e.parcelamentos&&a.push(`<div class="insight-card insight-${e.parcelamentos.type}"><div class="insight-icon"><i data-lucide="${h(e.parcelamentos.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Parcelamentos</span><span class="insight-badge">${e.parcelamentos.valor}</span></div><h4 class="insight-status">${e.parcelamentos.status}</h4><p class="insight-desc">${e.parcelamentos.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${e.parcelamentos.recomendacao}</p></div></div>`),e.limite&&a.push(`<div class="insight-card insight-${e.limite.type}"><div class="insight-icon"><i data-lucide="${h(e.limite.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Uso do Limite</span><span class="insight-badge">${e.limite.percentual}</span></div><h4 class="insight-status">${e.limite.status}</h4><p class="insight-desc">${e.limite.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${e.limite.recomendacao}</p></div></div>`),a.length===0?"":`
        <div class="insights-header"><i data-lucide="lightbulb"></i><h3>Análise Inteligente</h3></div>
        <div class="insights-grid">${a.join("")}</div>
    `}let l=null,c=null;async function S(e,a,r,i){if(!e){console.error("ID do cartão não fornecido");return}const t=document.querySelector(`[data-action="open-card-detail"][data-card-id="${e}"].card-action-btn`),o=t?.innerHTML;t&&(t.disabled=!0,t.innerHTML='<i data-lucide="loader-2" class="icon-spin"></i> <span>Carregando...</span>',window.lucide&&window.lucide.createIcons({nodes:[t]}));try{const[n,m]=i.split("-"),$=new URLSearchParams({mes:m,ano:n}),w=`${_()}api/reports/card-details/${e}?${$}`,u=await x(w,{credentials:"include"},{timeout:15e3});if(!u.success||!u.data)throw new Error(u.message||"Dados inválidos retornados");T(u.data,r)}catch(n){console.error("Erro ao abrir detalhes do cartão:",n),document.body.style.overflow="";const m=C(n,"Não foi possível carregar os detalhes do cartão. Tente novamente.");typeof Swal<"u"&&Swal.fire({icon:"error",title:"Erro ao carregar",text:m,confirmButtonColor:"#e67e22"})}finally{t&&(t.disabled=!1,o&&(t.innerHTML=o),window.lucide&&window.lucide.createIcons({nodes:[t]}))}}function T(e,a){const r=document.getElementById("cardDetailModalOverlay");r&&(r.remove(),document.body.style.overflow="");const i=document.getElementById("cardDetailModalTemplate");if(!i){console.error("Template do modal não encontrado");return}const t=document.createElement("div");t.id="cardDetailModalOverlay",t.className="card-detail-modal-overlay",t.appendChild(i.content.cloneNode(!0));try{q(t,e,a)}catch(n){console.error("Erro ao popular o modal:",n);return}Object.assign(t.style,{position:"fixed",top:"0",left:"0",width:"100vw",height:"100vh",zIndex:"9999999",display:"flex",alignItems:"flex-start",justifyContent:"center",overflowY:"auto",background:"rgba(0, 0, 0, 0.7)",backdropFilter:"blur(4px)",WebkitBackdropFilter:"blur(4px)"}),document.body.style.overflow="hidden",document.body.appendChild(t),t.scrollTop=0,t.addEventListener("click",n=>{n.target===t&&g()});const o=n=>{n.key==="Escape"&&(g(),document.removeEventListener("keydown",o))};document.addEventListener("keydown",o),setTimeout(()=>{try{window.lucide&&window.lucide.createIcons({nodes:[t]}),D(e.evolucao?.meses),L(e.impacto_futuro?.meses)}catch(n){console.error("Erro ao renderizar gráficos do modal:",n)}},100)}function q(e,a,r){e.querySelector("[data-color]").style.background=`linear-gradient(135deg, ${r}, ${r}DD)`,e.querySelector("[data-cartao-nome]").textContent=a.cartao.nome,e.querySelector("[data-periodo]").textContent=`${a.fatura_mes.mes}/${a.fatura_mes.ano}`,e.querySelector("[data-fatura-total]").textContent=s(a.fatura_mes.total),e.querySelector("[data-limite]").textContent=s(a.cartao.limite),e.querySelector("[data-disponivel]").textContent=s(a.cartao.limite_disponivel),e.querySelector("[data-utilizacao]").textContent=`${(a.cartao.percentual_utilizacao_geral||0).toFixed(1)}%`;const i=a.fatura_mes.lancamentos.length;e.querySelector("[data-lancamentos-count]").textContent=`${i} ${i===1?"lançamento":"lançamentos"}`,e.querySelector("[data-lancamentos-list]").innerHTML=v(a.fatura_mes.lancamentos),e.querySelector("[data-a-vista]").textContent=s(a.fatura_mes.a_vista),e.querySelector("[data-parcelado]").textContent=s(a.fatura_mes.parcelado),e.querySelector("[data-total]").textContent=s(a.fatura_mes.total);const t=e.querySelector("[data-comparison]");Math.abs(a.fatura_mes.diferenca_absoluta)>1&&(t.innerHTML=f({absoluta:a.fatura_mes.diferenca_absoluta,percentual:a.fatura_mes.diferenca_percentual}),t.style.display="block");const o=e.querySelector("[data-tendencia]");o.className=`tendencia-indicator ${a.evolucao.tendencia}`,o.innerHTML=`
        <i data-lucide="${a.evolucao.tendencia==="subindo"?"arrow-up":a.evolucao.tendencia==="caindo"?"arrow-down":"arrow-right"}"></i>
        ${a.evolucao.tendencia.charAt(0).toUpperCase()+a.evolucao.tendencia.slice(1)}
    `,window.lucide&&window.lucide.createIcons({nodes:[o]}),e.querySelector("[data-media]").textContent=s(a.evolucao.media);const n=e.querySelector("[data-comprometido]");a.parcelamentos.quantidade>0&&(n.textContent=`${s(a.parcelamentos.total_comprometido)} comprometidos`,n.style.display="inline-block"),e.querySelector("[data-parcelamentos-content]").innerHTML=b(a.parcelamentos);const m=e.querySelector("[data-insights]");a.insights&&(m.innerHTML=y(a.insights),m.style.display="block")}function g(){const e=document.getElementById("cardDetailModalOverlay");e&&(e.style.opacity="0",e.style.transition="opacity 0.25s ease",document.body.style.overflow="",l&&(l.destroy(),l=null),c&&(c.destroy(),c=null),setTimeout(()=>e.remove(),300))}function D(e){const a=document.getElementById("evolutionChart");if(!a)return;l&&(l.destroy(),l=null);const r=document.documentElement.getAttribute("data-theme")==="dark",i=p("--color-text-muted","#999"),t=p("--glass-border","rgba(255,255,255,0.1)");l=new ApexCharts(a,{chart:{type:"area",height:260,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Fatura",data:e.map(o=>Number(o.valor)||0)}],xaxis:{categories:e.map(o=>o.mes),labels:{style:{colors:i}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{min:0,labels:{style:{colors:i},formatter:o=>s(o)}},colors:["#E67E22"],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:t,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:r?"dark":"light",y:{formatter:o=>s(o)}},legend:{show:!1},dataLabels:{enabled:!1},theme:{mode:r?"dark":"light"}}),l.render()}function L(e){const a=document.getElementById("impactChart");if(!a)return;c&&(c.destroy(),c=null);const r=document.documentElement.getAttribute("data-theme")==="dark",i=p("--color-text-muted","#999"),t=p("--glass-border","rgba(255,255,255,0.1)");c=new ApexCharts(a,{chart:{type:"bar",height:260,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Projeção",data:e.map(o=>Number(o.valor)||0)}],xaxis:{categories:e.map(o=>o.mes),labels:{style:{colors:i}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{min:0,labels:{style:{colors:i},formatter:o=>s(o)}},colors:["#3498DB"],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:t,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:r?"dark":"light",y:{formatter:o=>s(o)}},legend:{show:!1},dataLabels:{enabled:!1},theme:{mode:r?"dark":"light"}}),c.render()}window.LK_CardDetail={open:S,close:g};window.CardModalRenderers={renderLancamentos:v,renderComparison:f,renderParcelamentos:b,renderInsights:y,formatCurrency:s,escapeHtml:d};
