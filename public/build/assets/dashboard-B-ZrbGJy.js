import{m as G,l as N,k as X,i as oe,e as J,c as W,d as ye,p as ve}from"./api-EIRNFJb7.js";import{a as be,i as we,g as ne,o as ie,r as _e}from"./primary-actions-LdXmvztn.js";import{o as re,g as K,e as ce,d as Ce,a as Ee}from"./runtime-config-BDcybaNg.js";import{t as le,u as Se}from"./finance-CgaDv1sH.js";import{j as Le,k as $e}from"./lancamentos-BFIM3VKH.js";import{e as k}from"./utils-Bj4jxwhy.js";import{c as Ie,p as Ae,f as xe}from"./ui-preferences-B8SkNUZA.js";const F={BASE_URL:G(),TRANSACTIONS_LIMIT:5,CHART_MONTHS:6,ANIMATION_DELAY:300},C={saldoValue:document.getElementById("saldoValue"),receitasValue:document.getElementById("receitasValue"),despesasValue:document.getElementById("despesasValue"),saldoMesValue:document.getElementById("saldoMesValue"),categoryChart:document.getElementById("categoryChart"),chartLoading:document.getElementById("chartLoading"),transactionsList:document.getElementById("transactionsList"),emptyState:document.getElementById("emptyState"),metasBody:document.getElementById("sectionMetasBody"),cartoesBody:document.getElementById("sectionCartoesBody"),contasBody:document.getElementById("sectionContasBody"),orcamentosBody:document.getElementById("sectionOrcamentosBody"),faturasBody:document.getElementById("sectionFaturasBody"),chartContainer:document.getElementById("categoryChart"),tableBody:document.getElementById("transactionsTableBody"),table:document.getElementById("transactionsTable"),cardsContainer:document.getElementById("transactionsCards"),monthLabel:document.getElementById("currentMonthText"),streakDays:document.getElementById("streakDays"),badgesGrid:document.getElementById("badgesGrid"),userLevel:document.getElementById("userLevel"),totalLancamentos:document.getElementById("totalLancamentos"),totalCategorias:document.getElementById("totalCategorias"),mesesAtivos:document.getElementById("mesesAtivos"),pontosTotal:document.getElementById("pontosTotal")},I={chartInstance:null,currentMonth:null,isLoading:!1},y={money:r=>{try{return Number(r||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}catch{return"R$ 0,00"}},dateBR:r=>{if(!r)return"-";try{const t=String(r).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);return t?`${t[3]}/${t[2]}/${t[1]}`:"-"}catch{return"-"}},formatMonth:r=>{try{const[e,t]=String(r).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})}catch{return"-"}},formatMonthShort:r=>{try{const[e,t]=String(r).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"short"})}catch{return"-"}},getCurrentMonth:()=>window.LukratoHeader?.getMonth?.()||new Date().toISOString().slice(0,7),getPreviousMonths:(r,e)=>{const t=[],[a,i]=r.split("-").map(Number);for(let s=e-1;s>=0;s--){const n=new Date(a,i-1-s,1),o=n.getFullYear(),c=String(n.getMonth()+1).padStart(2,"0");t.push(`${o}-${c}`)}return t},getCssVar:(r,e="")=>{try{return(getComputedStyle(document.documentElement).getPropertyValue(r)||"").trim()||e}catch{return e}},isLightTheme:()=>{try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getContaLabel:r=>{if(typeof r.conta=="string"&&r.conta.trim())return r.conta.trim();const e=r.conta_instituicao??r.conta_nome??r.conta?.instituicao??r.conta?.nome??null,t=r.conta_destino_instituicao??r.conta_destino_nome??r.conta_destino?.instituicao??r.conta_destino?.nome??null;return r.eh_transferencia&&(e||t)?`${e||"-"}${t||"-"}`:r.conta_label&&String(r.conta_label).trim()?String(r.conta_label).trim():e||"-"},getTipoClass:r=>{const e=String(r||"").toLowerCase();return e==="receita"?"receita":e.includes("despesa")?"despesa":e.includes("transferencia")?"transferencia":""},removeLoadingClass:()=>{setTimeout(()=>{document.querySelectorAll(".kpi-value.loading").forEach(r=>{r.classList.remove("loading")})},F.ANIMATION_DELAY)}},te=()=>{const r=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||y.isLightTheme?.();return{isLightTheme:r,axisColor:r?y.getCssVar("--color-primary","#e67e22")||"#e67e22":"rgba(255, 255, 255, 0.6)",yTickColor:r?"#000":"#fff",xTickColor:r?y.getCssVar("--color-text-muted","#6c757d")||"#6c757d":"rgba(255, 255, 255, 0.6)",gridColor:r?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",tooltipBg:r?"rgba(255, 255, 255, 0.92)":"rgba(0, 0, 0, 0.85)",tooltipColor:r?"#0f172a":"#f8fafc",labelColor:r?"#0f172a":"#f8fafc"}};function Te(){return"api/v1/dashboard/overview"}function Me(){return"api/v1/dashboard/evolucao"}const Be=3e4;function ke(r,e){return`dashboard:overview:${r}:${e}`}function U(r=y.getCurrentMonth(),{limit:e=F.TRANSACTIONS_LIMIT,force:t=!1}={}){return be(Te(),{month:r,limit:e},{cacheKey:ke(r,e),ttlMs:Be,force:t})}function R(r=null){const e=r?`dashboard:overview:${r}:`:"dashboard:overview:";we(e)}class Ne{constructor(e="greetingContainer"){this.container=document.getElementById(e),this.userName=this.getUserName(),this._listeningDataChanged=!1,re(()=>{this.userName=this.getUserName(),this.updateGreetingTitle()})}getUserName(){return String(K().username||"Usuario").trim().split(/\s+/)[0]||"Usuario"}render(){if(!this.container)return;this.userName=this.getUserName();const e=this.getGreeting(),a=new Date().toLocaleDateString("pt-BR",{weekday:"long",day:"numeric",month:"long"});this.container.innerHTML=`
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${a}</p>
        <p class="greeting-title">${e.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `,this.loadInsight(),ce({},{silent:!0})}updateGreetingTitle(){const e=this.container?.querySelector(".greeting-title");e&&(e.textContent=this.getGreeting().title)}getGreeting(){const e=new Date().getHours();return e>=5&&e<12?{title:`Bom dia, ${this.userName}.`}:e>=12&&e<18?{title:`Boa tarde, ${this.userName}.`}:e>=18&&e<24?{title:`Boa noite, ${this.userName}.`}:{title:`Boa madrugada, ${this.userName}.`}}async loadInsight({force:e=!1}={}){try{const t=await U(void 0,{force:e}),a=t?.data??t;a?.greeting_insight?this.displayInsight(a.greeting_insight):this.displayFallbackInsight()}catch(t){N("Error loading greeting insight",t,"Falha ao carregar insight"),this.displayFallbackInsight()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{R(),this.loadInsight({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.loadInsight({force:!0})}))}displayInsight(e){const t=document.getElementById("greetingInsight");if(!t)return;const{message:a,icon:i,color:s}=e;t.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon" style="color: ${s||"var(--color-primary)"};">
          <i data-lucide="${i||"sparkles"}" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">${a}</p>
      </div>
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}displayFallbackInsight(){const e=document.getElementById("greetingInsight");e&&(e.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon">
          <i data-lucide="sparkles" style="width:16px;height:16px;"></i>
        </div>
        <p class="insight-message">Seu resumo financeiro do mes aparece logo abaixo.</p>
      </div>
    `,typeof window.lucide<"u"&&window.lucide.createIcons())}}window.DashboardGreeting=Ne;class De{constructor(e="healthScoreContainer"){this.container=document.getElementById(e),this.healthScore=0,this.maxScore=100,this.animationDuration=1200}render(){if(!this.container)return;const e=45;this.circumference=2*Math.PI*e;const t=this.circumference;this.container.innerHTML=`
      <div class="health-score-widget surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="hs-header">
          <h2 class="hs-title">Saude financeira</h2>
          <div class="hs-badge" id="healthIndicator">
            <span class="hs-badge-dot"></span>
            <span class="hs-badge-text">...</span>
          </div>
        </div>

        <div class="hs-gauge-area">
          <svg class="hs-gauge" viewBox="0 0 100 100">
            <defs>
              <linearGradient id="gaugeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#10b981"/>
                <stop offset="100%" stop-color="#3b82f6"/>
              </linearGradient>
            </defs>
            <circle cx="50" cy="50" r="${e}" class="hs-gauge-track"/>
            <circle cx="50" cy="50" r="${e}" class="hs-gauge-fill"
              id="gaugeCircle"
              stroke-dasharray="${t}"
              stroke-dashoffset="${t}"
            />
            <text x="50" y="47" class="hs-gauge-value" id="gaugeValue">0</text>
            <text x="50" y="60" class="hs-gauge-label">de 100</text>
          </svg>
        </div>

        <p class="hs-message" id="healthMessage">Carregando...</p>

        <div class="hs-breakdown">
          <div class="hs-metric">
            <span class="hs-metric-label">Registros</span>
            <span class="hs-metric-value" id="hsLancamentos">--</span>
          </div>
          <div class="hs-metric">
            <span class="hs-metric-label">Limites</span>
            <span class="hs-metric-value" id="hsOrcamento">--</span>
          </div>
          <div class="hs-metric">
            <span class="hs-metric-label">Metas</span>
            <span class="hs-metric-value" id="hsMetas">--</span>
          </div>
        </div>
      </div>
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await U(void 0,{force:e}),a=t?.data??t;a?.health_score&&this.updateScore(a.health_score)}catch(t){N("Error loading health score",t,"Falha ao carregar health score"),this.showError()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{R(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.load({force:!0})}))}updateScore(e){const{score:t=0}=e;this.animateGauge(t),this.updateBreakdown(e),this.updateStatusIndicator(t)}animateGauge(e){const t=document.getElementById("gaugeCircle"),a=document.getElementById("gaugeValue");if(!t||!a)return;const i=this.circumference||2*Math.PI*45;let s=0;const n=e/(this.animationDuration/16),o=()=>{s+=n,s>=e&&(s=e);const c=i-i*s/this.maxScore;t.setAttribute("stroke-dashoffset",c),a.textContent=Math.round(s),s<e&&requestAnimationFrame(o)};o()}updateBreakdown(e){const t=document.getElementById("hsLancamentos"),a=document.getElementById("hsOrcamento"),i=document.getElementById("hsMetas");if(t){const s=e.lancamentos??0;t.textContent=`${s}`,s>=10?t.className="hs-metric-value color-success":s>=5?t.className="hs-metric-value color-warning":t.className="hs-metric-value color-muted"}if(a){const s=e.orcamentos??0,n=e.orcamentos_ok??0;s===0?(a.textContent="--",a.className="hs-metric-value color-muted"):(a.textContent=`${n}/${s}`,n===s?a.className="hs-metric-value color-success":n>=s/2?a.className="hs-metric-value color-warning":a.className="hs-metric-value color-danger")}if(i){const s=e.metas_ativas??0,n=e.metas_concluidas??0;s===0?(i.textContent="--",i.className="hs-metric-value color-muted"):n>0?(i.textContent=`${s}+${n}`,i.className="hs-metric-value color-success"):(i.textContent=`${s}`,i.className="hs-metric-value color-warning")}}updateStatusIndicator(e){const t=document.getElementById("healthIndicator"),a=document.getElementById("healthMessage");if(!t)return;let i="critical",s="CRITICA",n="Ajustes rapidos podem evitar aperto financeiro.";e>=70?(i="excellent",s="BOA",n="Você está no controle. Continue assim!"):e>=50?(i="good",s="ESTAVEL",n="Controle bom, mas há espaço para melhorar."):e>=30&&(i="warning",s="ATENCAO",n="Alguns sinais pedem cuidado neste mês."),t.className=`hs-badge hs-badge--${i}`,t.innerHTML=`
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${s}</span>
    `,a&&(a.textContent=n)}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}showError(){const e=document.getElementById("healthIndicator"),t=document.getElementById("healthMessage");e&&(e.className="hs-badge hs-badge--error",e.innerHTML=`
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `),t&&(t.textContent="Nao foi possivel carregar.")}}window.HealthScoreWidget=De;class Pe{constructor(e="healthScoreInsights"){this.container=document.getElementById(e),this.baseURL=G(),this.init()}init(){this.container&&(this._initialized||(this._initialized=!0,this.renderSkeleton(),this.loadInsights(),this._intervalId=setInterval(()=>this.loadInsights({force:!0}),3e5),document.addEventListener("lukrato:data-changed",()=>{R(),this.loadInsights({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.loadInsights({force:!0})})))}renderSkeleton(){this.container.innerHTML=`
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `}async loadInsights({force:e=!1}={}){try{const t=await U(void 0,{force:e}),a=t?.data??t;a?.health_score_insights?this.renderInsights(a.health_score_insights):this.renderEmpty()}catch(t){N("Error loading health score insights",t,"Falha ao carregar insights"),this.renderEmpty()}}renderInsights(e){const t=Array.isArray(e)?e:e?.insights||[],a=Array.isArray(e)?"":e?.total_possible_improvement||"";if(t.length===0){this.renderEmpty();return}const i=t.map((s,n)=>{const o=this.normalizeInsight(s);return`
      <a href="${this.baseURL}${o.action.url}" class="hsi-card hsi-card--${o.priority}" style="animation-delay: ${n*80}ms;">
        <div class="hsi-card-icon hsi-icon--${o.priority}">
          <i data-lucide="${this.getIconForType(o.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${o.title}</span>
          <span class="hsi-card-desc">${o.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${o.impact}</span>
          <i data-lucide="chevron-right" style="width:14px;height:14px;" class="hsi-arrow"></i>
        </div>
      </a>
    `}).join("");this.container.innerHTML=`
      <div class="hsi-list">${i}</div>
      ${a?`
        <div class="hsi-summary">
          <i data-lucide="trending-up" style="width:14px;height:14px;"></i>
          <span>Potencial: <strong>${a}</strong></span>
        </div>
      `:""}
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}normalizeInsight(e){const a={negative_balance:{title:"Seu saldo ficou negativo",impact:"Aja agora",action:{url:"lancamentos?tipo=despesa"}},low_activity:{title:"Registre mais movimentações",impact:"Mais controle",action:{url:"lancamentos"}},low_categories:{title:"Use mais categorias",impact:"Mais clareza",action:{url:"categorias"}},no_goals:{title:"Defina uma meta financeira",impact:"Mais direcao",action:{url:"financas#metas"}}}[e.type]||{title:"Insight do mes",impact:"Ver detalhe",action:{url:"dashboard"}};return{priority:e.priority||"medium",type:e.type||"generic",title:e.title||a.title,message:e.message||"",impact:e.impact||a.impact,action:e.action||a.action}}renderEmpty(){this.container.innerHTML=""}getIconForType(e){return{savings_rate:"piggy-bank",consistency:"calendar-check",diversification:"layers",negative_balance:"alert-triangle",low_balance:"wallet",no_income:"alert-circle",no_goals:"target"}[e]||"lightbulb"}}window.HealthScoreInsights=Pe;class Re{constructor(e="aiTipContainer"){this.container=document.getElementById(e),this.baseURL=G()}init(){this.container&&(this._initialized||(this._initialized=!0,this.render(),this.load(),document.addEventListener("lukrato:data-changed",()=>{R(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{R(),this.load({force:!0})})))}render(){this.container.innerHTML=`
      <div class="ai-tip-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400" data-aos-delay="100">
        <div class="ai-tip-header">
          <i data-lucide="sparkles" class="ai-tip-header-icon"></i>
          <h2 class="ai-tip-title">Dicas do Lukrato</h2>
          <span class="ai-tip-badge" id="aiTipBadge" style="display:none;"></span>
        </div>
        <div class="ai-tip-list" id="aiTipList">
          ${'<div class="ai-tip-skeleton"></div>'.repeat(4)}
        </div>
      </div>
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await U(void 0,{force:e}),a=t?.data??t,i=this.buildTips(a);this.renderTips(i)}catch(t){N("Error loading AI tips",t,"Falha ao carregar dicas"),this.renderEmpty()}}buildTips(e){const t=[],a=e?.health_score||{},i=e?.metrics||{},s=e?.provisao?.provisao||{},n=e?.provisao?.vencidos||{},o=e?.provisao?.parcelas||{},c=e?.chart||[],u=Array.isArray(e?.health_score_insights)?e.health_score_insights:e?.health_score_insights?.insights||[],p={critical:0,high:1,medium:2,low:3};if(u.sort((v,E)=>(p[v.priority]??9)-(p[E.priority]??9)).forEach(v=>{const E=this.normalizeInsight(v);t.push({type:E.type,priority:E.priority,icon:E.icon,title:v.title||E.title,desc:v.message||E.message,url:E.url,metric:v.metric||null,metricLabel:v.metric_label||null})}),n.count>0){const v=(n.total||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"overdue",priority:"critical",icon:"clock",title:`${n.count} conta(s) em atraso`,desc:"Regularize para evitar juros e manter o score saudável.",url:"lancamentos?status=vencido",metric:v,metricLabel:"em atraso"})}const l=e?.provisao?.proximos||[];if(l.length>0){const v=l[0],E=v.data_pagamento?new Date(v.data_pagamento+"T00:00:00"):null,B=new Date;if(B.setHours(0,0,0,0),E){const $=Math.ceil((E-B)/864e5);if($>=0&&$<=3){const V=(v.valor||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"upcoming",priority:"high",icon:"calendar",title:$===0?"Vence hoje!":`Vence em ${$} dia(s)`,desc:v.titulo||"Conta próxima do vencimento",url:"lancamentos",metric:V,metricLabel:$===0?"hoje":`${$}d`})}}}if(e?.greeting_insight){const v=e.greeting_insight;t.push({type:"greeting",priority:"positive",icon:v.icon||"trending-up",title:v.message||"Evolução do mês",desc:"",url:null,metric:null,metricLabel:null})}const d=a.savingsRate??0;(i.receitas??0)>0&&d>=20&&t.push({type:"savings",priority:"positive",icon:"piggy-bank",title:"Ótima taxa de economia!",desc:"Você está guardando acima dos 20% recomendados.",url:null,metric:d+"%",metricLabel:"guardado"});const g=a.orcamentos??0,h=a.orcamentos_ok??0;if(g>0){const v=g-h;v>0?t.push({type:"budget",priority:"high",icon:"alert-circle",title:`${v} orçamento(s) estourado(s)`,desc:"Revise seus gastos para voltar ao controle.",url:"financas",metric:`${h}/${g}`,metricLabel:"no limite"}):t.push({type:"budget",priority:"positive",icon:"check-circle",title:"Orçamentos sob controle!",desc:`Todas as ${g} categoria(s) dentro do limite.`,url:"financas",metric:`${g}/${g}`,metricLabel:"ok"})}const m=a.metas_ativas??0,_=a.metas_concluidas??0;if(_>0?t.push({type:"goals",priority:"positive",icon:"trophy",title:`${_} meta(s) alcançada(s)!`,desc:m>0?`Continue! ${m} ainda em progresso.`:"Parabéns pelo progresso!",url:"financas#metas",metric:String(_),metricLabel:"concluída(s)"}):m>0&&t.push({type:"goals",priority:"low",icon:"target",title:`${m} meta(s) em progresso`,desc:"Cada passo conta. Mantenha o foco!",url:"financas#metas",metric:String(m),metricLabel:"ativa(s)"}),o.ativas>0){const v=(o.total_mensal||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"installments",priority:"info",icon:"layers",title:`${o.ativas} parcelamento(s) ativo(s)`,desc:`${v}/mês comprometidos com parcelas.`,url:"lancamentos",metric:v,metricLabel:"/mês"})}const w=s.saldo_projetado??0,M=s.saldo_atual??0;if(M>0&&w<0?t.push({type:"projection",priority:"critical",icon:"trending-down",title:"Atenção: saldo projetado negativo",desc:"Até o fim do mês, seu saldo pode ficar negativo. Reduza gastos.",url:null,metric:w.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}):w>M&&M>0&&t.push({type:"projection",priority:"positive",icon:"trending-up",title:"Projeção positiva!",desc:"Você deve fechar o mês com saldo maior.",url:null,metric:w.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}),c.length>=3){const v=c.slice(-3),E=v.every($=>$.resultado>0),B=v.every($=>$.resultado<0);E?t.push({type:"trend",priority:"positive",icon:"flame",title:"Sequência de 3 meses positivos!",desc:"Ótima consistência. Mantenha o ritmo!",url:"relatorios",metric:"3",metricLabel:"meses"}):B&&t.push({type:"trend",priority:"high",icon:"alert-triangle",title:"3 meses no vermelho",desc:"É hora de repensar seus gastos.",url:"relatorios",metric:"3",metricLabel:"meses"})}const D=new Set,A=t.filter(v=>D.has(v.type)?!1:(D.add(v.type),!0)),x={critical:0,high:1,medium:2,low:3,positive:4,info:5};return A.sort((v,E)=>(x[v.priority]??9)-(x[E.priority]??9)),A.slice(0,5)}normalizeInsight(e){const a={negative_balance:{title:"Saldo no vermelho",icon:"alert-triangle",url:"lancamentos?tipo=despesa"},overspending:{title:"Gastos acima da receita",icon:"trending-down",url:"lancamentos?tipo=despesa"},low_savings:{title:"Economia muito baixa",icon:"piggy-bank",url:"relatorios"},moderate_savings:{title:"Aumente sua economia",icon:"piggy-bank",url:"relatorios"},low_activity:{title:"Registre suas movimentações",icon:"edit-3",url:"lancamentos"},low_categories:{title:"Organize por categorias",icon:"layers",url:"categorias"},no_goals:{title:"Crie sua primeira meta",icon:"target",url:"financas#metas"},no_budgets:{title:"Defina limites de gastos",icon:"shield",url:"financas"}}[e.type]||{title:"Dica do mês",icon:"lightbulb",url:"dashboard"};return{type:e.type||"generic",priority:e.priority||"medium",title:e.title||a.title,message:e.message||"",icon:a.icon,url:a.url}}renderTips(e){const t=document.getElementById("aiTipList");if(!t)return;if(e.length===0){this.renderEmpty();return}const a=document.getElementById("aiTipBadge"),i=e.some(n=>n.priority==="critical"||n.priority==="high");if(a)if(i)a.textContent=`${e.filter(n=>n.priority==="critical"||n.priority==="high").length} atenção`,a.style.display="",a.style.background="rgba(239, 68, 68, 0.12)",a.style.color="#ef4444";else{const n=e.filter(o=>o.priority==="positive").length;n>0?(a.textContent=`${n} positivo(s)`,a.style.display="",a.style.background="rgba(16, 185, 129, 0.12)",a.style.color="#10b981"):a.style.display="none"}const s=e.map((n,o)=>{const c=this.getIconClass(n.priority),u=n.url?"a":"div",p=n.url?` href="${this.baseURL}${n.url}"`:"",l=`ai-tip-accent--${n.priority||"info"}`,d=n.metric?`<div class="ai-tip-metric">
            <span class="ai-tip-metric-value">${n.metric}</span>
            ${n.metricLabel?`<span class="ai-tip-metric-label">${n.metricLabel}</span>`:""}
          </div>`:"";return`
        <${u}${p} class="ai-tip-item surface-card" data-priority="${n.priority}" style="animation-delay: ${o*70}ms;">
          <div class="ai-tip-accent ${l}"></div>
          <div class="ai-tip-content">
            <div class="ai-tip-item-icon ${c}">
              <i data-lucide="${n.icon}" style="width:16px;height:16px;"></i>
            </div>
            <div class="ai-tip-item-body">
              <span class="ai-tip-item-title">${n.title}</span>
              ${n.desc?`<span class="ai-tip-item-desc">${n.desc}</span>`:""}
            </div>
            ${n.url?'<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>':""}
          </div>
          ${d}
        </${u}>
      `}).join("");t.innerHTML=s,this.updateIcons()}renderEmpty(){const e=document.getElementById("aiTipList");if(!e)return;e.innerHTML=`
      <div class="ai-tip-empty">
        <i data-lucide="check-circle" class="ai-tip-empty-icon"></i>
        <p>Tudo certo por aqui! Suas finanças estão no caminho certo.</p>
      </div>
    `;const t=document.getElementById("aiTipBadge");t&&(t.textContent="Tudo ok",t.style.display="",t.style.background="rgba(16, 185, 129, 0.12)",t.style.color="#10b981"),this.updateIcons()}getIconClass(e){return{critical:"ai-tip-item-icon--critical",high:"ai-tip-item-icon--high",medium:"ai-tip-item-icon--medium",low:"ai-tip-item-icon--low",positive:"ai-tip-item-icon--positive"}[e]||"ai-tip-item-icon--info"}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.AiTipCard=Re;class He{constructor(e="financeOverviewContainer"){this.container=document.getElementById(e),this.baseURL=G()}render(){this.container&&(this.container.innerHTML=`
      <section class="finance-overview-section" data-aos="fade-up" data-aos-duration="500">
        <div class="dashboard-section-heading">
          <div>
            <span class="dashboard-section-eyebrow">Metas</span>
            <h2 class="dashboard-section-title">Seu proximo objetivo</h2>
            <p class="dashboard-section-copy" id="foGoalsHeadline">Faltam R$ 0,00 para alcancar sua meta.</p>
          </div>
          <a href="${this.baseURL}financas#metas" class="dashboard-section-link">Criar metas</a>
        </div>

        <div class="fo-grid">
          <div class="fo-card fo-card--goal" id="foMetas">
            <div class="fo-skeleton"></div>
          </div>
          <div class="fo-card fo-card--budget" id="foOrcamento">
            <div class="fo-skeleton"></div>
          </div>
        </div>
      </section>
    `)}async load(){try{const{mes:e,ano:t}=this.getSelectedPeriod(),a=await X(le(),{mes:e,ano:t});a.success&&a.data?(this.renderAlerts(a.data),this.renderMetas(a.data.metas),this.renderOrcamento(a.data.orcamento)):(this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty())}catch(e){console.error("Error loading finance overview:",e),this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty()}this._listening||(this._listening=!0,document.addEventListener("lukrato:data-changed",()=>this.load()),document.addEventListener("lukrato:month-changed",()=>this.load()))}renderAlerts(e=null){const t=document.getElementById("dashboardAlertsBudget");if(!t)return;const a=Array.isArray(e?.orcamento?.orcamentos)?e.orcamento.orcamentos.slice():[],i=a.filter(o=>o.status==="estourado").sort((o,c)=>Number(c.excedido||0)-Number(o.excedido||0)),s=a.filter(o=>o.status==="alerta").sort((o,c)=>Number(c.percentual||0)-Number(o.percentual||0)),n=[];if(i.slice(0,2).forEach(o=>{n.push({variant:"danger",title:`Você já passou do limite em ${o.categoria_nome}`,message:`Excedido em ${this.money(o.excedido||0)}.`})}),n.length<2&&s.slice(0,2-n.length).forEach(o=>{n.push({variant:"warning",title:`${o.categoria_nome} ja consumiu ${Math.round(o.percentual||0)}% do limite`,message:`Restam ${this.money(o.disponivel||0)} nessa categoria.`})}),n.length===0){t.innerHTML="",this.toggleAlertsSection();return}t.innerHTML=n.map(o=>`
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${o.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${o.variant==="danger"?"triangle-alert":"circle-alert"}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${o.title}</strong>
          <span>${o.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join(""),this.toggleAlertsSection(),this.refreshIcons()}renderOrcamento(e){const t=document.getElementById("foOrcamento");if(!t)return;if(!e||e.total_categorias===0){this.renderOrcamentoEmpty();return}const a=Math.round(e.percentual_geral||0),i=this.getBarColor(a),n=(e.orcamentos||[]).slice().sort((c,u)=>Number(u.percentual||0)-Number(c.percentual||0)).slice(0,3).map(c=>{const u=Math.min(Number(c.percentual||0),100),p=this.getBarColor(c.percentual);return`
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${c.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${p};">${Math.round(c.percentual||0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${u}%; background:${p};"></div>
          </div>
        </div>
      `}).join("");let o="No controle";(e.estourados||0)>0?o=`${e.estourados} acima do limite`:(e.em_alerta||0)>0&&(o=`${e.em_alerta} em atencao`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${i}; background:${i}18;">${o}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(e.total_gasto||0)} usados de ${this.money(e.total_limite||0)}</span>
        <span class="fo-summary-status">Saude: ${e.saude_financeira?.label||"Boa"}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(a,100)}%; background:${i};"></div>
      </div>

      ${n?`<div class="fo-orc-list">${n}</div>`:""}

      <a href="${this.baseURL}financas#orcamentos" class="fo-link">Ver limites <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `,this.refreshIcons()}renderOrcamentoEmpty(){const e=document.getElementById("foOrcamento");e&&(e.innerHTML=`
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu limites para acompanhar categorias.</p>
        <a href="${this.baseURL}financas#orcamentos" class="fo-cta">Definir limite</a>
      </div>
    `,this.refreshIcons())}renderMetas(e){const t=document.getElementById("foMetas");if(!t)return;if(!e||e.total_metas===0){this.renderMetasEmpty();return}const a=e.proxima_concluir,i=Math.round(e.progresso_geral||0);if(!a){this.updateGoalsHeadline("você tem metas ativas, mas nenhuma esta proxima de concluir."),t.innerHTML=`
        <div class="fo-card-header">
          <a href="${this.baseURL}financas#metas" class="fo-card-title">
            <i data-lucide="target" style="width:16px;height:16px;"></i>
            Metas
          </a>
          <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
        </div>
        <div class="fo-metas-summary">
          <div class="fo-metas-stat">
            <span class="fo-metas-stat-value">${i}%</span>
            <span class="fo-metas-stat-label">progresso geral</span>
          </div>
        </div>
        <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
      `,this.refreshIcons();return}const s=a.cor||"var(--color-primary)",n=this.normalizeIconName(a.icone),o=Math.round(a.progresso||0),c=Math.max(Number(a.valor_alvo||0)-Number(a.valor_atual||0),0);this.updateGoalsHeadline(`Faltam ${this.money(c)} para alcancar sua meta.`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
      </div>

      <div class="fo-meta-destaque">
        <div class="fo-meta-icon" style="color:${s}; background:${s}18;">
          <i data-lucide="${n}" style="width:16px;height:16px;"></i>
        </div>
        <div class="fo-meta-info">
          <span class="fo-meta-titulo">${a.titulo}</span>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${Math.min(o,100)}%; background:${s};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(a.valor_atual||0)} de ${this.money(a.valor_alvo||0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${s};">${o}%</span>
      </div>

      <div class="fo-metas-summary">
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${this.money(c)}</span>
          <span class="fo-metas-stat-label">faltam para concluir</span>
        </div>
        <div class="fo-metas-stat">
          <span class="fo-metas-stat-value">${i}%</span>
          <span class="fo-metas-stat-label">progresso geral</span>
        </div>
      </div>

      <a href="${this.baseURL}financas#metas" class="fo-link">Ver metas <i data-lucide="arrow-right" style="width:12px;height:12px;"></i></a>
    `,this.refreshIcons()}renderMetasEmpty(){const e=document.getElementById("foMetas");e&&(this.updateGoalsHeadline("Defina uma meta para transformar sua sobra em um objetivo claro."),e.innerHTML=`
      <div class="fo-card-header">
        <span class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </span>
      </div>
      <div class="fo-empty">
        <p>você ainda nao definiu uma meta ativa.</p>
        <a href="${this.baseURL}financas#metas" class="fo-cta">Criar meta</a>
      </div>
    `,this.refreshIcons())}updateGoalsHeadline(e){const t=document.getElementById("foGoalsHeadline");t&&(t.textContent=e)}toggleAlertsSection(){const e=document.getElementById("dashboardAlertsSection"),t=document.getElementById("dashboardAlertsOverview"),a=document.getElementById("dashboardAlertsBudget");if(!e)return;const i=t&&t.innerHTML.trim()!=="",s=a&&a.innerHTML.trim()!=="";e.style.display=i||s?"block":"none"}getSelectedPeriod(){const e=y.getCurrentMonth?y.getCurrentMonth():new Date().toISOString().slice(0,7),t=String(e).match(/^(\d{4})-(\d{2})$/);if(t)return{ano:Number(t[1]),mes:Number(t[2])};const a=new Date;return{mes:a.getMonth()+1,ano:a.getFullYear()}}getBarColor(e){return e>=100?"#ef4444":e>=80?"#f59e0b":"#10b981"}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-bullseye":"target","fa-target":"target","fa-wallet":"wallet","fa-university":"landmark","fa-plane":"plane","fa-car":"car","fa-home":"house","fa-heart":"heart","fa-briefcase":"briefcase-business","fa-piggy-bank":"piggy-bank","fa-shield":"shield","fa-graduation-cap":"graduation-cap","fa-store":"store","fa-baby":"baby","fa-hand-holding-usd":"hand-coins"}[t]||t.replace(/^fa-/,""))||"target"}money(e){return Number(e||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}refreshIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.FinanceOverview=He;class Oe{constructor(e="evolucaoChartsContainer"){this.container=document.getElementById(e),this._chartMensal=null,this._chartAnual=null,this._activeTab="mensal",this._currentMonth=null}init(){!this.container||this._initialized||(this._initialized=!0,this._render(),this._loadAndDraw(),document.addEventListener("lukrato:month-changed",e=>{this._currentMonth=e?.detail?.month??null,this._loadAndDraw()}),document.addEventListener("lukrato:data-changed",()=>{this._loadAndDraw()}))}_render(){this.container.innerHTML=`
      <div class="evo-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="evo-header">
          <div class="evo-title-group">
            <i data-lucide="trending-up" class="evo-title-icon"></i>
            <h2 class="evo-title">Evolução financeira</h2>
          </div>
          <div class="evo-tabs" role="tablist">
            <button class="evo-tab evo-tab--active" data-tab="mensal" role="tab" aria-selected="true">Mensal</button>
            <button class="evo-tab" data-tab="anual" role="tab" aria-selected="false">Anual</button>
          </div>
        </div>

        <div class="evo-stats" id="evoStats">
          <div class="evo-stat">
            <span class="evo-stat__label">Entradas</span>
            <span class="evo-stat__value evo-stat__value--income" id="evoStatReceitas">–</span>
          </div>
          <div class="evo-stat">
            <span class="evo-stat__label">Saídas</span>
            <span class="evo-stat__value evo-stat__value--expense" id="evoStatDespesas">–</span>
          </div>
          <div class="evo-stat">
            <span class="evo-stat__label">Resultado</span>
            <span class="evo-stat__value" id="evoStatResultado">–</span>
          </div>
        </div>

        <div class="evo-chart-wrap">
          <div id="evoChartMensal" class="evo-chart"></div>
          <div id="evoChartAnual"  class="evo-chart" style="display:none;"></div>
        </div>
      </div>
    `,this.container.querySelectorAll(".evo-tab").forEach(e=>{e.addEventListener("click",()=>this._switchTab(e.dataset.tab))}),typeof window.lucide<"u"&&window.lucide.createIcons({attrs:{class:["lucide"]}})}async _loadAndDraw(){const e=this._currentMonth||this._detectMonth();try{const t=await X(Me(),{month:e}),a=t?.data??t;if(!a?.mensal)return;this._data=a,this._drawMensal(a.mensal),this._drawAnual(a.anual),this._updateStats(a)}catch{}}_detectMonth(){const e=document.getElementById("monthSelector")||document.querySelector("[data-month]");return e?.value||e?.dataset?.month||new Date().toISOString().slice(0,7)}_theme(){const e=document.documentElement.getAttribute("data-theme")!=="light",t=getComputedStyle(document.documentElement);return{isDark:e,mode:e?"dark":"light",textMuted:t.getPropertyValue("--color-text-muted").trim()||(e?"#94a3b8":"#666"),gridColor:e?"rgba(255,255,255,0.05)":"rgba(0,0,0,0.06)",primary:t.getPropertyValue("--color-primary").trim()||"#E67E22",success:t.getPropertyValue("--color-success").trim()||"#2ecc71",danger:t.getPropertyValue("--color-danger").trim()||"#e74c3c",surface:e?"#0f172a":"#ffffff"}}_fmt(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e??0)}_drawMensal(e){const t=document.getElementById("evoChartMensal");if(!t||!Array.isArray(e))return;this._chartMensal&&(this._chartMensal.destroy(),this._chartMensal=null);const a=this._theme(),i=e.map(o=>o.label),s=e.map(o=>+o.receitas),n=e.map(o=>+o.despesas);this._chartMensal=new ApexCharts(t,{chart:{type:"bar",height:220,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,sparkline:{enabled:!1},animations:{enabled:!0,speed:600}},series:[{name:"Entradas",data:s},{name:"Saídas",data:n}],xaxis:{categories:i,tickAmount:7,labels:{rotate:0,style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:o=>this._fmt(o)}},colors:[a.success,a.danger],plotOptions:{bar:{borderRadius:4,columnWidth:"70%",dataLabels:{position:"top"}}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:o=>this._fmt(o)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartMensal.render()}_drawAnual(e){const t=document.getElementById("evoChartAnual");if(!t||!Array.isArray(e))return;this._chartAnual&&(this._chartAnual.destroy(),this._chartAnual=null);const a=this._theme(),i=e.map(c=>c.label),s=e.map(c=>+c.receitas),n=e.map(c=>+c.despesas),o=e.map(c=>+c.saldo);this._chartAnual=new ApexCharts(t,{chart:{type:"line",height:220,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,animations:{enabled:!0,speed:600}},series:[{name:"Entradas",type:"column",data:s},{name:"Saídas",type:"column",data:n},{name:"Saldo",type:"area",data:o}],xaxis:{categories:i,labels:{style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:c=>this._fmt(c)}},colors:[a.success,a.danger,a.primary],plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},stroke:{curve:"smooth",width:[0,0,2.5]},fill:{type:["solid","solid","gradient"],gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:.02,stops:[0,100]}},markers:{size:[0,0,4],hover:{size:6}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:c=>this._fmt(c)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartAnual.render()}_updateStats(e){const t=this._activeTab==="anual";let a=0,i=0;t&&e.anual?.length?e.anual.forEach(u=>{a+=+u.receitas,i+=+u.despesas}):e.mensal?.length&&e.mensal.forEach(u=>{a+=+u.receitas,i+=+u.despesas});const s=a-i,n=document.getElementById("evoStatReceitas"),o=document.getElementById("evoStatDespesas"),c=document.getElementById("evoStatResultado");n&&(n.textContent=this._fmt(a)),o&&(o.textContent=this._fmt(i)),c&&(c.textContent=this._fmt(s),c.className="evo-stat__value "+(s>=0?"evo-stat__value--income":"evo-stat__value--expense"))}_switchTab(e){if(this._activeTab===e)return;this._activeTab=e,this.container.querySelectorAll(".evo-tab").forEach(i=>{const s=i.dataset.tab===e;i.classList.toggle("evo-tab--active",s),i.setAttribute("aria-selected",String(s))});const t=document.getElementById("evoChartMensal"),a=document.getElementById("evoChartAnual");t&&(t.style.display=e==="mensal"?"":"none"),a&&(a.style.display=e==="anual"?"":"none"),this._data&&this._updateStats(this._data),setTimeout(()=>{e==="mensal"&&this._chartMensal&&this._chartMensal.windowResizeHandler?.(),e==="anual"&&this._chartAnual&&this._chartAnual.windowResizeHandler?.()},10)}}window.EvolucaoCharts=Oe;class de{constructor(){this.initialized=!1,this.init()}init(){this.setupEventListeners(),this.initialized=!0}setupEventListeners(){document.addEventListener("lukrato:transaction-added",()=>{this.playAddedAnimation()}),document.addEventListener("lukrato:level-up",e=>{this.playLevelUpAnimation(e.detail?.level)}),document.addEventListener("lukrato:streak-milestone",e=>{this.playStreakAnimation(e.detail?.days)}),document.addEventListener("lukrato:goal-completed",e=>{this.playGoalAnimation(e.detail?.goalName)}),document.addEventListener("lukrato:achievement-unlocked",e=>{this.playAchievementAnimation(e.detail?.name,e.detail?.icon)})}playAddedAnimation(){window.fab&&window.fab.celebrate(),window.LK?.toast&&window.LK.toast.success("Lancamento adicionado com sucesso."),this.fireConfetti("small",.9,.9)}playLevelUpAnimation(e){this.showCelebrationToast({title:`Nivel ${e}`,subtitle:"você subiu de nivel.",icon:"star",duration:3e3}),this.fireConfetti("large",.5,.3),this.screenFlash("#f59e0b",.3,2),window.fab?.container&&(window.fab.container.style.animation="spin 0.8s ease-out",setTimeout(()=>{window.fab.container.style.animation=""},800))}playStreakAnimation(e){const a={7:{title:"Semana perfeita",subtitle:"você chegou a 7 dias seguidos."},14:{title:"Duas semanas",subtitle:"você chegou a 14 dias seguidos."},30:{title:"Mes epico",subtitle:"você chegou a 30 dias seguidos."},100:{title:"Marco historico",subtitle:"você chegou a 100 dias seguidos."}}[e]||{title:`${e} dias seguidos`,subtitle:"Sua sequencia continua forte."};this.showCelebrationModal(a.title,a.subtitle),this.fireConfetti("extreme",.5,.2)}playGoalAnimation(e){this.showCelebrationToast({title:"Meta atingida",subtitle:`você completou: ${e}`,icon:"target",duration:3500}),this.fireConfetti("large",.5,.4),this.screenFlash("#10b981",.4,1.5)}playAchievementAnimation(e,t){const a=this.normalizeIconName(t),i=document.createElement("div");i.className="achievement-popup",i.innerHTML=`
      <div class="achievement-card">
        <div class="achievement-icon">
          <i data-lucide="${a}"></i>
        </div>
        <div class="achievement-title">Conquista desbloqueada</div>
        <div class="achievement-name">${e}</div>
      </div>
    `,document.body.appendChild(i),typeof window.lucide<"u"&&window.lucide.createIcons(),setTimeout(()=>{i.classList.add("show")},10),setTimeout(()=>{i.classList.remove("show"),setTimeout(()=>i.remove(),300)},3500),this.fireConfetti("medium",.5,.6)}showCelebrationToast(e){const{title:t="Parabens",subtitle:a="você fez progresso.",icon:i="party-popper",duration:s=3e3}=e;window.LK?.toast&&window.LK.toast.success(`${t}
${a}`)}showCelebrationModal(e,t){typeof Swal>"u"||Swal.fire({title:e,text:t,icon:"success",confirmButtonText:"Continuar",confirmButtonColor:"var(--color-primary)",allowOutsideClick:!1,didOpen:()=>{this.fireConfetti("extreme",.5,.2)}})}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-trophy":"trophy","fa-award":"award","fa-medal":"medal","fa-star":"star","fa-target":"target"}[t]||t.replace(/^fa-/,""))||"trophy"}screenFlash(e="#10b981",t=.3,a=1){const i=document.createElement("div");i.style.cssText=`
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: ${e};
      opacity: 0;
      z-index: 99999;
      pointer-events: none;
      transition: none;
    `,document.body.appendChild(i),setTimeout(()=>{i.style.transition=`opacity ${a/2}ms ease-out`,i.style.opacity=t},10),setTimeout(()=>{i.style.transition=`opacity ${a/2}ms ease-in`,i.style.opacity="0"},a/2),setTimeout(()=>i.remove(),a)}fireConfetti(e="medium",t=.5,a=.5){if(typeof confetti!="function")return;const i={small:{particleCount:30,spread:40},medium:{particleCount:60,spread:60},large:{particleCount:100,spread:90},extreme:{particleCount:150,spread:120}},s=i[e]||i.medium;confetti({...s,origin:{x:t,y:a},gravity:.8,decay:.95,zIndex:99999})}}window.CelebrationSystem=de;document.addEventListener("DOMContentLoaded",()=>{window.celebrationSystem||(window.celebrationSystem=new de)});function Fe(){return new Promise(r=>{let e=0;const t=setInterval(()=>{window.HealthScoreWidget&&window.DashboardGreeting&&window.HealthScoreInsights&&window.FinanceOverview&&window.EvolucaoCharts&&(clearInterval(t),r()),e++>50&&(clearInterval(t),r())},100)})}function ae(r,e){const t=document.getElementById(r);return t||e()}async function Ve(){await Fe(),document.readyState==="loading"?document.addEventListener("DOMContentLoaded",se):se()}function se(){const r=document.querySelector(".modern-dashboard");if(r){if(typeof window.DashboardGreeting<"u"&&(ae("greetingContainer",()=>{const t=document.createElement("div");return t.id="greetingContainer",r.insertBefore(t,r.firstChild),t}),new window.DashboardGreeting().render()),typeof window.HealthScoreWidget<"u"){if(document.getElementById("healthScoreContainer")){const t=new window.HealthScoreWidget;t.render(),t.load()}typeof window.HealthScoreInsights<"u"&&document.getElementById("healthScoreInsights")&&(window.healthScoreInsights=new window.HealthScoreInsights)}if(typeof window.AiTipCard<"u"&&document.getElementById("aiTipContainer")&&new window.AiTipCard().init(),typeof window.EvolucaoCharts<"u"&&document.getElementById("evolucaoChartsContainer")&&new window.EvolucaoCharts().init(),typeof window.FinanceOverview<"u"){ae("financeOverviewContainer",()=>{const t=document.createElement("div");t.id="financeOverviewContainer";const a=r.querySelector(".provisao-section");return a?a.insertAdjacentElement("afterend",t):r.appendChild(t),t});const e=new window.FinanceOverview;e.render(),e.load()}typeof window.lucide<"u"&&window.lucide.createIcons()}}Ve();function Y(r){return`lk_user_${K().userId??"anon"}_${r}`}const H={DISPLAY_NAME_DISMISSED:()=>Y("display_name_prompt_dismissed_v1"),TOUR_PROMPT_DISMISSED:()=>Y("dashboard_tour_prompt_dismissed_v1"),FIRST_ACTION_TOAST:()=>Y("dashboard_first_action_toast_v1")};class ze{constructor(){this.state={accountCount:0,primaryAction:"create_transaction",transactionCount:null,promptScheduled:!1,tourPromptVisible:!1,awaitingFirstActionFeedback:!1},this.elements={displayNameCard:document.getElementById("dashboardDisplayNamePrompt"),displayNameForm:document.getElementById("dashboardDisplayNameForm"),displayNameInput:document.getElementById("dashboardDisplayNameInput"),displayNameSubmit:document.getElementById("dashboardDisplayNameSubmit"),displayNameDismiss:document.getElementById("dashboardDisplayNameDismiss"),displayNameFeedback:document.getElementById("dashboardDisplayNameFeedback"),quickStart:document.getElementById("dashboardQuickStart"),quickStartTitle:document.querySelector("#dashboardQuickStart .dash-quick-start__header h2"),quickStartDescription:document.querySelector("#dashboardQuickStart .dash-quick-start__header p"),quickStartNotes:Array.from(document.querySelectorAll("#dashboardQuickStart .dash-quick-start__notes span")),firstTransactionCta:document.getElementById("dashboardFirstTransactionCta"),openTourPrompt:document.getElementById("dashboardOpenTourPrompt"),emptyStateTitle:document.querySelector("#emptyState p"),emptyStateDescription:document.querySelector("#emptyState .dash-empty__subtext"),emptyStateCta:document.getElementById("dashboardEmptyStateCta"),fabButton:document.getElementById("fabButton")}}init(){window.LKHelpCenter?.isManagingAutoOffers?.()||this.createTourPrompt(),this.bindEvents(),this.syncDisplayNamePrompt(),re(()=>{this.syncDisplayNamePrompt()}),ce({},{silent:!0}).then(()=>{this.syncDisplayNamePrompt()})}bindEvents(){this.elements.firstTransactionCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.emptyStateCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.openTourPrompt?.addEventListener("click",()=>this.startTour()),this.elements.displayNameDismiss?.addEventListener("click",()=>this.dismissDisplayNamePrompt()),this.elements.displayNameForm?.addEventListener("submit",e=>this.handleDisplayNameSubmit(e)),this.tourPrompt?.querySelector('[data-tour-action="start"]')?.addEventListener("click",()=>this.startTour()),this.tourPrompt?.querySelector('[data-tour-action="dismiss"]')?.addEventListener("click",()=>{localStorage.setItem(H.TOUR_PROMPT_DISMISSED(),"1"),this.hideTourPrompt(),this.focusPrimaryAction()}),document.addEventListener("lukrato:dashboard-overview-rendered",e=>{this.handleOverviewUpdate(e.detail||{})}),document.addEventListener("lukrato:data-changed",e=>{e.detail?.resource==="transactions"&&e.detail?.action==="create"&&(this.state.awaitingFirstActionFeedback=!0)})}createTourPrompt(){const e=document.createElement("div");e.className="dash-tour-offer",e.id="dashboardTourOffer",e.innerHTML=`
      <div class="dash-tour-offer__inner surface-card">
        <div class="dash-tour-offer__icon">
          <i data-lucide="sparkles"></i>
        </div>
        <div class="dash-tour-offer__copy">
          <span class="dash-tour-offer__eyebrow">Tour opcional</span>
          <strong>Quer um tour rápido de 30 segundos?</strong>
          <p>Eu te mostro só o essencial para começar sem travar sua navegação.</p>
        </div>
        <div class="dash-tour-offer__actions">
          <button type="button" class="dash-btn dash-btn--primary" data-tour-action="start">Sim</button>
          <button type="button" class="dash-btn dash-btn--ghost" data-tour-action="dismiss">Agora não</button>
        </div>
      </div>
    `,document.body.appendChild(e),this.tourPrompt=e,typeof window.lucide<"u"&&window.lucide.createIcons()}handleOverviewUpdate(e){const t=Number(this.state.transactionCount??0),a=Number(e.transactionCount||0),i=this.state.transactionCount===null,s=ne(e,{accountCount:Number(e.accountCount??0),actionType:e.primaryAction,ctaLabel:e.ctaLabel,ctaUrl:e.ctaUrl});this.state.accountCount=Number(s.action.accountCount||0),this.state.primaryAction=s.action.actionType,this.state.transactionCount=a,this.toggleQuickStart(a===0&&!e.isDemo),this.togglePrimaryActionFocus(a===0),this.syncPrimaryActionCopy(s),this.syncDisplayNamePrompt(),!this.state.promptScheduled&&this.shouldOfferTour()&&(this.state.promptScheduled=!0,window.setTimeout(()=>{this.shouldOfferTour()&&this.showTourPrompt()},1600)),!i&&t===0&&a>0?this.handleFirstActionCompleted():this.state.awaitingFirstActionFeedback&&a>0&&this.handleFirstActionCompleted()}shouldOfferTour(){const e=K();return!window.LKHelpCenter?.isManagingAutoOffers?.()&&localStorage.getItem(H.TOUR_PROMPT_DISMISSED())!=="1"&&e.tourCompleted!==!0&&this.state.primaryAction==="create_transaction"&&Number(this.state.transactionCount??0)===0}showTourPrompt(){!this.tourPrompt||this.state.tourPromptVisible||(this.state.tourPromptVisible=!0,this.tourPrompt.classList.add("is-visible"))}hideTourPrompt(){this.tourPrompt&&(this.state.tourPromptVisible=!1,this.tourPrompt.classList.remove("is-visible"))}toggleQuickStart(e){this.elements.quickStart&&(this.elements.quickStart.style.display=e?"":"none")}syncPrimaryActionCopy(e){e&&(this.elements.quickStartTitle&&(this.elements.quickStartTitle.textContent=e.quickStartTitle),this.elements.quickStartDescription&&(this.elements.quickStartDescription.textContent=e.quickStartDescription),this.elements.firstTransactionCta&&(this.elements.firstTransactionCta.innerHTML=`<i data-lucide="plus"></i> ${e.quickStartButton}`),this.elements.quickStartNotes.forEach((t,a)=>{if(!t)return;const i=e.quickStartNotes[a]||"",s=t.querySelector("i, svg")?.outerHTML||"";t.innerHTML=`${s} ${i}`}),this.elements.emptyStateTitle&&(this.elements.emptyStateTitle.textContent=e.emptyStateTitle),this.elements.emptyStateDescription&&(this.elements.emptyStateDescription.textContent=e.emptyStateDescription),this.elements.emptyStateCta&&(this.elements.emptyStateCta.innerHTML=`<i data-lucide="plus"></i> ${e.emptyStateButton}`),this.elements.openTourPrompt&&(this.elements.openTourPrompt.style.display=e.shouldOfferTour?"":"none"),e.shouldOfferTour||this.hideTourPrompt(),typeof window.lucide<"u"&&window.lucide.createIcons())}syncDisplayNamePrompt(){if(!this.elements.displayNameCard)return;const e=!!K().needsDisplayNamePrompt&&localStorage.getItem(H.DISPLAY_NAME_DISMISSED())!=="1";this.elements.displayNameCard.style.display=e?"":"none"}dismissDisplayNamePrompt(){localStorage.setItem(H.DISPLAY_NAME_DISMISSED(),"1"),this.syncDisplayNamePrompt()}async handleDisplayNameSubmit(e){if(e.preventDefault(),!this.elements.displayNameInput||!this.elements.displayNameSubmit)return;const t=this.elements.displayNameInput.value.trim();if(t.length<2){this.showDisplayNameFeedback("Use pelo menos 2 caracteres.",!0);return}this.elements.displayNameSubmit.disabled=!0,this.elements.displayNameSubmit.textContent="Salvando...";try{const a=await oe(Ce(),{display_name:t});if(a?.success===!1)throw a;const i=a?.data||{},s=String(i.display_name||t).trim(),n=String(i.first_name||s).trim();Ee({username:s,needsDisplayNamePrompt:!1},{source:"display-name"}),localStorage.removeItem(H.DISPLAY_NAME_DISMISSED()),this.updateGlobalIdentity(s,n),this.showDisplayNameFeedback("Perfeito. Agora o Lukrato já fala com você do jeito certo."),window.setTimeout(()=>this.syncDisplayNamePrompt(),900),window.LK?.toast&&window.LK.toast.success("Nome de exibição salvo.")}catch(a){N("Erro ao salvar nome de exibição",a,"Falha ao salvar nome de exibição"),this.showDisplayNameFeedback(J(a,"Não foi possível salvar agora."),!0)}finally{this.elements.displayNameSubmit.disabled=!1,this.elements.displayNameSubmit.textContent="Salvar nome"}}showDisplayNameFeedback(e,t=!1){this.elements.displayNameFeedback&&(this.elements.displayNameFeedback.hidden=!1,this.elements.displayNameFeedback.textContent=e,this.elements.displayNameFeedback.classList.toggle("is-error",t))}updateGlobalIdentity(e,t){const a=t||e||"U",i=a.charAt(0).toUpperCase();document.querySelectorAll(".greeting-name strong").forEach(o=>{o.textContent=a}),document.querySelectorAll(".avatar-initials-sm, .avatar-initials-xs").forEach(o=>{o.textContent=i});const s=document.getElementById("lkSupportToggle");s&&(s.dataset.supportName=e);const n=document.getElementById("sfName");n&&(n.textContent=e),this.elements.displayNameInput&&(this.elements.displayNameInput.value=e)}startTour(){if(!window.LKHelpCenter?.startCurrentPageTutorial){window.LK?.toast?.info("Tutorial indisponível no momento.");return}localStorage.setItem(H.TOUR_PROMPT_DISMISSED(),"1"),this.hideTourPrompt(),window.LKHelpCenter.startCurrentPageTutorial({source:"dashboard-first-run"})}togglePrimaryActionFocus(e){[this.elements.fabButton,this.elements.firstTransactionCta,document.getElementById("dashboardEmptyStateCta"),document.getElementById("dashboardChartEmptyCta")].forEach(a=>{a&&a.classList.toggle("dash-primary-cta-highlight",e)})}focusPrimaryAction(){this.togglePrimaryActionFocus(!0),this.state.transactionCount===0&&this.elements.quickStart?.scrollIntoView({behavior:"smooth",block:"center"})}handleFirstActionCompleted(){this.state.awaitingFirstActionFeedback=!1,localStorage.getItem(H.FIRST_ACTION_TOAST())!=="1"&&(window.LK?.toast&&window.LK.toast.success("Boa! Você já começou a controlar suas finanças."),localStorage.setItem(H.FIRST_ACTION_TOAST(),"1")),this.hideTourPrompt(),this.togglePrimaryActionFocus(!1)}openPrimaryAction(){ie({primary_action:this.state.primaryAction,real_account_count:this.state.accountCount})}}document.addEventListener("DOMContentLoaded",()=>{document.querySelector(".modern-dashboard")&&(window.dashboardFirstRunExperience||(window.dashboardFirstRunExperience=new ze,window.dashboardFirstRunExperience.init()))});function qe({API:r,CONFIG:e,Utils:t,escapeHtml:a,logClientError:i}){const s={getContainer:(n,o)=>{const c=document.getElementById(o);if(c)return c;const u=document.getElementById(n);if(!u)return null;const p=u.querySelector(".dash-optional-body");if(p)return p.id||(p.id=o),p;const l=document.createElement("div");l.className="dash-optional-body",l.id=o;const d=u.querySelector(".dash-section-header"),f=Array.from(u.children).filter(g=>g.classList?.contains("dash-placeholder"));return d?.nextSibling?u.insertBefore(l,d.nextSibling):u.appendChild(l),f.forEach(g=>l.appendChild(g)),l},renderLoading:n=>{n&&(n.innerHTML=`
                <div class="dash-widget dash-widget--loading" aria-hidden="true">
                    <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
                </div>
            `)},renderEmpty:(n,o,c,u)=>{n&&(n.innerHTML=`
                <div class="dash-widget-empty">
                    <p>${o}</p>
                    ${c&&u?`<a href="${c}" class="dash-widget-link">${u}</a>`:""}
                </div>
            `)},getUsageColor:n=>n>=85?"#ef4444":n>=60?"#f59e0b":"#10b981",getAccountBalance:n=>{const c=[n?.saldoAtual,n?.saldo_atual,n?.saldo,n?.saldoInicial,n?.saldo_inicial].find(u=>Number.isFinite(Number(u)));return Number(c||0)},renderMetas:async n=>{const o=s.getContainer("sectionMetas","sectionMetasBody");if(o){s.renderLoading(o);try{const u=(await r.getFinanceSummary(n))?.metas??null;if(!u||Number(u.total_metas||0)===0){s.renderEmpty(o,"Você ainda não tem metas ativas neste momento.",`${e.BASE_URL}financas#metas`,"Criar meta");return}const p=u.proxima_concluir||null,l=Math.round(Number(u.progresso_geral||0));if(!p){o.innerHTML=`
                        <div class="dash-widget">
                            <span class="dash-widget-label">Metas ativas</span>
                            <strong class="dash-widget-value">${Number(u.total_metas||0)}</strong>
                            <p class="dash-widget-caption">Você tem metas em andamento, mas nenhuma está próxima de conclusão.</p>
                            <div class="dash-widget-meta">
                                <span>Progresso geral</span>
                                <strong>${l}%</strong>
                            </div>
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(l,100)}%; background:var(--color-primary);"></span>
                            </div>
                            <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                        </div>
                    `;return}const d=a(String(p.titulo||"Sua meta principal")),f=Number(p.valor_atual||0),g=Number(p.valor_alvo||0),h=Math.max(g-f,0),m=Math.round(Number(p.progresso||0)),_=p.cor||"var(--color-primary)";o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Próxima meta</span>
                        <strong class="dash-widget-value">${d}</strong>
                        <p class="dash-widget-caption">Faltam ${t.money(h)} para concluir.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(m,100)}%; background:${_};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>${t.money(f)} de ${t.money(g)}</span>
                            <strong style="color:${_};">${m}%</strong>
                        </div>
                        <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `}catch(c){i("Erro ao carregar widget de metas",c,"Falha ao carregar metas"),s.renderEmpty(o,"Não foi possível carregar suas metas agora.",`${e.BASE_URL}financas#metas`,"Tentar nas finanças")}}},renderCartoes:async()=>{const n=s.getContainer("sectionCartoes","sectionCartoesBody");if(n){s.renderLoading(n);try{const o=await r.getCardsSummary(),c=Number(o?.total_cartoes||0);if(!o||c===0){s.renderEmpty(n,"Você ainda não tem cartões ativos no dashboard.",`${e.BASE_URL}cartoes`,"Cadastrar cartão");return}const u=Number(o.limite_disponivel||0),p=Number(o.limite_total||0),l=Math.round(Number(o.percentual_uso||0)),d=s.getUsageColor(l);n.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Limite disponível</span>
                        <strong class="dash-widget-value">${t.money(u)}</strong>
                        <p class="dash-widget-caption">${c} cartão(ões) ativo(s) com ${l}% de uso consolidado.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(l,100)}%; background:${d};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>Limite total ${t.money(p)}</span>
                            <strong style="color:${d};">${l}% usado</strong>
                        </div>
                        <a href="${e.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                    </div>
                `}catch(o){i("Erro ao carregar widget de cartões",o,"Falha ao carregar cartões"),s.renderEmpty(n,"Não foi possível carregar seus cartões agora.",`${e.BASE_URL}cartoes`,"Criar cartões")}}},renderContas:async n=>{const o=s.getContainer("sectionContas","sectionContasBody");if(o){s.renderLoading(o);try{const c=await r.getAccountsBalances(n),u=Array.isArray(c)?c:[];if(u.length===0){s.renderEmpty(o,"Você ainda não tem contas ativas conectadas.",`${e.BASE_URL}contas`,"Adicionar conta");return}const p=u.map(h=>({...h,__saldo:s.getAccountBalance(h)})).sort((h,m)=>m.__saldo-h.__saldo),l=p.reduce((h,m)=>h+m.__saldo,0),d=p[0]||null,f=a(String(d?.nome||d?.nome_conta||d?.instituicao||d?.banco_nome||"Conta principal")),g=d?t.money(d.__saldo):t.money(0);o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Saldo consolidado</span>
                        <strong class="dash-widget-value">${t.money(l)}</strong>
                        <p class="dash-widget-caption">${p.length} conta(s) ativa(s) no painel.</p>
                        <div class="dash-widget-list">
                            ${p.slice(0,3).map(h=>`
                                    <div class="dash-widget-list-item">
                                        <span>${a(String(h.nome||h.nome_conta||h.instituicao||h.banco_nome||"Conta"))}</span>
                                        <strong>${t.money(h.__saldo)}</strong>
                                    </div>
                                `).join("")}
                        </div>
                        <div class="dash-widget-meta">
                            <span>Maior saldo em ${f}</span>
                            <strong>${g}</strong>
                        </div>
                        <a href="${e.BASE_URL}contas" class="dash-widget-link">Abrir contas</a>
                    </div>
                `}catch(c){i("Erro ao carregar widget de contas",c,"Falha ao carregar contas"),s.renderEmpty(o,"Não foi possível carregar suas contas agora.",`${e.BASE_URL}contas`,"Abrir contas")}}},renderOrcamentos:async n=>{const o=s.getContainer("sectionOrcamentos","sectionOrcamentosBody");if(o){s.renderLoading(o);try{const u=(await r.getFinanceSummary(n))?.orcamento??null;if(!u||Number(u.total_categorias||0)===0){s.renderEmpty(o,"Você ainda não definiu limites para categorias.",`${e.BASE_URL}financas#orcamentos`,"Definir limite");return}const p=Math.round(Number(u.percentual_geral||0)),l=s.getUsageColor(p),f=(u.orcamentos||[]).slice().sort((g,h)=>Number(h.percentual||0)-Number(g.percentual||0)).slice(0,3).map(g=>{const h=s.getUsageColor(g.percentual);return`
                        <div class="dash-widget-list-item">
                            <span>${a(g.categoria_nome||"Categoria")}</span>
                            <strong style="color:${h};">${Math.round(g.percentual||0)}%</strong>
                        </div>
                    `}).join("");o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Uso geral dos limites</span>
                        <strong class="dash-widget-value" style="color:${l};">${p}%</strong>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(p,100)}%; background:${l};"></span>
                        </div>
                        <p class="dash-widget-caption">${t.money(u.total_gasto||0)} de ${t.money(u.total_limite||0)}</p>
                        ${f?`<div class="dash-widget-list">${f}</div>`:""}
                        <a href="${e.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                    </div>
                `}catch(c){i("Erro ao carregar widget de orçamentos",c,"Falha ao carregar orçamentos"),s.renderEmpty(o,"Não foi possível carregar seus orçamentos.",`${e.BASE_URL}financas#orcamentos`,"Abrir orçamentos")}}},renderFaturas:async()=>{const n=s.getContainer("sectionFaturas","sectionFaturasBody");if(n){s.renderLoading(n);try{const o=await r.getCardsSummary(),c=Number(o?.total_cartoes||0);if(!o||c===0){s.renderEmpty(n,"Você não tem cartões com faturas abertas.",`${e.BASE_URL}faturas`,"Ver faturas");return}const u=Number(o.fatura_aberta??o.limite_utilizado??0),p=Number(o.limite_total||0),l=p>0?Math.round(u/p*100):Number(o.percentual_uso||0),d=s.getUsageColor(l);n.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Fatura atual</span>
                        <strong class="dash-widget-value">${t.money(u)}</strong>
                        ${p>0?`
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(l,100)}%; background:${d};"></span>
                            </div>
                            <p class="dash-widget-caption">${l}% do limite utilizado</p>
                        `:`
                            <p class="dash-widget-caption">${c} cartão(ões) ativo(s)</p>
                        `}
                        <a href="${e.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                    </div>
                `}catch(o){i("Erro ao carregar widget de faturas",o,"Falha ao carregar faturas"),s.renderEmpty(n,"Não foi possível carregar suas faturas.",`${e.BASE_URL}faturas`,"Ver faturas")}}},render:async n=>{await Promise.allSettled([s.renderMetas(n),s.renderCartoes(),s.renderContas(n),s.renderOrcamentos(n),s.renderFaturas()])}};return s}function Ue({API:r,Utils:e,escapeHtml:t,logClientError:a}){const i={isProUser:null,checkProStatus:async()=>{try{const s=await r.getOverview(e.getCurrentMonth());i.isProUser=s?.plan?.is_pro===!0}catch{i.isProUser=!1}return i.isProUser},render:async s=>{const n=document.getElementById("sectionPrevisao");if(!n)return;await i.checkProStatus();const o=document.getElementById("provisaoProOverlay"),c=i.isProUser;n.classList.remove("is-locked"),o&&(o.style.display="none");try{const u=await r.getOverview(s);i.renderData(u.provisao||null,c)}catch(u){a("Erro ao carregar provisão",u,"Falha ao carregar previsão")}},renderData:(s,n=!0)=>{if(!s)return;const o=s.provisao||{},c=e.money,u=document.getElementById("provisaoTitle"),p=document.getElementById("provisaoHeadline");u&&(u.textContent=`Se continuar assim, você termina o mês com ${c(o.saldo_projetado||0)}`),p&&(p.textContent=(o.saldo_projetado||0)>=0?"A previsão abaixo considera seu saldo atual, o que ainda vai entrar e o que ainda vai sair.":"A previsao indica aperto no fim do mes se o ritmo atual continuar.");const l=document.getElementById("provisaoProximosTitle"),d=document.getElementById("provisaoVerTodos");l&&(l.innerHTML=n?'<i data-lucide="clock"></i> Próximos Vencimentos':'<i data-lucide="credit-card"></i> Próximas Faturas'),d&&(d.href=n?W("lancamentos"):W("faturas"));const f=document.getElementById("provisaoPagar"),g=document.getElementById("provisaoReceber"),h=document.getElementById("provisaoProjetado"),m=document.getElementById("provisaoPagarCount"),_=document.getElementById("provisaoReceberCount"),w=document.getElementById("provisaoProjetadoLabel"),M=g?.closest(".provisao-card");if(f&&(f.textContent=c(o.a_pagar||0)),n?(g&&(g.textContent=c(o.a_receber||0)),M&&(M.style.opacity="1")):(g&&(g.textContent="R$ --"),M&&(M.style.opacity="0.5")),h&&(h.textContent=c(o.saldo_projetado||0),h.style.color=(o.saldo_projetado||0)>=0?"":"var(--color-danger)"),m){const S=o.count_pagar||0,b=o.count_faturas||0;if(n){let T=`${S} pendente${S!==1?"s":""}`;b>0&&(T+=` • ${b} fatura${b!==1?"s":""}`),m.textContent=T}else m.textContent=`${b} fatura${b!==1?"s":""}`}n?_&&(_.textContent=`${o.count_receber||0} pendente${(o.count_receber||0)!==1?"s":""}`):_&&(_.textContent="Pro"),w&&(w.textContent=`saldo atual: ${c(o.saldo_atual||0)}`);const D=s.vencidos||{},A=document.getElementById("provisaoAlertDespesas");if(A){const S=D.despesas||{};if(n&&(S.count||0)>0){A.style.display="flex";const b=document.getElementById("provisaoAlertDespesasCount"),T=document.getElementById("provisaoAlertDespesasTotal");b&&(b.textContent=S.count),T&&(T.textContent=c(S.total||0))}else A.style.display="none"}const x=document.getElementById("provisaoAlertReceitas");if(x){const S=D.receitas||{};if(n&&(S.count||0)>0){x.style.display="flex";const b=document.getElementById("provisaoAlertReceitasCount"),T=document.getElementById("provisaoAlertReceitasTotal");b&&(b.textContent=S.count),T&&(T.textContent=c(S.total||0))}else x.style.display="none"}const v=document.getElementById("provisaoAlertFaturas");if(v){const S=D.count_faturas||0;if(S>0){v.style.display="flex";const b=document.getElementById("provisaoAlertFaturasCount"),T=document.getElementById("provisaoAlertFaturasTotal");b&&(b.textContent=S),T&&(T.textContent=c(D.total_faturas||0))}else v.style.display="none"}const E=document.getElementById("provisaoProximosList"),B=document.getElementById("provisaoEmpty");let $=s.proximos||[];if(n||($=$.filter(S=>S.is_fatura===!0)),E)if($.length===0){if(E.innerHTML="",B){const S=B.querySelector("span");S&&(S.textContent=n?"Nenhum vencimento pendente":"Nenhuma fatura pendente"),E.appendChild(B),B.style.display="flex"}}else{E.innerHTML="";const S=new Date().toISOString().slice(0,10);$.forEach(b=>{const T=(b.tipo||"").toLowerCase(),j=b.is_fatura===!0,Z=(b.data_pagamento||"").split(/[T\s]/)[0],me=Z===S,he=i.formatDateShort(Z);let O="";me&&(O+='<span class="provisao-item-badge vence-hoje">Hoje</span>'),j?(O+='<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>',b.cartao_ultimos_digitos&&(O+=`<span>****${b.cartao_ultimos_digitos}</span>`)):(b.eh_parcelado&&b.numero_parcelas>1&&(O+=`<span class="provisao-item-badge parcela">${b.parcela_atual}/${b.numero_parcelas}</span>`),b.recorrente&&(O+='<span class="provisao-item-badge recorrente">Recorrente</span>'),b.categoria&&(O+=`<span>${t(b.categoria)}</span>`));const ee=j?"fatura":T,q=document.createElement("div");q.className="provisao-item"+(j?" is-fatura":""),q.innerHTML=`
                                <div class="provisao-item-dot ${ee}"></div>
                                <div class="provisao-item-info">
                                    <div class="provisao-item-titulo">${t(b.titulo||"Sem título")}</div>
                                    <div class="provisao-item-meta">${O}</div>
                                </div>
                                <span class="provisao-item-valor ${ee}">${c(b.valor||0)}</span>
                                <span class="provisao-item-data">${he}</span>
                            `,j&&b.cartao_id&&(q.style.cursor="pointer",q.addEventListener("click",()=>{const pe=(b.data_pagamento||"").split(/[T\s]/)[0],[ge,fe]=pe.split("-");window.location.href=W("faturas",{cartao_id:b.cartao_id,mes:parseInt(fe,10),ano:ge})})),E.appendChild(q)})}const V=document.getElementById("provisaoParcelas"),z=s.parcelas||{};if(V)if(n&&(z.ativas||0)>0){V.style.display="flex";const S=document.getElementById("provisaoParcelasText"),b=document.getElementById("provisaoParcelasValor");S&&(S.textContent=`${z.ativas} parcelamento${z.ativas!==1?"s":""} ativo${z.ativas!==1?"s":""}`),b&&(b.textContent=`${c(z.total_mensal||0)}/mês`)}else V.style.display="none"},formatDateShort:s=>{if(!s)return"-";try{const n=s.match(/^(\d{4})-(\d{2})-(\d{2})$/);return n?`${n[3]}/${n[2]}`:"-"}catch{return"-"}}};return i}function je({CONFIG:r,getDashboardOverview:e,getApiPayload:t,apiGet:a,apiDelete:i,apiPost:s,getErrorMessage:n}){function o(l){if(l?.is_demo){window.LKDemoPreviewBanner?.show(l);return}window.LKDemoPreviewBanner?.hide()}const c={getOverview:async(l,d={})=>{const f=await e(l,d),g=t(f,{});return o(g?.meta),g},fetch:async l=>{const d=await a(l);if(d?.success===!1)throw new Error(n({data:d},"Erro na API"));return d?.data??d},getMetrics:async l=>(await c.getOverview(l)).metrics||{},getAccountsBalances:async l=>{const d=await c.getOverview(l);return Array.isArray(d.accounts_balances)?d.accounts_balances:[]},getTransactions:async(l,d)=>{const f=await c.getOverview(l,{limit:d});return Array.isArray(f.recent_transactions)?f.recent_transactions:[]},getChartData:async l=>{const d=await c.getOverview(l);return Array.isArray(d.chart)?d.chart:[]},getFinanceSummary:async l=>{const d=String(l||"").match(/^(\d{4})-(\d{2})$/);if(!d)return{};const f=await a(le(),{ano:Number(d[1]),mes:Number(d[2])});return t(f,{})},getCardsSummary:async()=>{const l=await a(Se());return t(l,{})},deleteTransaction:async l=>{const d=[{request:()=>i(Le(l))},{request:()=>s($e(),{id:l})}];for(const f of d)try{return await f.request()}catch(g){if(g?.status!==404)throw new Error(n(g,"Erro ao excluir"))}throw new Error("Endpoint de exclusão não encontrado.")}},u={ensureSwal:async()=>{window.Swal},toast:(l,d)=>{if(window.LK?.toast)return LK.toast[l]?.(d)||LK.toast.info(d);window.Swal?.fire({toast:!0,position:"top-end",timer:2500,timerProgressBar:!0,showConfirmButton:!1,icon:l,title:d})},loading:(l="Processando...")=>{if(window.LK?.loading)return LK.loading(l);window.Swal?.fire({title:l,didOpen:()=>window.Swal.showLoading(),allowOutsideClick:!1,showConfirmButton:!1})},close:()=>{if(window.LK?.hideLoading)return LK.hideLoading();window.Swal?.close()},confirm:async(l,d)=>window.LK?.confirm?LK.confirm({title:l,text:d,confirmText:"Sim, confirmar",danger:!0}):(await window.Swal?.fire({title:l,text:d,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, confirmar",cancelButtonText:"Cancelar",confirmButtonColor:"var(--color-danger)",cancelButtonColor:"var(--color-text-muted)"}))?.isConfirmed,error:(l,d)=>{if(window.LK?.toast)return LK.toast.error(d||l);window.Swal?.fire({icon:"error",title:l,text:d,confirmButtonColor:"var(--color-primary)"})}},p={badges:[{id:"first",icon:"target",name:"Inicio",condition:l=>l.totalTransactions>=1},{id:"week",icon:"bar-chart-3",name:"7 Dias",condition:l=>l.streak>=7},{id:"month",icon:"gem",name:"30 Dias",condition:l=>l.streak>=30},{id:"saver",icon:"coins",name:"Economia",condition:l=>l.savingsRate>=10},{id:"diverse",icon:"palette",name:"Diverso",condition:l=>l.uniqueCategories>=5},{id:"master",icon:"crown",name:"Mestre",condition:l=>l.totalTransactions>=100}],calculateStreak:l=>{if(!Array.isArray(l)||l.length===0)return 0;const d=l.map(_=>_.data_lancamento||_.data).filter(Boolean).map(_=>{const w=String(_).match(/^(\d{4})-(\d{2})-(\d{2})/);return w?`${w[1]}-${w[2]}-${w[3]}`:null}).filter(Boolean).sort().reverse();if(d.length===0)return 0;const f=[...new Set(d)],g=new Date;g.setHours(0,0,0,0);let h=0,m=new Date(g);for(const _ of f){const[w,M,D]=_.split("-").map(Number),A=new Date(w,M-1,D);A.setHours(0,0,0,0);const x=Math.round((m-A)/(1e3*60*60*24));if(x===0||x===1)h++,m=new Date(A),m.setDate(m.getDate()-1);else if(x>1)break}return h},calculateLevel:l=>l<100?1:l<300?2:l<600?3:l<1e3?4:l<1500?5:l<2500?6:l<5e3?7:l<1e4?8:l<2e4?9:10,calculatePoints:l=>{let d=0;return d+=l.totalTransactions*10,d+=l.streak*50,d+=l.activeMonths*100,d+=l.uniqueCategories*20,d+=Math.floor(l.savingsRate)*30,d},calculateData:(l,d)=>{const f=l.length,g=p.calculateStreak(l),h=new Set(l.map(E=>E.categoria_id||E.categoria).filter(Boolean)).size,_=new Set(l.map(E=>{const B=E.data_lancamento||E.data;if(!B)return null;const $=String(B).match(/^(\d{4}-\d{2})/);return $?$[1]:null}).filter(Boolean)).size,w=Number(d?.receitas||0),M=Number(d?.despesas||0),D=w>0?(w-M)/w*100:0,A={totalTransactions:f,streak:g,uniqueCategories:h,activeMonths:_,savingsRate:Math.max(0,D)},x=p.calculatePoints(A),v=p.calculateLevel(x);return{...A,points:x,level:v}}};return{API:c,Notifications:u,Gamification:p}}function Ke({STATE:r,DOM:e,Utils:t,API:a,Notifications:i,Renderers:s,Provisao:n,OptionalWidgets:o,invalidateDashboardOverview:c,getErrorMessage:u,logClientError:p}){const l={delete:async(g,h)=>{try{if(await i.ensureSwal(),!await i.confirm("Excluir lançamento?","Esta ação não pode ser desfeita."))return;i.loading("Excluindo..."),await a.deleteTransaction(Number(g)),i.close(),i.toast("success","Lançamento excluído com sucesso!"),h&&(h.style.opacity="0",h.style.transform="translateX(-20px)",setTimeout(()=>{h.remove(),e.tableBody.children.length===0&&(e.emptyState&&(e.emptyState.style.display="block"),e.table&&(e.table.style.display="none"))},300)),document.dispatchEvent(new CustomEvent("lukrato:data-changed",{detail:{resource:"transactions",action:"delete",id:Number(g)}}))}catch(m){console.error("Erro ao excluir lançamento:",m),await i.ensureSwal(),i.error("Erro",u(m,"Falha ao excluir lançamento"))}}},d={refresh:async({force:g=!1}={})=>{if(r.isLoading)return;r.isLoading=!0;const h=t.getCurrentMonth();r.currentMonth=h,g&&c(h);try{s.updateMonthLabel(h),await Promise.allSettled([s.renderKPIs(h),s.renderTable(h),s.renderTransactionsList(h),s.renderChart(h),n.render(h),o.render(h)])}catch(m){p("Erro ao atualizar dashboard",m,"Falha ao atualizar dashboard")}finally{r.isLoading=!1}},init:async()=>{await d.refresh({force:!1})}};return{TransactionManager:l,DashboardManager:d,EventListeners:{init:()=>{if(r.eventListenersInitialized)return;r.eventListenersInitialized=!0,e.tableBody?.addEventListener("click",async h=>{const m=h.target.closest(".btn-del");if(!m)return;const _=h.target.closest("tr"),w=m.getAttribute("data-id");w&&(m.disabled=!0,await l.delete(w,_),m.disabled=!1)}),e.cardsContainer?.addEventListener("click",async h=>{const m=h.target.closest(".btn-del");if(!m)return;const _=h.target.closest(".transaction-card"),w=m.getAttribute("data-id");w&&(m.disabled=!0,await l.delete(w,_),m.disabled=!1)}),e.transactionsList?.addEventListener("click",async h=>{const m=h.target.closest(".btn-del");if(!m)return;const _=h.target.closest(".dash-tx-item"),w=m.getAttribute("data-id");w&&(m.disabled=!0,await l.delete(w,_),m.disabled=!1)}),document.addEventListener("lukrato:data-changed",()=>{c(r.currentMonth||t.getCurrentMonth()),d.refresh({force:!1})}),document.addEventListener("lukrato:month-changed",()=>{d.refresh({force:!1})}),document.addEventListener("lukrato:theme-changed",()=>{s.renderChart(r.currentMonth||t.getCurrentMonth())});const g=document.getElementById("chartToggle");g&&g.addEventListener("click",h=>{const m=h.target.closest("[data-mode]");if(!m)return;const _=m.getAttribute("data-mode");g.querySelectorAll(".dash-chart-toggle__btn").forEach(w=>w.classList.remove("is-active")),m.classList.add("is-active"),s.renderChart(r.currentMonth||t.getCurrentMonth(),_)})}}}}const{API:P,Notifications:Ge}=je({CONFIG:F,getDashboardOverview:U,getApiPayload:ye,apiGet:X,apiDelete:ve,apiPost:oe,getErrorMessage:J}),L={updateMonthLabel:r=>{C.monthLabel&&(C.monthLabel.textContent=y.formatMonth(r))},toggleAlertsSection:()=>{const r=document.getElementById("dashboardAlertsSection");r&&(r.style.display="none")},setSignedState:(r,e,t)=>{const a=document.getElementById(r),i=document.getElementById(e);!a||!i||(a.classList.remove("is-positive","is-negative","income","expense"),i.classList.remove("is-positive","is-negative"),t>0?(a.classList.add("is-positive"),i.classList.add("is-positive")):t<0&&(a.classList.add("is-negative"),i.classList.add("is-negative")))},formatSignedMoney:r=>{const e=Number(r||0);return`${e>=0?"+":"-"}${y.money(Math.abs(e))}`},renderStatusChip:(r,e,t)=>{r&&(r.innerHTML=`
            <i data-lucide="${e}" class="dashboard-status-chip-icon" style="width:16px;height:16px;"></i>
            <span>${t}</span>
        `,typeof window.lucide<"u"&&window.lucide.createIcons())},renderHeroNarrative:({saldo:r,receitas:e,despesas:t,resultado:a})=>{const i=document.getElementById("dashboardHeroStatus"),s=document.getElementById("dashboardHeroMessage"),n=Number(e||0),o=Number(t||0),c=Number.isFinite(Number(a))?Number(a):n-o;if(!(!i||!s)){if(i.className="dashboard-status-chip",s.className="dashboard-hero-message",o>n){i.classList.add("dashboard-status-chip--negative"),s.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(i,"triangle-alert",`Mês no vermelho (${L.formatSignedMoney(c)})`),s.textContent=`Atenção: você gastou mais do que ganhou (${L.formatSignedMoney(c)}).`;return}if(c>0){i.classList.add("dashboard-status-chip--positive"),s.classList.add("dashboard-hero-message--positive"),L.renderStatusChip(i,r>=0?"piggy-bank":"trending-up",r>=0?`Mês positivo (${L.formatSignedMoney(c)})`:`Recuperando o mês (${L.formatSignedMoney(c)})`),s.textContent=`Você está positivo este mês (${L.formatSignedMoney(c)}).`;return}if(c===0){i.classList.add("dashboard-status-chip--neutral"),L.renderStatusChip(i,"scale","Mês zerado (R$ 0,00)"),s.textContent=`Entrou ${y.money(n)} e saiu ${y.money(o)}. Seu saldo do mês está em R$ 0,00.`;return}i.classList.add("dashboard-status-chip--negative"),s.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(i,"wallet",`Resultado do mês ${L.formatSignedMoney(c)}`),s.textContent=`Seu resultado mensal está em ${L.formatSignedMoney(c)}. Vale rever os gastos mais pesados agora.`}},renderHeroSparkline:async r=>{const e=document.getElementById("heroSparkline");if(!(!e||typeof ApexCharts>"u"))try{const t=await P.getOverview(r),a=Array.isArray(t.chart)?t.chart:[];if(a.length<2){e.innerHTML="";return}const i=a.map(c=>Number(c.resultado||0)),{isLightTheme:s}=te(),o=(i[i.length-1]||0)>=0?"#10b981":"#ef4444";I._heroSparkInstance&&(I._heroSparkInstance.destroy(),I._heroSparkInstance=null),I._heroSparkInstance=new ApexCharts(e,{chart:{type:"area",height:48,sparkline:{enabled:!0},background:"transparent"},series:[{data:i}],stroke:{width:2,curve:"smooth",colors:[o]},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:0,stops:[0,100],colorStops:[{offset:0,color:o,opacity:.25},{offset:100,color:o,opacity:0}]}},tooltip:{enabled:!0,fixed:{enabled:!1},x:{show:!1},y:{formatter:c=>y.money(c),title:{formatter:()=>""}},theme:s?"light":"dark"},colors:[o]}),I._heroSparkInstance.render()}catch{}},renderHeroContext:({receitas:r,despesas:e})=>{const t=document.getElementById("heroContext");if(!t)return;const a=Number(r||0),i=Number(e||0);if(a<=0){t.style.display="none";return}const s=(a-i)/a*100;let n,o,c;s>=20?(n="piggy-bank",o=`Você está economizando ${Math.round(s)}% da renda — excelente!`,c="dash-hero__context--positive"):s>=1?(n="target",o=`Economia de ${Math.round(s)}% da renda — meta ideal é 20%.`,c="dash-hero__context--neutral"):(n="alert-triangle",o="Sem margem de economia este mês. Revise seus gastos.",c="dash-hero__context--negative"),t.className=`dash-hero__context ${c}`,t.innerHTML=`<i data-lucide="${n}" style="width:14px;height:14px;"></i> ${o}`,t.style.display="",typeof window.lucide<"u"&&window.lucide.createIcons()},renderOverviewAlerts:({receitas:r,despesas:e})=>{const t=document.getElementById("dashboardAlertsOverview");if(!t)return;const a=document.getElementById("dashboardAlertsSection");a&&(a.style.display="none");const i=Number(r||0),s=Number(e||0),n=i-s;s>i?(t.innerHTML=`
                <a href="${F.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>Atenção: você gastou mais do que ganhou</strong>
                        <span>Entrou ${y.money(i)} e saiu ${y.money(s)}. Diferença do mês: ${L.formatSignedMoney(n)}.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `,typeof window.lucide<"u"&&window.lucide.createIcons()):t.innerHTML="",L.toggleAlertsSection()},renderChartInsight:(r,e)=>{const t=document.getElementById("chartInsight");if(!t)return;if(!Array.isArray(e)||e.length===0||e.every(n=>Number(n)===0)){t.textContent="Seu historico aparece aqui conforme você usa o Lukrato mais vezes.";return}let a=0;e.forEach((n,o)=>{Number(n)<Number(e[a])&&(a=o)});const i=r[a],s=Number(e[a]||0);if(s<0){t.textContent=`Seu pior mes foi ${y.formatMonth(i)} (${y.money(s)}).`;return}t.textContent=`Seu pior mes foi ${y.formatMonth(i)} e mesmo assim fechou em ${y.money(s)}.`},renderKPIs:async r=>{try{const e=await P.getOverview(r),t=e?.metrics||{},a=Array.isArray(e?.accounts_balances)?e.accounts_balances:[],i=e?.meta||{},s={receitasValue:t.receitas||0,despesasValue:t.despesas||0,saldoMesValue:t.resultado||0};Object.entries(s).forEach(([f,g])=>{const h=document.getElementById(f);h&&(h.textContent=y.money(g))});const n=Number(t.saldoAcumulado??t.saldo??0),o=(Array.isArray(a)?a:[]).reduce((f,g)=>{const h=typeof g.saldoAtual=="number"?g.saldoAtual:g.saldoInicial||0;return f+(isFinite(h)?Number(h):0)},0),c=Array.isArray(a)&&a.length>0?o:n;C.saldoValue&&(C.saldoValue.textContent=y.money(c)),L.setSignedState("saldoValue","saldoCard",c),L.setSignedState("saldoMesValue","saldoMesCard",Number(t.resultado||0)),L.renderHeroNarrative({saldo:c,receitas:Number(t.receitas||0),despesas:Number(t.despesas||0),resultado:Number(t.resultado||0)}),L.renderHeroSparkline(r),L.renderHeroContext({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)}),L.renderOverviewAlerts({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)});const u=Number(i?.real_transaction_count??t.count??0),p=Number(i?.real_category_count??t.categories??0),l=Number(i?.real_account_count??a.length??0),d=_e(i,{accountCount:l});document.dispatchEvent(new CustomEvent("lukrato:dashboard-overview-rendered",{detail:{month:r,accountCount:l,transactionCount:u,categoryCount:p,hasData:u>0,primaryAction:d.actionType,ctaLabel:d.ctaLabel,ctaUrl:d.ctaUrl,isDemo:!!i?.is_demo}})),y.removeLoadingClass()}catch(e){N("Erro ao renderizar KPIs",e,"Falha ao carregar indicadores"),["saldoValue","receitasValue","despesasValue","saldoMesValue"].forEach(t=>{const a=document.getElementById(t);a&&(a.textContent="R$ 0,00",a.classList.remove("loading"))})}},renderTable:async r=>{try{const e=await P.getTransactions(r,F.TRANSACTIONS_LIMIT);C.tableBody&&(C.tableBody.innerHTML=""),C.cardsContainer&&(C.cardsContainer.innerHTML=""),Array.isArray(e)&&e.length>0&&e.forEach(a=>{const i=String(a.tipo||"").toLowerCase(),s=y.getTipoClass(i),n=String(a.tipo||"").replace(/_/g," "),o=a.categoria_nome??(typeof a.categoria=="string"?a.categoria:a.categoria?.nome)??null,c=o?k(o):'<span class="categoria-empty">Sem categoria</span>',u=k(y.getContaLabel(a)),p=k(a.descricao||"--"),l=k(n),d=Number(a.valor)||0,f=y.dateBR(a.data),g=document.createElement("tr");if(g.setAttribute("data-id",a.id),g.innerHTML=`
              <td data-label="Data">${f}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${s}">${l}</span>
              </td>
              <td data-label="Categoria">${c}</td>
              <td data-label="Conta">${u}</td>
              <td data-label="Descrição">${p}</td>
              <td data-label="Valor" class="valor-cell ${s}">${y.money(d)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `,C.tableBody&&C.tableBody.appendChild(g),C.cardsContainer){const h=document.createElement("div");h.className="transaction-card",h.setAttribute("data-id",a.id),h.innerHTML=`
                <div class="transaction-card-header">
                  <span class="transaction-date">${f}</span>
                  <span class="transaction-value ${s}">${y.money(d)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${s}">${l}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${c}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${u}</span>
                  </div>
                  ${p!=="--"?`
                  <div class="transaction-info-row">
                    <span class="transaction-label">Descrição</span>
                    <span class="transaction-description">${p}</span>
                  </div>
                  `:""}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `,C.cardsContainer.appendChild(h)}})}catch(e){N("Erro ao renderizar transações",e,"Falha ao carregar transações")}},renderTransactionsList:async r=>{if(C.transactionsList)try{const e=await P.getTransactions(r,F.TRANSACTIONS_LIMIT),t=Array.isArray(e)&&e.length>0;if(C.transactionsList.innerHTML="",C.emptyState&&(C.emptyState.style.display=t?"none":"flex"),!t)return;const a=new Date().toISOString().slice(0,10),i=new Date(Date.now()-864e5).toISOString().slice(0,10),s=new Map;e.forEach(n=>{const o=String(n.data||"").split(/[T\s]/)[0];s.has(o)||s.set(o,[]),s.get(o).push(n)});for(const[n,o]of s){let c;n===a?c="Hoje":n===i?c="Ontem":c=y.dateBR(n);const u=document.createElement("div");u.className="dash-tx-date-group",u.textContent=c,C.transactionsList.appendChild(u),o.forEach(p=>{const d=String(p.tipo||"").toLowerCase()==="receita",f=k(p.descricao||"--"),g=p.categoria_nome??(typeof p.categoria=="string"?p.categoria:p.categoria?.nome)??"Sem categoria",h=Number(p.valor)||0,m=!!p.pago,_=p.categoria_icone||(d?"arrow-down-left":"arrow-up-right"),w=document.createElement("div");w.className="dash-tx-item surface-card",w.setAttribute("data-id",p.id),w.innerHTML=`
                        <div class="dash-tx__left">
                            <div class="dash-tx__icon dash-tx__icon--${d?"income":"expense"}">
                                <i data-lucide="${k(_)}"></i>
                            </div>
                            <div class="dash-tx__info">
                                <span class="dash-tx__desc">${f}</span>
                                <span class="dash-tx__category">${k(g)}</span>
                            </div>
                        </div>
                        <div class="dash-tx__right">
                            <span class="dash-tx__amount dash-tx__amount--${d?"income":"expense"}">${d?"+":"-"}${y.money(Math.abs(h))}</span>
                            <span class="dash-tx__badge dash-tx__badge--${m?"paid":"pending"}">${m?"Pago":"Pendente"}</span>
                        </div>
                    `,C.transactionsList.appendChild(w)})}typeof window.lucide<"u"&&window.lucide.createIcons()}catch(e){N("Erro ao renderizar lista de transações",e,"Falha ao carregar transações"),C.emptyState&&(C.emptyState.style.display="flex")}},renderChart:async(r,e)=>{if(!(!C.categoryChart||typeof ApexCharts>"u")){e||(e=I._chartMode||"donut"),I._chartMode=e,C.chartLoading&&(C.chartLoading.style.display="flex");try{const t=await P.getOverview(r),a=Array.isArray(t.despesas_por_categoria)?t.despesas_por_categoria:[],{isLightTheme:i}=te(),s=i?"light":"dark";if(I.chartInstance&&(I.chartInstance.destroy(),I.chartInstance=null),a.length===0){const o=ne(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)});C.categoryChart.innerHTML=`
                    <div class="dash-chart-empty">
                        <i data-lucide="pie-chart"></i>
                        <strong>${k(o.chartEmptyTitle)}</strong>
                        <p>${k(o.chartEmptyDescription)}</p>
                        <button class="dash-btn dash-btn--ghost" type="button" id="dashboardChartEmptyCta">
                            <i data-lucide="plus"></i> ${k(o.chartEmptyButton)}
                        </button>
                    </div>
                `,document.getElementById("dashboardChartEmptyCta")?.addEventListener("click",()=>{ie(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)})}),typeof window.lucide<"u"&&window.lucide.createIcons();return}const n=["#E67E22","#2ecc71","#e74c3c","#3498db","#9b59b6","#1abc9c","#f39c12","#e91e63","#00bcd4","#8bc34a"];if(e==="compare"){const c=y.getPreviousMonths(r,2)[0];let u=[];try{const m=await P.getOverview(c);u=Array.isArray(m.despesas_por_categoria)?m.despesas_por_categoria:[]}catch{}const l=[...new Set([...a.map(m=>m.categoria),...u.map(m=>m.categoria)])],d=Object.fromEntries(a.map(m=>[m.categoria,Math.abs(Number(m.valor)||0)])),f=Object.fromEntries(u.map(m=>[m.categoria,Math.abs(Number(m.valor)||0)])),g=l.map(m=>d[m]||0),h=l.map(m=>f[m]||0);I.chartInstance=new ApexCharts(C.categoryChart,{chart:{type:"bar",height:300,background:"transparent",fontFamily:"Inter, Arial, sans-serif",toolbar:{show:!1}},series:[{name:y.formatMonthShort(r),data:g},{name:y.formatMonthShort(c),data:h}],colors:["#E67E22","rgba(230,126,34,0.35)"],xaxis:{categories:l,labels:{style:{colors:i?"#555":"#aaa",fontSize:"11px"},rotate:-35,trim:!0,maxHeight:80}},yaxis:{labels:{formatter:m=>y.money(m),style:{colors:i?"#555":"#aaa"}}},plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},dataLabels:{enabled:!1},legend:{position:"top",fontSize:"12px",labels:{colors:i?"#555":"#ccc"}},tooltip:{theme:s,y:{formatter:m=>y.money(m)}},grid:{borderColor:i?"#e5e5e5":"rgba(255,255,255,0.06)",strokeDashArray:3},theme:{mode:s}})}else{const o=a.map(u=>u.categoria),c=a.map(u=>Math.abs(Number(u.valor)||0));I.chartInstance=new ApexCharts(C.categoryChart,{chart:{type:"donut",height:280,background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:c,labels:o,colors:n.slice(0,o.length),stroke:{width:2,colors:[i?"#fff":"#1e1e1e"]},plotOptions:{pie:{donut:{size:"60%",labels:{show:!0,value:{formatter:u=>y.money(Number(u))},total:{show:!0,label:"Total",formatter:u=>y.money(u.globals.seriesTotals.reduce((p,l)=>p+l,0))}}}}},legend:{position:"bottom",fontSize:"13px",labels:{colors:i?"#555":"#ccc"}},tooltip:{theme:s,y:{formatter:u=>y.money(u)}},dataLabels:{enabled:!1},theme:{mode:s}})}I.chartInstance.render()}catch(t){N("Erro ao renderizar gráfico",t,"Falha ao carregar gráfico")}finally{C.chartLoading&&setTimeout(()=>{C.chartLoading.style.display="none"},300)}}}},We=qe({API:P,CONFIG:F,Utils:y,escapeHtml:k,logClientError:N}),Ye=Ue({API:P,Utils:y,escapeHtml:k,logClientError:N}),{DashboardManager:Q,EventListeners:Qe}=Ke({STATE:I,DOM:C,Utils:y,API:P,Notifications:Ge,Renderers:L,Provisao:Ye,OptionalWidgets:We,invalidateDashboardOverview:R,getErrorMessage:J,logClientError:N}),Xe={toggleHealthScore:"sectionHealthScore",toggleAiTip:"sectionAiTip",toggleEvolucao:"sectionEvolucao",toggleAlertas:"sectionAlertas",toggleGrafico:"chart-section",togglePrevisao:"sectionPrevisao",toggleMetas:"sectionMetas",toggleCartoes:"sectionCartoes",toggleContas:"sectionContas",toggleOrcamentos:"sectionOrcamentos",toggleFaturas:"sectionFaturas",toggleGamificacao:"sectionGamificacao"},ue={toggleHealthScore:!0,toggleAiTip:!0,toggleEvolucao:!0,toggleAlertas:!0,toggleGrafico:!0,togglePrevisao:!0,toggleMetas:!1,toggleCartoes:!1,toggleContas:!1,toggleOrcamentos:!1,toggleFaturas:!1,toggleGamificacao:!1},Je={...ue,toggleHealthScore:!1,toggleAiTip:!1,toggleEvolucao:!1,togglePrevisao:!1};async function Ze(){return xe("dashboard")}async function et(r){await Ae("dashboard",r)}const tt=Ie({storageKey:"lk_dashboard_prefs",sectionMap:Xe,completeDefaults:ue,essentialDefaults:Je,gridContainerId:"optionalGrid",gridToggleKeys:["toggleMetas","toggleCartoes","toggleContas","toggleOrcamentos","toggleFaturas"],loadPreferences:Ze,savePreferences:et});function at(){tt.init()}window.__LK_DASHBOARD_LOADER__||(window.__LK_DASHBOARD_LOADER__=!0,window.refreshDashboard=Q.refresh,window.LK=window.LK||{},window.LK.refreshDashboard=Q.refresh,(()=>{const e=()=>{Qe.init(),Q.init(),at()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",e):e()})());
