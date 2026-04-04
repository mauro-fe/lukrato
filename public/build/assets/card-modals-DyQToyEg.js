import{b as _,a as x,e as C}from"./api-CiEmwEpk.js";import{e as m}from"./utils-Bj4jxwhy.js";function o(a){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(Number(a)||0)}function p(a,e=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(a)||"").trim()||e}catch{return e}}const k={"check-circle":"circle-check","exclamation-triangle":"triangle-alert","arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","info-circle":"info","times-circle":"x-circle",lightbulb:"lightbulb","chart-line":"line-chart"},h=a=>{const e=a.replace(/^fa-/,"");return k[e]||e};function g(a){return!a||a.length===0?'<div class="empty-message"><i data-lucide="inbox"></i><p>Nenhum lançamento neste mês</p></div>':a.map(e=>`
        <div class="lancamento-row">
            <div class="lancamento-left">
                <div class="lancamento-category" style="background: ${e.categoria_cor}20; color: ${e.categoria_cor};">
                    ${m(e.categoria)}
                </div>
                <div class="lancamento-description">
                    ${m(e.descricao)}
                    ${e.eh_parcelado?`<span class="parcela-tag">${e.parcela_info}</span>`:""}
                </div>
                <div class="lancamento-date">${new Date(e.data.split(" ")[0]+"T00:00:00").toLocaleDateString("pt-BR")}</div>
            </div>
            <div class="lancamento-amount">${o(e.valor)}</div>
        </div>
    `).join("")}function f(a){return Math.abs(a.absoluta)<=1?"":`
        <span class="comparison-label">vs mês anterior</span>
        <span class="comparison-value ${a.absoluta>0?"negative":"positive"}">
            ${a.absoluta>0?"↑":"↓"} 
            ${o(Math.abs(a.absoluta))} 
            (${a.percentual>0?"+":""}${a.percentual.toFixed(1)}%)
        </span>
    `}function S(a){return`
        <table class="parcelamentos-table">
            <thead><tr><th>Compra</th><th>Categoria</th><th>Progresso</th><th>Valor/Mês</th><th>Restante</th><th>Término</th></tr></thead>
            <tbody>
                ${a.map(e=>{const s=(e.total_parcelas-e.parcelas_restantes)/e.total_parcelas*100;return`<tr>
                        <td><strong>${m(e.descricao)}</strong></td>
                        <td><span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${e.categoria_cor};margin-right:.5rem;"></span>${m(e.categoria)}</td>
                        <td><div class="parcela-progress"><span style="font-size:.75rem;color:var(--color-text-muted);">${e.total_parcelas-e.parcelas_restantes}/${e.total_parcelas}</span><div class="parcela-bar"><div class="parcela-bar-fill" style="width:${s}%;background:${e.categoria_cor};"></div></div></div></td>
                        <td>${o(e.valor_parcela)}</td>
                        <td><strong>${o(e.valor_total_restante)}</strong></td>
                        <td style="font-size:.875rem;color:var(--color-text-muted);">${e.data_final}</td>
                    </tr>`}).join("")}
            </tbody>
        </table>
    `}function E(a){return a.map(e=>{const s=(e.total_parcelas-e.parcelas_restantes)/e.total_parcelas*100;return`
            <div class="parcelamento-card-mobile">
                <div class="parcelamento-card-header">
                    <div class="parcelamento-card-title">
                        <span class="categoria-dot" style="background:${e.categoria_cor};"></span>
                        <strong>${m(e.descricao)}</strong>
                    </div>
                    <button class="btn-ver-detalhes" onclick="this.closest('.parcelamento-card-mobile').classList.toggle('expanded')">
                        <i data-lucide="chevron-down"></i><span>Detalhes</span>
                    </button>
                </div>
                <div class="parcelamento-card-summary">
                    <span class="valor-mensal">${o(e.valor_parcela)}/mês</span>
                    <span class="parcelas-info">${e.total_parcelas-e.parcelas_restantes}/${e.total_parcelas} parcelas</span>
                </div>
                <div class="parcelamento-card-progress">
                    <div class="parcela-bar"><div class="parcela-bar-fill" style="width:${s}%;background:${e.categoria_cor};"></div></div>
                </div>
                <div class="parcelamento-card-details">
                    <div class="detail-row"><span class="detail-label">Categoria</span><span class="detail-value"><span class="categoria-dot" style="background:${e.categoria_cor};"></span>${m(e.categoria)}</span></div>
                    <div class="detail-row"><span class="detail-label">Valor por Parcela</span><span class="detail-value">${o(e.valor_parcela)}</span></div>
                    <div class="detail-row"><span class="detail-label">Total Restante</span><span class="detail-value highlight">${o(e.valor_total_restante)}</span></div>
                    <div class="detail-row"><span class="detail-label">Término Previsto</span><span class="detail-value">${e.data_final}</span></div>
                </div>
            </div>
        `}).join("")}function b(a){return!a||a.quantidade===0?'<div class="empty-message"><i data-lucide="circle-check"></i><p>Nenhum parcelamento ativo</p></div>':`
        <div class="parcelamentos-table-wrapper">${S(a.ativos)}</div>
        <div class="parcelamentos-mobile-list">${E(a.ativos)}</div>
    `}function y(a){if(!a)return"";const e=[];return a.tendencia&&e.push(`<div class="insight-card insight-${a.tendencia.type}"><div class="insight-icon"><i data-lucide="${h(a.tendencia.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Tendência</span><span class="insight-badge">${a.tendencia.variacao}</span></div><h4 class="insight-status">${a.tendencia.status}</h4><p class="insight-desc">${a.tendencia.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${a.tendencia.recomendacao}</p></div></div>`),a.parcelamentos&&e.push(`<div class="insight-card insight-${a.parcelamentos.type}"><div class="insight-icon"><i data-lucide="${h(a.parcelamentos.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Parcelamentos</span><span class="insight-badge">${a.parcelamentos.valor}</span></div><h4 class="insight-status">${a.parcelamentos.status}</h4><p class="insight-desc">${a.parcelamentos.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${a.parcelamentos.recomendacao}</p></div></div>`),a.limite&&e.push(`<div class="insight-card insight-${a.limite.type}"><div class="insight-icon"><i data-lucide="${h(a.limite.icon)}"></i></div><div class="insight-content"><div class="insight-header-row"><span class="insight-label">Uso do Limite</span><span class="insight-badge">${a.limite.percentual}</span></div><h4 class="insight-status">${a.limite.status}</h4><p class="insight-desc">${a.limite.descricao}</p><p class="insight-recommendation"><i data-lucide="star"></i> ${a.limite.recomendacao}</p></div></div>`),e.length===0?"":`
        <div class="insights-header"><i data-lucide="lightbulb"></i><h3>Análise Inteligente</h3></div>
        <div class="insights-grid">${e.join("")}</div>
    `}let c=null,d=null;async function L(a,e,s,i){if(!a){console.error("ID do cartão não fornecido");return}const r=document.querySelector(`[data-action="open-card-detail"][data-card-id="${a}"].card-action-btn`),t=r?.innerHTML;r&&(r.disabled=!0,r.innerHTML='<i data-lucide="loader-2" class="icon-spin"></i> <span>Carregando...</span>',window.lucide&&window.lucide.createIcons({nodes:[r]}));try{const[l,n]=i.split("-"),w=new URLSearchParams({mes:n,ano:l}),$=`${_()}api/reports/card-details/${a}?${w}`,u=await x($,{credentials:"include"},{timeout:15e3});if(!u.success||!u.data)throw new Error(u.message||"Dados inválidos retornados");M(u.data,s)}catch(l){console.error("Erro ao abrir detalhes do cartão:",l),document.body.style.overflow="";const n=C(l,"Não foi possível carregar os detalhes do cartão. Tente novamente.");typeof Swal<"u"&&Swal.fire({icon:"error",title:"Erro ao carregar",text:n,confirmButtonColor:"#e67e22"})}finally{r&&(r.disabled=!1,t&&(r.innerHTML=t),window.lucide&&window.lucide.createIcons({nodes:[r]}))}}function M(a,e){const s=window.LK?.modalSystem,i=document.getElementById("cardDetailModalOverlay");i&&(i.remove(),s||(document.body.style.overflow=""));const r=document.getElementById("cardDetailModalTemplate");if(!r){console.error("Template do modal não encontrado");return}const t=document.createElement("div");t.id="cardDetailModalOverlay",t.className="card-detail-modal-overlay",t.appendChild(r.content.cloneNode(!0));try{T(t,a,e)}catch(n){console.error("Erro ao popular o modal:",n);return}Object.assign(t.style,{position:"fixed",top:"0",left:"0",width:"100vw",height:"100vh",zIndex:"9999999",display:"flex",alignItems:"flex-start",justifyContent:"center",overflowY:"auto",background:"rgba(0, 0, 0, 0.7)",backdropFilter:"blur(4px)",WebkitBackdropFilter:"blur(4px)"}),s||(document.body.style.overflow="hidden"),document.body.appendChild(t),s?.prepareOverlay(t,{scope:"page"}),t.classList.add("active"),t.scrollTop=0,t.addEventListener("click",n=>{n.target===t&&v()});const l=n=>{n.key==="Escape"&&(v(),document.removeEventListener("keydown",l))};document.addEventListener("keydown",l),setTimeout(()=>{try{window.lucide&&window.lucide.createIcons({nodes:[t]}),q(a.evolucao?.meses),D(a.impacto_futuro?.meses)}catch(n){console.error("Erro ao renderizar gráficos do modal:",n)}},100)}function T(a,e,s){a.querySelector("[data-color]").style.background=`linear-gradient(135deg, ${s}, ${s}DD)`,a.querySelector("[data-cartao-nome]").textContent=e.cartao.nome,a.querySelector("[data-periodo]").textContent=`${e.fatura_mes.mes}/${e.fatura_mes.ano}`,a.querySelector("[data-fatura-total]").textContent=o(e.fatura_mes.total),a.querySelector("[data-limite]").textContent=o(e.cartao.limite),a.querySelector("[data-disponivel]").textContent=o(e.cartao.limite_disponivel),a.querySelector("[data-utilizacao]").textContent=`${(e.cartao.percentual_utilizacao_geral||0).toFixed(1)}%`;const i=e.fatura_mes.lancamentos.length;a.querySelector("[data-lancamentos-count]").textContent=`${i} ${i===1?"lançamento":"lançamentos"}`,a.querySelector("[data-lancamentos-list]").innerHTML=g(e.fatura_mes.lancamentos),a.querySelector("[data-a-vista]").textContent=o(e.fatura_mes.a_vista),a.querySelector("[data-parcelado]").textContent=o(e.fatura_mes.parcelado),a.querySelector("[data-total]").textContent=o(e.fatura_mes.total);const r=a.querySelector("[data-comparison]");Math.abs(e.fatura_mes.diferenca_absoluta)>1&&(r.innerHTML=f({absoluta:e.fatura_mes.diferenca_absoluta,percentual:e.fatura_mes.diferenca_percentual}),r.style.display="block");const t=a.querySelector("[data-tendencia]");t.className=`tendencia-indicator ${e.evolucao.tendencia}`,t.innerHTML=`
        <i data-lucide="${e.evolucao.tendencia==="subindo"?"arrow-up":e.evolucao.tendencia==="caindo"?"arrow-down":"arrow-right"}"></i>
        ${e.evolucao.tendencia.charAt(0).toUpperCase()+e.evolucao.tendencia.slice(1)}
    `,window.lucide&&window.lucide.createIcons({nodes:[t]}),a.querySelector("[data-media]").textContent=o(e.evolucao.media);const l=a.querySelector("[data-comprometido]");e.parcelamentos.quantidade>0&&(l.textContent=`${o(e.parcelamentos.total_comprometido)} comprometidos`,l.style.display="inline-block"),a.querySelector("[data-parcelamentos-content]").innerHTML=b(e.parcelamentos);const n=a.querySelector("[data-insights]");e.insights&&(n.innerHTML=y(e.insights),n.style.display="block")}function v(){const a=document.getElementById("cardDetailModalOverlay");if(!a)return;const e=window.LK?.modalSystem;a.classList.remove("active"),a.style.opacity="0",a.style.transition="opacity 0.25s ease",e||(document.body.style.overflow=""),c&&(c.destroy(),c=null),d&&(d.destroy(),d=null),setTimeout(()=>a.remove(),300)}function q(a){const e=document.getElementById("evolutionChart");if(!e)return;c&&(c.destroy(),c=null);const s=document.documentElement.getAttribute("data-theme")==="dark",i=p("--color-text-muted","#999"),r=p("--glass-border","rgba(255,255,255,0.1)");c=new ApexCharts(e,{chart:{type:"area",height:260,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Fatura",data:a.map(t=>Number(t.valor)||0)}],xaxis:{categories:a.map(t=>t.mes),labels:{style:{colors:i}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{min:0,labels:{style:{colors:i},formatter:t=>o(t)}},colors:["#E67E22"],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:r,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:s?"dark":"light",y:{formatter:t=>o(t)}},legend:{show:!1},dataLabels:{enabled:!1},theme:{mode:s?"dark":"light"}}),c.render()}function D(a){const e=document.getElementById("impactChart");if(!e)return;d&&(d.destroy(),d=null);const s=document.documentElement.getAttribute("data-theme")==="dark",i=p("--color-text-muted","#999"),r=p("--glass-border","rgba(255,255,255,0.1)");d=new ApexCharts(e,{chart:{type:"bar",height:260,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Projeção",data:a.map(t=>Number(t.valor)||0)}],xaxis:{categories:a.map(t=>t.mes),labels:{style:{colors:i}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{min:0,labels:{style:{colors:i},formatter:t=>o(t)}},colors:["#3498DB"],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:r,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:s?"dark":"light",y:{formatter:t=>o(t)}},legend:{show:!1},dataLabels:{enabled:!1},theme:{mode:s?"dark":"light"}}),d.render()}window.LK_CardDetail={open:L,close:v};window.CardModalRenderers={renderLancamentos:g,renderComparison:f,renderParcelamentos:b,renderInsights:y,formatCurrency:o,escapeHtml:m};
