import{m as X,l as N,k as ne,i as pe,e as oe,c as te,d as $e,p as Ie}from"./api-DpYnTMaG.js";import{a as xe,i as Ae,g as ge,o as fe,r as Be}from"./primary-actions-D39VL42P.js";import{o as ye,g as ie,e as ve,d as Me,a as Te}from"./runtime-config-CXTcOn9X.js";import{t as be,u as ke}from"./finance-CgaDv1sH.js";import{j as Ne,k as De}from"./lancamentos-BFIM3VKH.js";import{e as k}from"./utils-Bj4jxwhy.js";import{c as Re,p as Pe,f as He}from"./ui-preferences-CUuIZRMg.js";const F={BASE_URL:X(),TRANSACTIONS_LIMIT:5,CHART_MONTHS:6,ANIMATION_DELAY:300},_={saldoValue:document.getElementById("saldoValue"),receitasValue:document.getElementById("receitasValue"),despesasValue:document.getElementById("despesasValue"),saldoMesValue:document.getElementById("saldoMesValue"),categoryChart:document.getElementById("categoryChart"),chartLoading:document.getElementById("chartLoading"),transactionsList:document.getElementById("transactionsList"),emptyState:document.getElementById("emptyState"),metasBody:document.getElementById("sectionMetasBody"),cartoesBody:document.getElementById("sectionCartoesBody"),contasBody:document.getElementById("sectionContasBody"),orcamentosBody:document.getElementById("sectionOrcamentosBody"),faturasBody:document.getElementById("sectionFaturasBody"),chartContainer:document.getElementById("categoryChart"),tableBody:document.getElementById("transactionsTableBody"),table:document.getElementById("transactionsTable"),cardsContainer:document.getElementById("transactionsCards"),monthLabel:document.getElementById("currentMonthText"),streakDays:document.getElementById("streakDays"),badgesGrid:document.getElementById("badgesGrid"),userLevel:document.getElementById("userLevel"),totalLancamentos:document.getElementById("totalLancamentos"),totalCategorias:document.getElementById("totalCategorias"),mesesAtivos:document.getElementById("mesesAtivos"),pontosTotal:document.getElementById("pontosTotal")},I={chartInstance:null,currentMonth:null,isLoading:!1},b={money:r=>{try{return Number(r||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}catch{return"R$ 0,00"}},dateBR:r=>{if(!r)return"-";try{const t=String(r).split(/[T\s]/)[0].match(/^(\d{4})-(\d{2})-(\d{2})$/);return t?`${t[3]}/${t[2]}/${t[1]}`:"-"}catch{return"-"}},formatMonth:r=>{try{const[e,t]=String(r).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})}catch{return"-"}},formatMonthShort:r=>{try{const[e,t]=String(r).split("-").map(Number);return new Date(e,t-1,1).toLocaleDateString("pt-BR",{month:"short"})}catch{return"-"}},getCurrentMonth:()=>window.LukratoHeader?.getMonth?.()||new Date().toISOString().slice(0,7),getPreviousMonths:(r,e)=>{const t=[],[a,i]=r.split("-").map(Number);for(let n=e-1;n>=0;n--){const o=new Date(a,i-1-n,1),s=o.getFullYear(),c=String(o.getMonth()+1).padStart(2,"0");t.push(`${s}-${c}`)}return t},getCssVar:(r,e="")=>{try{return(getComputedStyle(document.documentElement).getPropertyValue(r)||"").trim()||e}catch{return e}},isLightTheme:()=>{try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getContaLabel:r=>{if(typeof r.conta=="string"&&r.conta.trim())return r.conta.trim();const e=r.conta_instituicao??r.conta_nome??r.conta?.instituicao??r.conta?.nome??null,t=r.conta_destino_instituicao??r.conta_destino_nome??r.conta_destino?.instituicao??r.conta_destino?.nome??null;return r.eh_transferencia&&(e||t)?`${e||"-"}${t||"-"}`:r.conta_label&&String(r.conta_label).trim()?String(r.conta_label).trim():e||"-"},getTipoClass:r=>{const e=String(r||"").toLowerCase();return e==="receita"?"receita":e.includes("despesa")?"despesa":e.includes("transferencia")?"transferencia":""},removeLoadingClass:()=>{setTimeout(()=>{document.querySelectorAll(".kpi-value.loading").forEach(r=>{r.classList.remove("loading")})},F.ANIMATION_DELAY)}},de=()=>{const r=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||b.isLightTheme?.();return{isLightTheme:r,axisColor:r?b.getCssVar("--color-primary","#e67e22")||"#e67e22":"rgba(255, 255, 255, 0.6)",yTickColor:r?"#000":"#fff",xTickColor:r?b.getCssVar("--color-text-muted","#6c757d")||"#6c757d":"rgba(255, 255, 255, 0.6)",gridColor:r?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",tooltipBg:r?"rgba(255, 255, 255, 0.92)":"rgba(0, 0, 0, 0.85)",tooltipColor:r?"#0f172a":"#f8fafc",labelColor:r?"#0f172a":"#f8fafc"}};function Fe(){return"api/v1/dashboard/overview"}function Oe(){return"api/v1/dashboard/evolucao"}const Ve=3e4;function qe(r,e){return`dashboard:overview:${r}:${e}`}function K(r=b.getCurrentMonth(),{limit:e=F.TRANSACTIONS_LIMIT,force:t=!1}={}){return xe(Fe(),{month:r,limit:e},{cacheKey:qe(r,e),ttlMs:Ve,force:t})}function P(r=null){const e=r?`dashboard:overview:${r}:`:"dashboard:overview:";Ae(e)}class ze{constructor(e="greetingContainer"){this.container=document.getElementById(e),this.userName=this.getUserName(),this._listeningDataChanged=!1,ye(()=>{this.userName=this.getUserName(),this.updateGreetingTitle()})}getUserName(){return String(ie().username||"Usuario").trim().split(/\s+/)[0]||"Usuario"}render(){if(!this.container)return;this.userName=this.getUserName();const e=this.getGreeting(),a=new Date().toLocaleDateString("pt-BR",{weekday:"long",day:"numeric",month:"long"});this.container.innerHTML=`
      <div class="dashboard-greeting dashboard-greeting--compact" data-aos="fade-right" data-aos-duration="500">
        <p class="greeting-date">${a}</p>
        <p class="greeting-title">${e.title}</p>
        <div class="greeting-insight" id="greetingInsight">
          <div class="insight-skeleton">
            <div class="skeleton-line" style="width: 70%;"></div>
          </div>
        </div>
      </div>
    `,this.loadInsight(),ve({},{silent:!0})}updateGreetingTitle(){const e=this.container?.querySelector(".greeting-title");e&&(e.textContent=this.getGreeting().title)}getGreeting(){const e=new Date().getHours();return e>=5&&e<12?{title:`Bom dia, ${this.userName}.`}:e>=12&&e<18?{title:`Boa tarde, ${this.userName}.`}:e>=18&&e<24?{title:`Boa noite, ${this.userName}.`}:{title:`Boa madrugada, ${this.userName}.`}}async loadInsight({force:e=!1}={}){try{const t=await K(void 0,{force:e}),a=t?.data??t;a?.greeting_insight?this.displayInsight(a.greeting_insight):this.displayFallbackInsight()}catch(t){N("Error loading greeting insight",t,"Falha ao carregar insight"),this.displayFallbackInsight()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{P(),this.loadInsight({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.loadInsight({force:!0})}))}displayInsight(e){const t=document.getElementById("greetingInsight");if(!t)return;const{message:a,icon:i,color:n}=e;t.innerHTML=`
      <div class="insight-content">
        <div class="insight-icon" style="color: ${n||"var(--color-primary)"};">
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
    `,typeof window.lucide<"u"&&window.lucide.createIcons())}}window.DashboardGreeting=ze;class Ue{constructor(e="healthScoreContainer"){this.container=document.getElementById(e),this.healthScore=0,this.maxScore=100,this.animationDuration=1200}render(){if(!this.container)return;const e=45;this.circumference=2*Math.PI*e;const t=this.circumference;this.container.innerHTML=`
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
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await K(void 0,{force:e}),a=t?.data??t;a?.health_score&&this.updateScore(a.health_score)}catch(t){N("Error loading health score",t,"Falha ao carregar health score"),this.showError()}this._listeningDataChanged||(this._listeningDataChanged=!0,document.addEventListener("lukrato:data-changed",()=>{P(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.load({force:!0})}))}updateScore(e){const{score:t=0}=e;this.animateGauge(t),this.updateBreakdown(e),this.updateStatusIndicator(t)}animateGauge(e){const t=document.getElementById("gaugeCircle"),a=document.getElementById("gaugeValue");if(!t||!a)return;const i=this.circumference||2*Math.PI*45;let n=0;const o=e/(this.animationDuration/16),s=()=>{n+=o,n>=e&&(n=e);const c=i-i*n/this.maxScore;t.setAttribute("stroke-dashoffset",c),a.textContent=Math.round(n),n<e&&requestAnimationFrame(s)};s()}updateBreakdown(e){const t=document.getElementById("hsLancamentos"),a=document.getElementById("hsOrcamento"),i=document.getElementById("hsMetas");if(t){const n=e.lancamentos??0;t.textContent=`${n}`,n>=10?t.className="hs-metric-value color-success":n>=5?t.className="hs-metric-value color-warning":t.className="hs-metric-value color-muted"}if(a){const n=e.orcamentos??0,o=e.orcamentos_ok??0;n===0?(a.textContent="--",a.className="hs-metric-value color-muted"):(a.textContent=`${o}/${n}`,o===n?a.className="hs-metric-value color-success":o>=n/2?a.className="hs-metric-value color-warning":a.className="hs-metric-value color-danger")}if(i){const n=e.metas_ativas??0,o=e.metas_concluidas??0;n===0?(i.textContent="--",i.className="hs-metric-value color-muted"):o>0?(i.textContent=`${n}+${o}`,i.className="hs-metric-value color-success"):(i.textContent=`${n}`,i.className="hs-metric-value color-warning")}}updateStatusIndicator(e){const t=document.getElementById("healthIndicator"),a=document.getElementById("healthMessage");if(!t)return;let i="critical",n="CRÍTICA",o="Ajustes rápidos podem evitar aperto financeiro.";e>=70?(i="excellent",n="BOA",o="Você está no controle. Continue assim!"):e>=50?(i="good",n="ESTÁVEL",o="Controle bom, mas há espaço para melhorar."):e>=30&&(i="warning",n="ATENÇÃO",o="Alguns sinais pedem cuidado neste mês."),t.className=`hs-badge hs-badge--${i}`,t.innerHTML=`
      <span class="hs-badge-dot"></span>
      <span class="hs-badge-text">${n}</span>
    `,a&&(a.textContent=o)}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}showError(){const e=document.getElementById("healthIndicator"),t=document.getElementById("healthMessage");e&&(e.className="hs-badge hs-badge--error",e.innerHTML=`
        <span class="hs-badge-dot"></span>
        <span class="hs-badge-text">Erro</span>
      `),t&&(t.textContent="Não foi possível carregar.")}}window.HealthScoreWidget=Ue;class je{constructor(e="healthScoreInsights"){this.container=document.getElementById(e),this.baseURL=X(),this.init()}init(){this.container&&(this._initialized||(this._initialized=!0,this.renderSkeleton(),this.loadInsights(),this._intervalId=setInterval(()=>this.loadInsights({force:!0}),3e5),document.addEventListener("lukrato:data-changed",()=>{P(),this.loadInsights({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.loadInsights({force:!0})})))}renderSkeleton(){this.container.innerHTML=`
      <div class="hsi-list">
        <div class="hsi-skeleton"></div>
        <div class="hsi-skeleton"></div>
      </div>
    `}async loadInsights({force:e=!1}={}){try{const t=await K(void 0,{force:e}),a=t?.data??t;a?.health_score_insights?this.renderInsights(a.health_score_insights):this.renderEmpty()}catch(t){N("Error loading health score insights",t,"Falha ao carregar insights"),this.renderEmpty()}}renderInsights(e){const t=Array.isArray(e)?e:e?.insights||[],a=Array.isArray(e)?"":e?.total_possible_improvement||"";if(t.length===0){this.renderEmpty();return}const i=t.map((n,o)=>{const s=this.normalizeInsight(n);return`
      <a href="${this.baseURL}${s.action.url}" class="hsi-card hsi-card--${s.priority}" style="animation-delay: ${o*80}ms;">
        <div class="hsi-card-icon hsi-icon--${s.priority}">
          <i data-lucide="${this.getIconForType(s.type)}" style="width:16px;height:16px;"></i>
        </div>
        <div class="hsi-card-body">
          <span class="hsi-card-title">${s.title}</span>
          <span class="hsi-card-desc">${s.message}</span>
        </div>
        <div class="hsi-card-meta">
          <span class="hsi-impact">${s.impact}</span>
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
    `,typeof window.lucide<"u"&&window.lucide.createIcons()}normalizeInsight(e){const a={negative_balance:{title:"Seu saldo ficou negativo",impact:"Aja agora",action:{url:"lancamentos?tipo=despesa"}},low_activity:{title:"Registre mais movimentações",impact:"Mais controle",action:{url:"lancamentos"}},low_categories:{title:"Use mais categorias",impact:"Mais clareza",action:{url:"categorias"}},no_goals:{title:"Defina uma meta financeira",impact:"Mais direcao",action:{url:"financas#metas"}}}[e.type]||{title:"Insight do mes",impact:"Ver detalhe",action:{url:"dashboard"}};return{priority:e.priority||"medium",type:e.type||"generic",title:e.title||a.title,message:e.message||"",impact:e.impact||a.impact,action:e.action||a.action}}renderEmpty(){this.container.innerHTML=""}getIconForType(e){return{savings_rate:"piggy-bank",consistency:"calendar-check",diversification:"layers",negative_balance:"alert-triangle",low_balance:"wallet",no_income:"alert-circle",no_goals:"target"}[e]||"lightbulb"}}window.HealthScoreInsights=je;class Ke{constructor(e="aiTipContainer"){this.container=document.getElementById(e),this.baseURL=X()}init(){this.container&&(this._initialized||(this._initialized=!0,this.render(),this.load(),document.addEventListener("lukrato:data-changed",()=>{P(),this.load({force:!0})}),document.addEventListener("lukrato:month-changed",()=>{P(),this.load({force:!0})})))}render(){this.container.innerHTML=`
      <div class="ai-tip-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400" data-aos-delay="100">
        <div class="ai-tip-header">
          <i data-lucide="sparkles" class="ai-tip-header-icon"></i>
          <h2 class="ai-tip-title">Prioridades do mês</h2>
          <span class="ai-tip-badge" id="aiTipBadge" style="display:none;"></span>
        </div>
        <div class="ai-tip-list" id="aiTipList">
          ${'<div class="ai-tip-skeleton"></div>'.repeat(4)}
        </div>
      </div>
    `,this.updateIcons()}async load({force:e=!1}={}){try{const t=await K(void 0,{force:e}),a=t?.data??t,i=this.buildTips(a);this.renderTips(i)}catch(t){N("Error loading AI tips",t,"Falha ao carregar dicas"),this.renderEmpty()}}buildTips(e){const t=[],a=e?.health_score||{},i=e?.metrics||{},n=e?.provisao?.provisao||{},o=e?.provisao?.vencidos||{},s=e?.provisao?.parcelas||{},c=e?.chart||[],d=Array.isArray(e?.health_score_insights)?e.health_score_insights:e?.health_score_insights?.insights||[],m={critical:0,high:1,medium:2,low:3};if(d.sort((v,S)=>(m[v.priority]??9)-(m[S.priority]??9)).forEach(v=>{const S=this.normalizeInsight(v);t.push({type:S.type,priority:S.priority,icon:S.icon,title:v.title||S.title,desc:v.message||S.message,url:S.url,metric:v.metric||null,metricLabel:v.metric_label||null})}),o.count>0){const v=(o.total||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"overdue",priority:"critical",icon:"clock",title:`${o.count} conta(s) em atraso`,desc:"Regularize para evitar juros e manter o score saudável.",url:"lancamentos?status=vencido",metric:v,metricLabel:"em atraso"})}const l=e?.provisao?.proximos||[];if(l.length>0){const v=l[0],S=v.data_pagamento?new Date(v.data_pagamento+"T00:00:00"):null,T=new Date;if(T.setHours(0,0,0,0),S){const $=Math.ceil((S-T)/864e5);if($>=0&&$<=3){const O=(v.valor||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"upcoming",priority:"high",icon:"calendar",title:$===0?"Vence hoje!":`Vence em ${$} dia(s)`,desc:v.titulo||"Conta próxima do vencimento",url:"lancamentos",metric:O,metricLabel:$===0?"hoje":`${$}d`})}}}if(e?.greeting_insight){const v=e.greeting_insight;t.push({type:"greeting",priority:"positive",icon:v.icon||"trending-up",title:v.message||"Evolução do mês",desc:"",url:null,metric:null,metricLabel:null})}const u=a.savingsRate??0;(i.receitas??0)>0&&u>=20&&t.push({type:"savings",priority:"positive",icon:"piggy-bank",title:"Ótima taxa de economia!",desc:"Você está guardando acima dos 20% recomendados.",url:null,metric:u+"%",metricLabel:"guardado"});const g=a.orcamentos??0,h=a.orcamentos_ok??0;if(g>0){const v=g-h;v>0?t.push({type:"budget",priority:"high",icon:"alert-circle",title:`${v} orçamento(s) estourado(s)`,desc:"Revise seus gastos para voltar ao controle.",url:"financas",metric:`${h}/${g}`,metricLabel:"no limite"}):t.push({type:"budget",priority:"positive",icon:"check-circle",title:"Orçamentos sob controle!",desc:`Todas as ${g} categoria(s) dentro do limite.`,url:"financas",metric:`${g}/${g}`,metricLabel:"ok"})}const p=a.metas_ativas??0,C=a.metas_concluidas??0;if(C>0?t.push({type:"goals",priority:"positive",icon:"trophy",title:`${C} meta(s) alcançada(s)!`,desc:p>0?`Continue! ${p} ainda em progresso.`:"Parabéns pelo progresso!",url:"financas#metas",metric:String(C),metricLabel:"concluída(s)"}):p>0&&t.push({type:"goals",priority:"low",icon:"target",title:`${p} meta(s) em progresso`,desc:"Cada passo conta. Mantenha o foco!",url:"financas#metas",metric:String(p),metricLabel:"ativa(s)"}),s.ativas>0){const v=(s.total_mensal||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"});t.push({type:"installments",priority:"info",icon:"layers",title:`${s.ativas} parcelamento(s) ativo(s)`,desc:`${v}/mês comprometidos com parcelas.`,url:"lancamentos",metric:v,metricLabel:"/mês"})}const y=n.saldo_projetado??0,D=n.saldo_atual??0;if(D>0&&y<0?t.push({type:"projection",priority:"critical",icon:"trending-down",title:"Atenção: saldo projetado negativo",desc:"Até o fim do mês, seu saldo pode ficar negativo. Reduza gastos.",url:null,metric:y.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}):y>D&&D>0&&t.push({type:"projection",priority:"positive",icon:"trending-up",title:"Projeção positiva!",desc:"Você deve fechar o mês com saldo maior.",url:null,metric:y.toLocaleString("pt-BR",{style:"currency",currency:"BRL"}),metricLabel:"projetado"}),c.length>=3){const v=c.slice(-3),S=v.every($=>$.resultado>0),T=v.every($=>$.resultado<0);S?t.push({type:"trend",priority:"positive",icon:"flame",title:"Sequência de 3 meses positivos!",desc:"Ótima consistência. Mantenha o ritmo!",url:"relatorios",metric:"3",metricLabel:"meses"}):T&&t.push({type:"trend",priority:"high",icon:"alert-triangle",title:"3 meses no vermelho",desc:"É hora de repensar seus gastos.",url:"relatorios",metric:"3",metricLabel:"meses"})}const M=new Set,x=t.filter(v=>M.has(v.type)?!1:(M.add(v.type),!0)),A={critical:0,high:1,medium:2,low:3,positive:4,info:5};return x.sort((v,S)=>(A[v.priority]??9)-(A[S.priority]??9)),x.slice(0,5)}normalizeInsight(e){const a={negative_balance:{title:"Saldo no vermelho",icon:"alert-triangle",url:"lancamentos?tipo=despesa"},overspending:{title:"Gastos acima da receita",icon:"trending-down",url:"lancamentos?tipo=despesa"},low_savings:{title:"Economia muito baixa",icon:"piggy-bank",url:"relatorios"},moderate_savings:{title:"Aumente sua economia",icon:"piggy-bank",url:"relatorios"},low_activity:{title:"Registre suas movimentações",icon:"edit-3",url:"lancamentos"},low_categories:{title:"Organize por categorias",icon:"layers",url:"categorias"},no_goals:{title:"Crie sua primeira meta",icon:"target",url:"financas#metas"},no_budgets:{title:"Defina limites de gastos",icon:"shield",url:"financas"}}[e.type]||{title:"Dica do mês",icon:"lightbulb",url:"dashboard"};return{type:e.type||"generic",priority:e.priority||"medium",title:e.title||a.title,message:e.message||"",icon:a.icon,url:a.url}}renderTips(e){const t=document.getElementById("aiTipList");if(!t)return;if(e.length===0){this.renderEmpty();return}const a=document.getElementById("aiTipBadge"),i=e.some(o=>o.priority==="critical"||o.priority==="high");if(a)if(i)a.textContent=`${e.filter(o=>o.priority==="critical"||o.priority==="high").length} atenção`,a.style.display="",a.style.background="rgba(239, 68, 68, 0.12)",a.style.color="#ef4444";else{const o=e.filter(s=>s.priority==="positive").length;o>0?(a.textContent=`${o} positivo(s)`,a.style.display="",a.style.background="rgba(16, 185, 129, 0.12)",a.style.color="#10b981"):a.style.display="none"}const n=e.map((o,s)=>{const c=this.getIconClass(o.priority),d=o.url?"a":"div",m=o.url?` href="${this.baseURL}${o.url}"`:"",l=`ai-tip-accent--${o.priority||"info"}`,u=o.metric?`<div class="ai-tip-metric">
            <span class="ai-tip-metric-value">${o.metric}</span>
            ${o.metricLabel?`<span class="ai-tip-metric-label">${o.metricLabel}</span>`:""}
          </div>`:"";return`
        <${d}${m} class="ai-tip-item surface-card" data-priority="${o.priority}" style="animation-delay: ${s*70}ms;">
          <div class="ai-tip-accent ${l}"></div>
          <div class="ai-tip-content">
            <div class="ai-tip-item-icon ${c}">
              <i data-lucide="${o.icon}" style="width:16px;height:16px;"></i>
            </div>
            <div class="ai-tip-item-body">
              <span class="ai-tip-item-title">${o.title}</span>
              ${o.desc?`<span class="ai-tip-item-desc">${o.desc}</span>`:""}
            </div>
            ${o.url?'<i data-lucide="chevron-right" style="width:14px;height:14px;" class="ai-tip-item-arrow"></i>':""}
          </div>
          ${u}
        </${d}>
      `}).join("");t.innerHTML=n,this.updateIcons()}renderEmpty(){const e=document.getElementById("aiTipList");if(!e)return;e.innerHTML=`
      <div class="ai-tip-empty">
        <i data-lucide="check-circle" class="ai-tip-empty-icon"></i>
        <p>Tudo certo por aqui! Suas finanças estão no caminho certo.</p>
      </div>
    `;const t=document.getElementById("aiTipBadge");t&&(t.textContent="Tudo ok",t.style.display="",t.style.background="rgba(16, 185, 129, 0.12)",t.style.color="#10b981"),this.updateIcons()}getIconClass(e){return{critical:"ai-tip-item-icon--critical",high:"ai-tip-item-icon--high",medium:"ai-tip-item-icon--medium",low:"ai-tip-item-icon--low",positive:"ai-tip-item-icon--positive"}[e]||"ai-tip-item-icon--info"}updateIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.AiTipCard=Ke;class Ge{constructor(e="financeOverviewContainer"){this.container=document.getElementById(e),this.baseURL=X()}render(){this.container&&(this.container.innerHTML=`
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
    `)}async load(){try{const{mes:e,ano:t}=this.getSelectedPeriod(),a=await ne(be(),{mes:e,ano:t});a.success&&a.data?(this.renderAlerts(a.data),this.renderMetas(a.data.metas),this.renderOrcamento(a.data.orcamento)):(this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty())}catch(e){console.error("Error loading finance overview:",e),this.renderAlerts(),this.renderMetasEmpty(),this.renderOrcamentoEmpty()}this._listening||(this._listening=!0,document.addEventListener("lukrato:data-changed",()=>this.load()),document.addEventListener("lukrato:month-changed",()=>this.load()))}renderAlerts(e=null){const t=document.getElementById("dashboardAlertsBudget");if(!t)return;const a=Array.isArray(e?.orcamento?.orcamentos)?e.orcamento.orcamentos.slice():[],i=a.filter(s=>s.status==="estourado").sort((s,c)=>Number(c.excedido||0)-Number(s.excedido||0)),n=a.filter(s=>s.status==="alerta").sort((s,c)=>Number(c.percentual||0)-Number(s.percentual||0)),o=[];if(i.slice(0,2).forEach(s=>{o.push({variant:"danger",title:`Você já passou do limite em ${s.categoria_nome}`,message:`Excedido em ${this.money(s.excedido||0)}.`})}),o.length<2&&n.slice(0,2-o.length).forEach(s=>{o.push({variant:"warning",title:`${s.categoria_nome} já consumiu ${Math.round(s.percentual||0)}% do limite`,message:`Restam ${this.money(s.disponivel||0)} nessa categoria.`})}),o.length===0){t.innerHTML="",this.toggleAlertsSection();return}t.innerHTML=o.map(s=>`
      <a href="${this.baseURL}financas#orcamentos" class="dashboard-alert dashboard-alert--${s.variant}">
        <div class="dashboard-alert-icon">
          <i data-lucide="${s.variant==="danger"?"triangle-alert":"circle-alert"}" style="width:18px;height:18px;"></i>
        </div>
        <div class="dashboard-alert-content">
          <strong>${s.title}</strong>
          <span>${s.message}</span>
        </div>
        <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
      </a>
    `).join(""),this.toggleAlertsSection(),this.refreshIcons()}renderOrcamento(e){const t=document.getElementById("foOrcamento");if(!t)return;if(!e||e.total_categorias===0){this.renderOrcamentoEmpty();return}const a=Math.round(e.percentual_geral||0),i=this.getBarColor(a),o=(e.orcamentos||[]).slice().sort((c,d)=>Number(d.percentual||0)-Number(c.percentual||0)).slice(0,3).map(c=>{const d=Math.min(Number(c.percentual||0),100),m=this.getBarColor(c.percentual);return`
        <div class="fo-orc-item">
          <div class="fo-orc-item-header">
            <span class="fo-orc-item-name">${c.categoria_nome}</span>
            <span class="fo-orc-item-pct" style="color:${m};">${Math.round(c.percentual||0)}%</span>
          </div>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${d}%; background:${m};"></div>
          </div>
        </div>
      `}).join("");let s="No controle";(e.estourados||0)>0?s=`${e.estourados} acima do limite`:(e.em_alerta||0)>0&&(s=`${e.em_alerta} em atencao`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#orcamentos" class="fo-card-title">
          <i data-lucide="wallet" style="width:16px;height:16px;"></i>
          Limites do mes
        </a>
        <span class="fo-badge" style="color:${i}; background:${i}18;">${s}</span>
      </div>

      <div class="fo-orc-summary">
        <span>${this.money(e.total_gasto||0)} usados de ${this.money(e.total_limite||0)}</span>
        <span class="fo-summary-status">Saude: ${e.saude_financeira?.label||"Boa"}</span>
      </div>

      <div class="fo-bar-track fo-bar-track--main">
        <div class="fo-bar-fill" style="width:${Math.min(a,100)}%; background:${i};"></div>
      </div>

      ${o?`<div class="fo-orc-list">${o}</div>`:""}

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
      `,this.refreshIcons();return}const n=a.cor||"var(--color-primary)",o=this.normalizeIconName(a.icone),s=Math.round(a.progresso||0),c=Math.max(Number(a.valor_alvo||0)-Number(a.valor_atual||0),0);this.updateGoalsHeadline(`Faltam ${this.money(c)} para alcancar sua meta.`),t.innerHTML=`
      <div class="fo-card-header">
        <a href="${this.baseURL}financas#metas" class="fo-card-title">
          <i data-lucide="target" style="width:16px;height:16px;"></i>
          Metas
        </a>
        <span class="fo-badge">${e.total_metas} ativa${e.total_metas!==1?"s":""}</span>
      </div>

      <div class="fo-meta-destaque">
        <div class="fo-meta-icon" style="color:${n}; background:${n}18;">
          <i data-lucide="${o}" style="width:16px;height:16px;"></i>
        </div>
        <div class="fo-meta-info">
          <span class="fo-meta-titulo">${a.titulo}</span>
          <div class="fo-bar-track">
            <div class="fo-bar-fill" style="width:${Math.min(s,100)}%; background:${n};"></div>
          </div>
          <span class="fo-meta-detail">${this.money(a.valor_atual||0)} de ${this.money(a.valor_alvo||0)}</span>
        </div>
        <span class="fo-meta-pct" style="color:${n};">${s}%</span>
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
    `,this.refreshIcons())}updateGoalsHeadline(e){const t=document.getElementById("foGoalsHeadline");t&&(t.textContent=e)}toggleAlertsSection(){const e=document.getElementById("dashboardAlertsSection"),t=document.getElementById("dashboardAlertsOverview"),a=document.getElementById("dashboardAlertsBudget");if(!e)return;const i=t&&t.innerHTML.trim()!=="",n=a&&a.innerHTML.trim()!=="";e.style.display=i||n?"block":"none"}getSelectedPeriod(){const e=b.getCurrentMonth?b.getCurrentMonth():new Date().toISOString().slice(0,7),t=String(e).match(/^(\d{4})-(\d{2})$/);if(t)return{ano:Number(t[1]),mes:Number(t[2])};const a=new Date;return{mes:a.getMonth()+1,ano:a.getFullYear()}}getBarColor(e){return e>=100?"#ef4444":e>=80?"#f59e0b":"#10b981"}normalizeIconName(e){const t=String(e||"").trim();return t&&({"fa-bullseye":"target","fa-target":"target","fa-wallet":"wallet","fa-university":"landmark","fa-plane":"plane","fa-car":"car","fa-home":"house","fa-heart":"heart","fa-briefcase":"briefcase-business","fa-piggy-bank":"piggy-bank","fa-shield":"shield","fa-graduation-cap":"graduation-cap","fa-store":"store","fa-baby":"baby","fa-hand-holding-usd":"hand-coins"}[t]||t.replace(/^fa-/,""))||"target"}money(e){return Number(e||0).toLocaleString("pt-BR",{style:"currency",currency:"BRL"})}refreshIcons(){typeof window.lucide<"u"&&window.lucide.createIcons()}}window.FinanceOverview=Ge;class We{constructor(e="evolucaoChartsContainer"){this.container=document.getElementById(e),this._chartMensal=null,this._chartAnual=null,this._activeTab="mensal",this._currentMonth=null}init(){!this.container||this._initialized||(this._initialized=!0,this._render(),this._loadAndDraw(),document.addEventListener("lukrato:month-changed",e=>{this._currentMonth=e?.detail?.month??null,this._loadAndDraw()}),document.addEventListener("lukrato:data-changed",()=>{this._loadAndDraw()}))}_render(){this.container.innerHTML=`
      <div class="evo-card surface-card surface-card--interactive" data-aos="fade-up" data-aos-duration="400">
        <div class="evo-header">
          <div class="evo-title-group">
            <i data-lucide="trending-up" class="evo-title-icon"></i>
                        <div class="evo-title-stack">
                            <h2 class="evo-title">Fluxo do período</h2>
                            <p class="evo-subtitle">Entradas, saídas e resultado em contexto.</p>
                        </div>
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
    `,this.container.querySelectorAll(".evo-tab").forEach(e=>{e.addEventListener("click",()=>this._switchTab(e.dataset.tab))}),typeof window.lucide<"u"&&window.lucide.createIcons({attrs:{class:["lucide"]}})}async _loadAndDraw(){const e=this._currentMonth||this._detectMonth();try{const t=await ne(Oe(),{month:e}),a=t?.data??t;if(!a?.mensal)return;this._data=a,this._drawMensal(a.mensal),this._drawAnual(a.anual),this._updateStats(a)}catch{}}_detectMonth(){const e=document.getElementById("monthSelector")||document.querySelector("[data-month]");return e?.value||e?.dataset?.month||new Date().toISOString().slice(0,7)}_theme(){const e=document.documentElement.getAttribute("data-theme")!=="light",t=getComputedStyle(document.documentElement);return{isDark:e,mode:e?"dark":"light",textMuted:t.getPropertyValue("--color-text-muted").trim()||(e?"#94a3b8":"#666"),gridColor:e?"rgba(255,255,255,0.05)":"rgba(0,0,0,0.06)",primary:t.getPropertyValue("--color-primary").trim()||"#E67E22",success:t.getPropertyValue("--color-success").trim()||"#2ecc71",danger:t.getPropertyValue("--color-danger").trim()||"#e74c3c",surface:e?"#0f172a":"#ffffff"}}_fmt(e){return new Intl.NumberFormat("pt-BR",{style:"currency",currency:"BRL"}).format(e??0)}_chartHeight(){return window.matchMedia("(max-width: 768px)").matches?176:188}_drawMensal(e){const t=document.getElementById("evoChartMensal");if(!t||!Array.isArray(e))return;this._chartMensal&&(this._chartMensal.destroy(),this._chartMensal=null);const a=this._theme(),i=e.map(s=>s.label),n=e.map(s=>+s.receitas),o=e.map(s=>+s.despesas);this._chartMensal=new ApexCharts(t,{chart:{type:"bar",height:this._chartHeight(),toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,sparkline:{enabled:!1},animations:{enabled:!0,speed:600}},series:[{name:"Entradas",data:n},{name:"Saídas",data:o}],xaxis:{categories:i,tickAmount:7,labels:{rotate:0,style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:s=>this._fmt(s)}},colors:[a.success,a.danger],plotOptions:{bar:{borderRadius:4,columnWidth:"70%",dataLabels:{position:"top"}}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:s=>this._fmt(s)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartMensal.render()}_drawAnual(e){const t=document.getElementById("evoChartAnual");if(!t||!Array.isArray(e))return;this._chartAnual&&(this._chartAnual.destroy(),this._chartAnual=null);const a=this._theme(),i=e.map(c=>c.label),n=e.map(c=>+c.receitas),o=e.map(c=>+c.despesas),s=e.map(c=>+c.saldo);this._chartAnual=new ApexCharts(t,{chart:{type:"line",height:this._chartHeight(),toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,animations:{enabled:!0,speed:600}},series:[{name:"Entradas",type:"column",data:n},{name:"Saídas",type:"column",data:o},{name:"Saldo",type:"area",data:s}],xaxis:{categories:i,labels:{style:{colors:a.textMuted,fontSize:"10px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:a.textMuted,fontSize:"10px"},formatter:c=>this._fmt(c)}},colors:[a.success,a.danger,a.primary],plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},stroke:{curve:"smooth",width:[0,0,2.5]},fill:{type:["solid","solid","gradient"],gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:.02,stops:[0,100]}},markers:{size:[0,0,4],hover:{size:6}},dataLabels:{enabled:!1},grid:{borderColor:a.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:a.mode,shared:!0,intersect:!1,y:{formatter:c=>this._fmt(c)}},legend:{position:"top",horizontalAlign:"right",labels:{colors:a.textMuted},markers:{shape:"circle",size:6},fontSize:"12px"},theme:{mode:a.mode}}),this._chartAnual.render()}_updateStats(e){const t=this._activeTab==="anual";let a=0,i=0;t&&e.anual?.length?e.anual.forEach(d=>{a+=+d.receitas,i+=+d.despesas}):e.mensal?.length&&e.mensal.forEach(d=>{a+=+d.receitas,i+=+d.despesas});const n=a-i,o=document.getElementById("evoStatReceitas"),s=document.getElementById("evoStatDespesas"),c=document.getElementById("evoStatResultado");o&&(o.textContent=this._fmt(a)),s&&(s.textContent=this._fmt(i)),c&&(c.textContent=this._fmt(n),c.className="evo-stat__value "+(n>=0?"evo-stat__value--income":"evo-stat__value--expense"))}_switchTab(e){if(this._activeTab===e)return;this._activeTab=e,this.container.querySelectorAll(".evo-tab").forEach(i=>{const n=i.dataset.tab===e;i.classList.toggle("evo-tab--active",n),i.setAttribute("aria-selected",String(n))});const t=document.getElementById("evoChartMensal"),a=document.getElementById("evoChartAnual");t&&(t.style.display=e==="mensal"?"":"none"),a&&(a.style.display=e==="anual"?"":"none"),this._data&&this._updateStats(this._data),setTimeout(()=>{e==="mensal"&&this._chartMensal&&this._chartMensal.windowResizeHandler?.(),e==="anual"&&this._chartAnual&&this._chartAnual.windowResizeHandler?.()},10)}}window.EvolucaoCharts=We;class we{constructor(){this.initialized=!1,this.init()}init(){this.setupEventListeners(),this.initialized=!0}setupEventListeners(){document.addEventListener("lukrato:transaction-added",()=>{this.playAddedAnimation()}),document.addEventListener("lukrato:level-up",e=>{this.playLevelUpAnimation(e.detail?.level)}),document.addEventListener("lukrato:streak-milestone",e=>{this.playStreakAnimation(e.detail?.days)}),document.addEventListener("lukrato:goal-completed",e=>{this.playGoalAnimation(e.detail?.goalName)}),document.addEventListener("lukrato:achievement-unlocked",e=>{this.playAchievementAnimation(e.detail?.name,e.detail?.icon)})}playAddedAnimation(){window.fab&&window.fab.celebrate(),window.LK?.toast&&window.LK.toast.success("Lancamento adicionado com sucesso."),this.fireConfetti("small",.9,.9)}playLevelUpAnimation(e){this.showCelebrationToast({title:`Nivel ${e}`,subtitle:"você subiu de nivel.",icon:"star",duration:3e3}),this.fireConfetti("large",.5,.3),this.screenFlash("#f59e0b",.3,2),window.fab?.container&&(window.fab.container.style.animation="spin 0.8s ease-out",setTimeout(()=>{window.fab.container.style.animation=""},800))}playStreakAnimation(e){const a={7:{title:"Semana perfeita",subtitle:"você chegou a 7 dias seguidos."},14:{title:"Duas semanas",subtitle:"você chegou a 14 dias seguidos."},30:{title:"Mes epico",subtitle:"você chegou a 30 dias seguidos."},100:{title:"Marco historico",subtitle:"você chegou a 100 dias seguidos."}}[e]||{title:`${e} dias seguidos`,subtitle:"Sua sequencia continua forte."};this.showCelebrationModal(a.title,a.subtitle),this.fireConfetti("extreme",.5,.2)}playGoalAnimation(e){this.showCelebrationToast({title:"Meta atingida",subtitle:`você completou: ${e}`,icon:"target",duration:3500}),this.fireConfetti("large",.5,.4),this.screenFlash("#10b981",.4,1.5)}playAchievementAnimation(e,t){const a=this.normalizeIconName(t),i=document.createElement("div");i.className="achievement-popup",i.innerHTML=`
      <div class="achievement-card">
        <div class="achievement-icon">
          <i data-lucide="${a}"></i>
        </div>
        <div class="achievement-title">Conquista desbloqueada</div>
        <div class="achievement-name">${e}</div>
      </div>
    `,document.body.appendChild(i),typeof window.lucide<"u"&&window.lucide.createIcons(),setTimeout(()=>{i.classList.add("show")},10),setTimeout(()=>{i.classList.remove("show"),setTimeout(()=>i.remove(),300)},3500),this.fireConfetti("medium",.5,.6)}showCelebrationToast(e){const{title:t="Parabens",subtitle:a="você fez progresso.",icon:i="party-popper",duration:n=3e3}=e;window.LK?.toast&&window.LK.toast.success(`${t}
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
    `,document.body.appendChild(i),setTimeout(()=>{i.style.transition=`opacity ${a/2}ms ease-out`,i.style.opacity=t},10),setTimeout(()=>{i.style.transition=`opacity ${a/2}ms ease-in`,i.style.opacity="0"},a/2),setTimeout(()=>i.remove(),a)}fireConfetti(e="medium",t=.5,a=.5){if(typeof confetti!="function")return;const i={small:{particleCount:30,spread:40},medium:{particleCount:60,spread:60},large:{particleCount:100,spread:90},extreme:{particleCount:150,spread:120}},n=i[e]||i.medium;confetti({...n,origin:{x:t,y:a},gravity:.8,decay:.95,zIndex:99999})}}window.CelebrationSystem=we;document.addEventListener("DOMContentLoaded",()=>{window.celebrationSystem||(window.celebrationSystem=new we)});function Ye(){return new Promise(r=>{let e=0;const t=setInterval(()=>{window.HealthScoreWidget&&window.DashboardGreeting&&window.HealthScoreInsights&&window.FinanceOverview&&window.EvolucaoCharts&&(clearInterval(t),r()),e++>50&&(clearInterval(t),r())},100)})}function ue(r,e){const t=document.getElementById(r);return t||e()}async function Qe(){await Ye(),document.readyState==="loading"?document.addEventListener("DOMContentLoaded",me):me()}function me(){const r=document.querySelector(".modern-dashboard");if(r){if(typeof window.DashboardGreeting<"u"&&(ue("greetingContainer",()=>{const t=document.createElement("div");return t.id="greetingContainer",r.insertBefore(t,r.firstChild),t}),new window.DashboardGreeting().render()),typeof window.HealthScoreWidget<"u"){if(document.getElementById("healthScoreContainer")){const t=new window.HealthScoreWidget;t.render(),t.load()}typeof window.HealthScoreInsights<"u"&&document.getElementById("healthScoreInsights")&&(window.healthScoreInsights=new window.HealthScoreInsights)}if(typeof window.AiTipCard<"u"&&document.getElementById("aiTipContainer")&&new window.AiTipCard().init(),typeof window.EvolucaoCharts<"u"&&document.getElementById("evolucaoChartsContainer")&&new window.EvolucaoCharts().init(),typeof window.FinanceOverview<"u"){ue("financeOverviewContainer",()=>{const t=document.createElement("div");t.id="financeOverviewContainer";const a=r.querySelector(".provisao-section");return a?a.insertAdjacentElement("afterend",t):r.appendChild(t),t});const e=new window.FinanceOverview;e.render(),e.load()}typeof window.lucide<"u"&&window.lucide.createIcons()}}Qe();function he(r){return`lk_user_${ie().userId??"anon"}_${r}`}const U={DISPLAY_NAME_DISMISSED:()=>he("display_name_prompt_dismissed_v1"),FIRST_ACTION_TOAST:()=>he("dashboard_first_action_toast_v1")};class Xe{constructor(){this.state={accountCount:0,primaryAction:"create_transaction",transactionCount:null,isDemo:!1,awaitingFirstActionFeedback:!1},this.elements={firstRunStack:document.getElementById("dashboardFirstRunStack"),displayNameCard:document.getElementById("dashboardDisplayNamePrompt"),previewNotice:document.getElementById("dashboardPreviewNotice"),previewLearnMore:document.getElementById("dashboardPreviewLearnMore"),displayNameForm:document.getElementById("dashboardDisplayNameForm"),displayNameInput:document.getElementById("dashboardDisplayNameInput"),displayNameSubmit:document.getElementById("dashboardDisplayNameSubmit"),displayNameDismiss:document.getElementById("dashboardDisplayNameDismiss"),displayNameFeedback:document.getElementById("dashboardDisplayNameFeedback"),quickStart:document.getElementById("dashboardQuickStart"),quickStartEyebrow:document.getElementById("dashboardQuickStartEyebrow"),quickStartTitle:document.getElementById("dashboardQuickStartTitle"),primaryActionCta:document.getElementById("dashboardFirstTransactionCta"),openTourPrompt:document.getElementById("dashboardOpenTourPrompt"),emptyStateTitle:document.querySelector("#emptyState p"),emptyStateDescription:document.querySelector("#emptyState .dash-empty__subtext"),emptyStateCta:document.getElementById("dashboardEmptyStateCta"),fabButton:document.getElementById("fabButton")}}init(){this.bindEvents(),this.syncDisplayNamePrompt(),this.syncStackVisibility(),ye(()=>{this.syncDisplayNamePrompt()}),ve({},{silent:!0}).then(()=>{this.syncDisplayNamePrompt()})}bindEvents(){this.elements.primaryActionCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.emptyStateCta?.addEventListener("click",()=>this.openPrimaryAction()),this.elements.openTourPrompt?.addEventListener("click",()=>this.startTour()),this.elements.previewLearnMore?.addEventListener("click",()=>{this.openPreviewHelp()}),this.elements.displayNameDismiss?.addEventListener("click",()=>this.dismissDisplayNamePrompt()),this.elements.displayNameForm?.addEventListener("submit",e=>this.handleDisplayNameSubmit(e)),document.addEventListener("lukrato:dashboard-overview-rendered",e=>{this.handleOverviewUpdate(e.detail||{})}),document.addEventListener("lukrato:data-changed",e=>{e.detail?.resource==="transactions"&&e.detail?.action==="create"&&(this.state.awaitingFirstActionFeedback=!0)})}handleOverviewUpdate(e){const t=Number(this.state.transactionCount??0),a=Number(e.transactionCount||0),i=this.state.transactionCount===null,n=ge(e,{accountCount:Number(e.accountCount??0),actionType:e.primaryAction,ctaLabel:e.ctaLabel,ctaUrl:e.ctaUrl});this.state.accountCount=Math.max(0,Number(e.accountCount??n.action.accountCount??0)||0),this.state.primaryAction=n.action.actionType,this.state.transactionCount=a,this.state.isDemo=e.isDemo===!0,this.toggleQuickStart(a===0),this.syncPrimaryActionCopy(n),this.syncDisplayNamePrompt(),this.togglePrimaryActionFocus(a===0),!i&&t===0&&a>0?this.handleFirstActionCompleted():this.state.awaitingFirstActionFeedback&&a>0&&this.handleFirstActionCompleted()}toggleQuickStart(e){this.elements.quickStart&&(this.elements.quickStart.hidden=!e,e&&this.suppressHelpCenterOffer(),this.syncStackVisibility())}syncPrimaryActionCopy(e){if(!e)return;const t=this.buildQuickStartContent(e);this.elements.quickStartEyebrow&&(this.elements.quickStartEyebrow.textContent=t.eyebrow),this.elements.quickStartTitle&&(this.elements.quickStartTitle.textContent=t.title),this.elements.primaryActionCta&&(this.elements.primaryActionCta.innerHTML=`<i data-lucide="plus"></i> ${this.getPrimaryCtaLabel(e)}`),this.elements.emptyStateTitle&&(this.elements.emptyStateTitle.textContent=e.emptyStateTitle),this.elements.emptyStateDescription&&(this.elements.emptyStateDescription.textContent=e.emptyStateDescription),this.elements.emptyStateCta&&(this.elements.emptyStateCta.innerHTML=`<i data-lucide="plus"></i> ${e.emptyStateButton}`),this.elements.openTourPrompt&&(this.elements.openTourPrompt.hidden=!this.hasTourAction(e)),this.elements.previewLearnMore&&(this.elements.previewLearnMore.hidden=!this.hasPreviewHelp()),typeof window.lucide<"u"&&window.lucide.createIcons()}getPrimaryCtaLabel(e){return e?.action?.actionType==="create_account"?"Criar primeira conta":e?.action?.actionType==="create_transaction"?"Registrar primeira transação":String(e?.quickStartButton||"Continuar").trim()}buildQuickStartContent(e){return e?.action?.actionType==="create_transaction"?{eyebrow:"Passo 2",title:"Adicione sua primeira transação"}:{eyebrow:"Passo 1",title:"Crie sua primeira conta"}}hasTourAction(e=null){return!(!!!(window.LKHelpCenter?.startCurrentPageTutorial||window.LKHelpCenter?.showCurrentPageTips)||e&&e.shouldOfferTour===!1&&!this.state.isDemo)}hasPreviewHelp(){return!0}syncDisplayNamePrompt(){if(!this.elements.displayNameCard)return;const e=this.state.isDemo,t=!!ie().needsDisplayNamePrompt&&localStorage.getItem(U.DISPLAY_NAME_DISMISSED())!=="1";this.elements.previewNotice&&(this.elements.previewNotice.hidden=!e),this.elements.previewLearnMore&&(this.elements.previewLearnMore.hidden=!e),this.elements.displayNameForm&&(this.elements.displayNameForm.hidden=!t);const a=e||t;this.elements.displayNameCard.hidden=!a,this.elements.displayNameCard.classList.toggle("is-preview-only",e&&!t),this.elements.displayNameCard.classList.toggle("is-name-only",t&&!e),this.elements.displayNameCard.classList.toggle("is-dual",e&&t),this.syncStackVisibility()}syncStackVisibility(){if(!this.elements.firstRunStack)return;const e=this.elements.quickStart&&!this.elements.quickStart.hidden,t=this.elements.displayNameCard&&!this.elements.displayNameCard.hidden;this.elements.firstRunStack.hidden=!(e||t)}dismissDisplayNamePrompt(){localStorage.setItem(U.DISPLAY_NAME_DISMISSED(),"1"),this.syncDisplayNamePrompt()}async handleDisplayNameSubmit(e){if(e.preventDefault(),!this.elements.displayNameInput||!this.elements.displayNameSubmit)return;const t=this.elements.displayNameInput.value.trim();if(t.length<2){this.showDisplayNameFeedback("Use pelo menos 2 caracteres.",!0);return}this.elements.displayNameSubmit.disabled=!0,this.elements.displayNameSubmit.textContent="Salvando...";try{const a=await pe(Me(),{display_name:t});if(a?.success===!1)throw a;const i=a?.data||{},n=String(i.display_name||t).trim(),o=String(i.first_name||n).trim();Te({username:n,needsDisplayNamePrompt:!1},{source:"display-name"}),localStorage.removeItem(U.DISPLAY_NAME_DISMISSED()),this.updateGlobalIdentity(n,o),this.showDisplayNameFeedback("Perfeito. Agora o Lukrato já fala com você do jeito certo."),window.setTimeout(()=>this.syncDisplayNamePrompt(),900),window.LK?.toast&&window.LK.toast.success("Nome de exibição salvo.")}catch(a){N("Erro ao salvar nome de exibição",a,"Falha ao salvar nome de exibição"),this.showDisplayNameFeedback(oe(a,"Não foi possível salvar agora."),!0)}finally{this.elements.displayNameSubmit.disabled=!1,this.elements.displayNameSubmit.textContent="Salvar"}}showDisplayNameFeedback(e,t=!1){this.elements.displayNameFeedback&&(this.elements.displayNameFeedback.hidden=!1,this.elements.displayNameFeedback.textContent=e,this.elements.displayNameFeedback.classList.toggle("is-error",t))}updateGlobalIdentity(e,t){const a=t||e||"U",i=a.charAt(0).toUpperCase();document.querySelectorAll(".greeting-name strong").forEach(s=>{s.textContent=a}),document.querySelectorAll(".avatar-initials-sm, .avatar-initials-xs").forEach(s=>{s.textContent=i});const n=document.getElementById("lkSupportToggle");n&&(n.dataset.supportName=e);const o=document.getElementById("sfName");o&&(o.textContent=e),this.elements.displayNameInput&&(this.elements.displayNameInput.value=e)}async openPreviewHelp(){if(window.Swal?.fire){const e=this.elements.primaryActionCta?.textContent?.trim()||"Continuar",t=this.hasTourAction(),a=await window.Swal.fire({title:"O que é esta prévia?",html:`
          <div class="dash-preview-modal__content">
            <p class="dash-preview-modal__intro">
              Estes números servem só para mostrar como o Lukrato organiza suas finanças antes do primeiro uso real.
            </p>
            <ul class="dash-preview-modal__list">
              <li>Os valores exibidos aqui são apenas de exemplo.</li>
              <li>Nada dessa prévia entra no seu histórico real.</li>
              <li>Assim que você criar sua primeira conta e começar a usar, a demonstração some.</li>
            </ul>
            <p class="dash-preview-modal__footnote">
              O próximo passo é só um: começar seu painel com dados seus.
            </p>
          </div>
        `,showConfirmButton:!0,confirmButtonText:e,showDenyButton:t,denyButtonText:"Ver tour",showCancelButton:!0,cancelButtonText:"Fechar",reverseButtons:!1,focusConfirm:!0,customClass:{popup:"lk-swal-popup dash-preview-modal",confirmButton:"dash-preview-modal__confirm",denyButton:"dash-preview-modal__deny",cancelButton:"dash-preview-modal__cancel"}});if(a.isConfirmed){this.openPrimaryAction();return}a.isDenied&&this.startTour();return}window.alert("Estes números são apenas de exemplo. Assim que você começar a usar, a prévia some.")}startTour(){if(window.LKHelpCenter?.startCurrentPageTutorial){this.suppressHelpCenterOffer(),window.LKHelpCenter.startCurrentPageTutorial({source:"dashboard-first-run"});return}if(window.LKHelpCenter?.showCurrentPageTips){window.LKHelpCenter.showCurrentPageTips();return}window.LK?.toast?.info("Tutorial indisponível no momento.")}suppressHelpCenterOffer(){const e=window.LKHelpCenter;if(!e?.getPageTutorialTarget)return;const t=e.getPageTutorialTarget();t&&(e.markOfferShownThisSession?.(t),e.hideOffer?.())}togglePrimaryActionFocus(e){[this.elements.fabButton,this.elements.primaryActionCta,document.getElementById("dashboardEmptyStateCta"),document.getElementById("dashboardChartEmptyCta")].forEach(a=>{a&&a.classList.toggle("dash-primary-cta-highlight",e)})}focusPrimaryAction(){this.togglePrimaryActionFocus(!0),this.state.transactionCount===0&&this.elements.quickStart?.scrollIntoView({behavior:"smooth",block:"center"})}handleFirstActionCompleted(){this.state.awaitingFirstActionFeedback=!1,localStorage.getItem(U.FIRST_ACTION_TOAST())!=="1"&&(window.LK?.toast&&window.LK.toast.success("Boa! Você já começou a controlar suas finanças."),localStorage.setItem(U.FIRST_ACTION_TOAST(),"1")),this.togglePrimaryActionFocus(!1)}openPrimaryAction(){fe({primary_action:this.state.primaryAction,real_account_count:this.state.accountCount})}}document.addEventListener("DOMContentLoaded",()=>{document.querySelector(".modern-dashboard")&&(window.dashboardFirstRunExperience||(window.dashboardFirstRunExperience=new Xe,window.dashboardFirstRunExperience.init()))});function Je({API:r,CONFIG:e,Utils:t,escapeHtml:a,logClientError:i}){const n={getContainer:(o,s)=>{const c=document.getElementById(s);if(c)return c;const d=document.getElementById(o);if(!d)return null;const m=d.querySelector(".dash-optional-body");if(m)return m.id||(m.id=s),m;const l=document.createElement("div");l.className="dash-optional-body",l.id=s;const u=d.querySelector(".dash-section-header"),f=Array.from(d.children).filter(g=>g.classList?.contains("dash-placeholder"));return u?.nextSibling?d.insertBefore(l,u.nextSibling):d.appendChild(l),f.forEach(g=>l.appendChild(g)),l},renderLoading:o=>{o&&(o.innerHTML=`
                <div class="dash-widget dash-widget--loading" aria-hidden="true">
                    <div class="dash-widget-skeleton dash-widget-skeleton--title"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--value"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--text"></div>
                    <div class="dash-widget-skeleton dash-widget-skeleton--bar"></div>
                </div>
            `)},renderEmpty:(o,s,c,d)=>{o&&(o.innerHTML=`
                <div class="dash-widget-empty">
                    <p>${s}</p>
                    ${c&&d?`<a href="${c}" class="dash-widget-link">${d}</a>`:""}
                </div>
            `)},getUsageColor:o=>o>=85?"#ef4444":o>=60?"#f59e0b":"#10b981",getAccountBalance:o=>{const c=[o?.saldoAtual,o?.saldo_atual,o?.saldo,o?.saldoInicial,o?.saldo_inicial].find(d=>Number.isFinite(Number(d)));return Number(c||0)},renderMetas:async o=>{const s=n.getContainer("sectionMetas","sectionMetasBody");if(s){n.renderLoading(s);try{const d=(await r.getFinanceSummary(o))?.metas??null;if(!d||Number(d.total_metas||0)===0){n.renderEmpty(s,"Você ainda não tem metas ativas neste momento.",`${e.BASE_URL}financas#metas`,"Criar meta");return}const m=d.proxima_concluir||null,l=Math.round(Number(d.progresso_geral||0));if(!m){s.innerHTML=`
                        <div class="dash-widget">
                            <span class="dash-widget-label">Metas ativas</span>
                            <strong class="dash-widget-value">${Number(d.total_metas||0)}</strong>
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
                    `;return}const u=a(String(m.titulo||"Sua meta principal")),f=Number(m.valor_atual||0),g=Number(m.valor_alvo||0),h=Math.max(g-f,0),p=Math.round(Number(m.progresso||0)),C=m.cor||"var(--color-primary)";s.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Próxima meta</span>
                        <strong class="dash-widget-value">${u}</strong>
                        <p class="dash-widget-caption">Faltam ${t.money(h)} para concluir.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(p,100)}%; background:${C};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>${t.money(f)} de ${t.money(g)}</span>
                            <strong style="color:${C};">${p}%</strong>
                        </div>
                        <a href="${e.BASE_URL}financas#metas" class="dash-widget-link">Criar metas</a>
                    </div>
                `}catch(c){i("Erro ao carregar widget de metas",c,"Falha ao carregar metas"),n.renderEmpty(s,"Não foi possível carregar suas metas agora.",`${e.BASE_URL}financas#metas`,"Tentar nas finanças")}}},renderCartoes:async()=>{const o=n.getContainer("sectionCartoes","sectionCartoesBody");if(o){n.renderLoading(o);try{const s=await r.getCardsSummary(),c=Number(s?.total_cartoes||0);if(!s||c===0){n.renderEmpty(o,"Você ainda não tem cartões ativos no dashboard.",`${e.BASE_URL}cartoes`,"Cadastrar cartão");return}const d=Number(s.limite_disponivel||0),m=Number(s.limite_total||0),l=Math.round(Number(s.percentual_uso||0)),u=n.getUsageColor(l);o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Limite disponível</span>
                        <strong class="dash-widget-value">${t.money(d)}</strong>
                        <p class="dash-widget-caption">${c} cartão(ões) ativo(s) com ${l}% de uso consolidado.</p>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(l,100)}%; background:${u};"></span>
                        </div>
                        <div class="dash-widget-meta">
                            <span>Limite total ${t.money(m)}</span>
                            <strong style="color:${u};">${l}% usado</strong>
                        </div>
                        <a href="${e.BASE_URL}cartoes" class="dash-widget-link">Criar cartões</a>
                    </div>
                `}catch(s){i("Erro ao carregar widget de cartões",s,"Falha ao carregar cartões"),n.renderEmpty(o,"Não foi possível carregar seus cartões agora.",`${e.BASE_URL}cartoes`,"Criar cartões")}}},renderContas:async o=>{const s=n.getContainer("sectionContas","sectionContasBody");if(s){n.renderLoading(s);try{const c=await r.getAccountsBalances(o),d=Array.isArray(c)?c:[];if(d.length===0){n.renderEmpty(s,"Você ainda não tem contas ativas conectadas.",`${e.BASE_URL}contas`,"Adicionar conta");return}const m=d.map(h=>({...h,__saldo:n.getAccountBalance(h)})).sort((h,p)=>p.__saldo-h.__saldo),l=m.reduce((h,p)=>h+p.__saldo,0),u=m[0]||null,f=a(String(u?.nome||u?.nome_conta||u?.instituicao||u?.banco_nome||"Conta principal")),g=u?t.money(u.__saldo):t.money(0);s.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Saldo consolidado</span>
                        <strong class="dash-widget-value">${t.money(l)}</strong>
                        <p class="dash-widget-caption">${m.length} conta(s) ativa(s) no painel.</p>
                        <div class="dash-widget-list">
                            ${m.slice(0,3).map(h=>`
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
                `}catch(c){i("Erro ao carregar widget de contas",c,"Falha ao carregar contas"),n.renderEmpty(s,"Não foi possível carregar suas contas agora.",`${e.BASE_URL}contas`,"Abrir contas")}}},renderOrcamentos:async o=>{const s=n.getContainer("sectionOrcamentos","sectionOrcamentosBody");if(s){n.renderLoading(s);try{const d=(await r.getFinanceSummary(o))?.orcamento??null;if(!d||Number(d.total_categorias||0)===0){n.renderEmpty(s,"Você ainda não definiu limites para categorias.",`${e.BASE_URL}financas#orcamentos`,"Definir limite");return}const m=Math.round(Number(d.percentual_geral||0)),l=n.getUsageColor(m),f=(d.orcamentos||[]).slice().sort((g,h)=>Number(h.percentual||0)-Number(g.percentual||0)).slice(0,3).map(g=>{const h=n.getUsageColor(g.percentual);return`
                        <div class="dash-widget-list-item">
                            <span>${a(g.categoria_nome||"Categoria")}</span>
                            <strong style="color:${h};">${Math.round(g.percentual||0)}%</strong>
                        </div>
                    `}).join("");s.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Uso geral dos limites</span>
                        <strong class="dash-widget-value" style="color:${l};">${m}%</strong>
                        <div class="dash-widget-progress">
                            <span style="width:${Math.min(m,100)}%; background:${l};"></span>
                        </div>
                        <p class="dash-widget-caption">${t.money(d.total_gasto||0)} de ${t.money(d.total_limite||0)}</p>
                        ${f?`<div class="dash-widget-list">${f}</div>`:""}
                        <a href="${e.BASE_URL}financas#orcamentos" class="dash-widget-link">Ver orçamentos</a>
                    </div>
                `}catch(c){i("Erro ao carregar widget de orçamentos",c,"Falha ao carregar orçamentos"),n.renderEmpty(s,"Não foi possível carregar seus orçamentos.",`${e.BASE_URL}financas#orcamentos`,"Abrir orçamentos")}}},renderFaturas:async()=>{const o=n.getContainer("sectionFaturas","sectionFaturasBody");if(o){n.renderLoading(o);try{const s=await r.getCardsSummary(),c=Number(s?.total_cartoes||0);if(!s||c===0){n.renderEmpty(o,"Você não tem cartões com faturas abertas.",`${e.BASE_URL}faturas`,"Ver faturas");return}const d=Number(s.fatura_aberta??s.limite_utilizado??0),m=Number(s.limite_total||0),l=m>0?Math.round(d/m*100):Number(s.percentual_uso||0),u=n.getUsageColor(l);o.innerHTML=`
                    <div class="dash-widget">
                        <span class="dash-widget-label">Fatura atual</span>
                        <strong class="dash-widget-value">${t.money(d)}</strong>
                        ${m>0?`
                            <div class="dash-widget-progress">
                                <span style="width:${Math.min(l,100)}%; background:${u};"></span>
                            </div>
                            <p class="dash-widget-caption">${l}% do limite utilizado</p>
                        `:`
                            <p class="dash-widget-caption">${c} cartão(ões) ativo(s)</p>
                        `}
                        <a href="${e.BASE_URL}faturas" class="dash-widget-link">Abrir faturas</a>
                    </div>
                `}catch(s){i("Erro ao carregar widget de faturas",s,"Falha ao carregar faturas"),n.renderEmpty(o,"Não foi possível carregar suas faturas.",`${e.BASE_URL}faturas`,"Ver faturas")}}},render:async o=>{await Promise.allSettled([n.renderMetas(o),n.renderCartoes(),n.renderContas(o),n.renderOrcamentos(o),n.renderFaturas()])}};return n}function Ze({API:r,Utils:e,escapeHtml:t,logClientError:a}){const i=(s,{includeYear:c=!0}={})=>{try{const[d,m]=String(s||"").split("-").map(Number);return new Date(d,m-1,1).toLocaleDateString("pt-BR",{month:"long",...c?{year:"numeric"}:{}})}catch{return c?"este mês":"próximo mês"}},n=s=>{try{const[c,d]=String(s||"").split("-").map(Number),m=new Date(c,d,1);return`${m.getFullYear()}-${String(m.getMonth()+1).padStart(2,"0")}`}catch{return e.getCurrentMonth()}},o={isProUser:null,checkProStatus:async()=>{try{const s=await r.getOverview(e.getCurrentMonth());o.isProUser=s?.plan?.is_pro===!0}catch{o.isProUser=!1}return o.isProUser},render:async s=>{const c=document.getElementById("sectionPrevisao");if(!c)return;await o.checkProStatus();const d=document.getElementById("provisaoProOverlay"),m=o.isProUser;c.classList.remove("is-locked"),d&&(d.style.display="none");try{const l=await r.getOverview(s);o.renderData(l.provisao||null,m)}catch(l){a("Erro ao carregar provisão",l,"Falha ao carregar previsão")}},renderData:(s,c=!0)=>{if(!s)return;const d=s.provisao||{},m=e.money,l=s.month||e.getCurrentMonth(),u=i(l),f=i(l,{includeYear:!1}),g=i(n(l),{includeYear:!1}),h=document.getElementById("provisaoTitle"),p=document.getElementById("provisaoHeadline");h&&(h.textContent=`Fechamento previsto de ${u}`),p&&(p.textContent=(d.saldo_projetado||0)>=0?`Se nada mudar, você fecha ${f} com ${m(d.saldo_projetado||0)}. A leitura abaixo considera saldo atual de ${m(d.saldo_atual||0)}, o que ainda entra e o que ainda sai.`:`Se nada mudar, você fecha ${f} com ${m(d.saldo_projetado||0)}. A leitura abaixo considera saldo atual de ${m(d.saldo_atual||0)}, o que ainda entra e o que ainda sai.`);const C=document.getElementById("provisaoProximosTitle"),y=document.getElementById("provisaoVerTodos");C&&(C.innerHTML=c?'<i data-lucide="clock"></i> Próximos compromissos':'<i data-lucide="credit-card"></i> Próximas Faturas'),y&&(y.href=c?te("lancamentos"):te("faturas"));const D=document.getElementById("provisaoPagar"),M=document.getElementById("provisaoReceber"),x=document.getElementById("provisaoProjetado"),A=document.getElementById("provisaoPagarCount"),v=document.getElementById("provisaoReceberCount"),S=document.getElementById("provisaoProjetadoLabel"),T=M?.closest(".provisao-card");if(D&&(D.textContent=m(d.a_pagar||0)),c?(M&&(M.textContent=m(d.a_receber||0)),T&&(T.style.opacity="1")):(M&&(M.textContent="R$ --"),T&&(T.style.opacity="0.5")),x&&(x.textContent=m(d.saldo_projetado||0),x.style.color=(d.saldo_projetado||0)>=0?"":"var(--color-danger)"),A){const E=d.count_pagar||0,w=d.count_faturas||0;if(c){let B=`${E} pendente${E!==1?"s":""}`;w>0&&(B+=` • ${w} fatura${w!==1?"s":""}`),A.textContent=B}else A.textContent=`${w} fatura${w!==1?"s":""}`}c?v&&(v.textContent=`${d.count_receber||0} pendente${(d.count_receber||0)!==1?"s":""}`):v&&(v.textContent="Pro"),S&&(S.textContent=`entra em ${g} com ${m(d.saldo_projetado||0)}`);const $=s.vencidos||{},O=document.getElementById("provisaoAlertDespesas");if(O){const E=$.despesas||{};if(c&&(E.count||0)>0){O.style.display="flex";const w=document.getElementById("provisaoAlertDespesasCount"),B=document.getElementById("provisaoAlertDespesasTotal");w&&(w.textContent=E.count),B&&(B.textContent=m(E.total||0))}else O.style.display="none"}const J=document.getElementById("provisaoAlertReceitas");if(J){const E=$.receitas||{};if(c&&(E.count||0)>0){J.style.display="flex";const w=document.getElementById("provisaoAlertReceitasCount"),B=document.getElementById("provisaoAlertReceitasTotal");w&&(w.textContent=E.count),B&&(B.textContent=m(E.total||0))}else J.style.display="none"}const Z=document.getElementById("provisaoAlertFaturas");if(Z){const E=$.count_faturas||0;if(E>0){Z.style.display="flex";const w=document.getElementById("provisaoAlertFaturasCount"),B=document.getElementById("provisaoAlertFaturasTotal");w&&(w.textContent=E),B&&(B.textContent=m($.total_faturas||0))}else Z.style.display="none"}const V=document.getElementById("provisaoProximosList"),G=document.getElementById("provisaoEmpty");let W=s.proximos||[];if(c||(W=W.filter(E=>E.is_fatura===!0)),V)if(W.length===0){if(V.innerHTML="",G){const E=G.querySelector("span");E&&(E.textContent=c?"Nenhum vencimento pendente":"Nenhuma fatura pendente"),V.appendChild(G),G.style.display="flex"}}else{V.innerHTML="";const E=new Date().toISOString().slice(0,10);W.forEach(w=>{const B=(w.tipo||"").toLowerCase(),Y=w.is_fatura===!0,ce=(w.data_pagamento||"").split(/[T\s]/)[0],Ce=ce===E,_e=o.formatDateShort(ce);let H="";Ce&&(H+='<span class="provisao-item-badge vence-hoje">Hoje</span>'),Y?(H+='<span class="provisao-item-badge fatura"><i data-lucide="credit-card"></i> Fatura</span>',w.cartao_ultimos_digitos&&(H+=`<span>****${w.cartao_ultimos_digitos}</span>`)):(w.eh_parcelado&&w.numero_parcelas>1&&(H+=`<span class="provisao-item-badge parcela">${w.parcela_atual}/${w.numero_parcelas}</span>`),w.recorrente&&(H+='<span class="provisao-item-badge recorrente">Recorrente</span>'),w.categoria&&(H+=`<span>${t(w.categoria)}</span>`));const le=Y?"fatura":B,z=document.createElement("div");z.className="provisao-item"+(Y?" is-fatura":""),z.innerHTML=`
                                <div class="provisao-item-dot ${le}"></div>
                                <div class="provisao-item-info">
                                    <div class="provisao-item-titulo">${t(w.titulo||"Sem título")}</div>
                                    <div class="provisao-item-meta">${H}</div>
                                </div>
                                <span class="provisao-item-valor ${le}">${m(w.valor||0)}</span>
                                <span class="provisao-item-data">${_e}</span>
                            `,Y&&w.cartao_id&&(z.style.cursor="pointer",z.addEventListener("click",()=>{const Se=(w.data_pagamento||"").split(/[T\s]/)[0],[Ee,Le]=Se.split("-");window.location.href=te("faturas",{cartao_id:w.cartao_id,mes:parseInt(Le,10),ano:Ee})})),V.appendChild(z)})}const ee=document.getElementById("provisaoParcelas"),q=s.parcelas||{};if(ee)if(c&&(q.ativas||0)>0){ee.style.display="flex";const E=document.getElementById("provisaoParcelasText"),w=document.getElementById("provisaoParcelasValor");E&&(E.textContent=`${q.ativas} parcelamento${q.ativas!==1?"s":""} ativo${q.ativas!==1?"s":""}`),w&&(w.textContent=`${m(q.total_mensal||0)}/mês`)}else ee.style.display="none"},formatDateShort:s=>{if(!s)return"-";try{const c=s.match(/^(\d{4})-(\d{2})-(\d{2})$/);return c?`${c[3]}/${c[2]}`:"-"}catch{return"-"}}};return o}function et({CONFIG:r,getDashboardOverview:e,getApiPayload:t,apiGet:a,apiDelete:i,apiPost:n,getErrorMessage:o}){function s(l){window.LKDemoPreviewBanner?.hide()}const c={getOverview:async(l,u={})=>{const f=await e(l,u),g=t(f,{});return s(g?.meta),g},fetch:async l=>{const u=await a(l);if(u?.success===!1)throw new Error(o({data:u},"Erro na API"));return u?.data??u},getMetrics:async l=>(await c.getOverview(l)).metrics||{},getAccountsBalances:async l=>{const u=await c.getOverview(l);return Array.isArray(u.accounts_balances)?u.accounts_balances:[]},getTransactions:async(l,u)=>{const f=await c.getOverview(l,{limit:u});return Array.isArray(f.recent_transactions)?f.recent_transactions:[]},getChartData:async l=>{const u=await c.getOverview(l);return Array.isArray(u.chart)?u.chart:[]},getFinanceSummary:async l=>{const u=String(l||"").match(/^(\d{4})-(\d{2})$/);if(!u)return{};const f=await a(be(),{ano:Number(u[1]),mes:Number(u[2])});return t(f,{})},getCardsSummary:async()=>{const l=await a(ke());return t(l,{})},deleteTransaction:async l=>{const u=[{request:()=>i(Ne(l))},{request:()=>n(De(),{id:l})}];for(const f of u)try{return await f.request()}catch(g){if(g?.status!==404)throw new Error(o(g,"Erro ao excluir"))}throw new Error("Endpoint de exclusão não encontrado.")}},d={ensureSwal:async()=>{window.Swal},toast:(l,u)=>{if(window.LK?.toast)return LK.toast[l]?.(u)||LK.toast.info(u);window.Swal?.fire({toast:!0,position:"top-end",timer:2500,timerProgressBar:!0,showConfirmButton:!1,icon:l,title:u})},loading:(l="Processando...")=>{if(window.LK?.loading)return LK.loading(l);window.Swal?.fire({title:l,didOpen:()=>window.Swal.showLoading(),allowOutsideClick:!1,showConfirmButton:!1})},close:()=>{if(window.LK?.hideLoading)return LK.hideLoading();window.Swal?.close()},confirm:async(l,u)=>window.LK?.confirm?LK.confirm({title:l,text:u,confirmText:"Sim, confirmar",danger:!0}):(await window.Swal?.fire({title:l,text:u,icon:"warning",showCancelButton:!0,confirmButtonText:"Sim, confirmar",cancelButtonText:"Cancelar",confirmButtonColor:"var(--color-danger)",cancelButtonColor:"var(--color-text-muted)"}))?.isConfirmed,error:(l,u)=>{if(window.LK?.toast)return LK.toast.error(u||l);window.Swal?.fire({icon:"error",title:l,text:u,confirmButtonColor:"var(--color-primary)"})}},m={badges:[{id:"first",icon:"target",name:"Inicio",condition:l=>l.totalTransactions>=1},{id:"week",icon:"bar-chart-3",name:"7 Dias",condition:l=>l.streak>=7},{id:"month",icon:"gem",name:"30 Dias",condition:l=>l.streak>=30},{id:"saver",icon:"coins",name:"Economia",condition:l=>l.savingsRate>=10},{id:"diverse",icon:"palette",name:"Diverso",condition:l=>l.uniqueCategories>=5},{id:"master",icon:"crown",name:"Mestre",condition:l=>l.totalTransactions>=100}],calculateStreak:l=>{if(!Array.isArray(l)||l.length===0)return 0;const u=l.map(C=>C.data_lancamento||C.data).filter(Boolean).map(C=>{const y=String(C).match(/^(\d{4})-(\d{2})-(\d{2})/);return y?`${y[1]}-${y[2]}-${y[3]}`:null}).filter(Boolean).sort().reverse();if(u.length===0)return 0;const f=[...new Set(u)],g=new Date;g.setHours(0,0,0,0);let h=0,p=new Date(g);for(const C of f){const[y,D,M]=C.split("-").map(Number),x=new Date(y,D-1,M);x.setHours(0,0,0,0);const A=Math.round((p-x)/(1e3*60*60*24));if(A===0||A===1)h++,p=new Date(x),p.setDate(p.getDate()-1);else if(A>1)break}return h},calculateLevel:l=>l<100?1:l<300?2:l<600?3:l<1e3?4:l<1500?5:l<2500?6:l<5e3?7:l<1e4?8:l<2e4?9:10,calculatePoints:l=>{let u=0;return u+=l.totalTransactions*10,u+=l.streak*50,u+=l.activeMonths*100,u+=l.uniqueCategories*20,u+=Math.floor(l.savingsRate)*30,u},calculateData:(l,u)=>{const f=l.length,g=m.calculateStreak(l),h=new Set(l.map(S=>S.categoria_id||S.categoria).filter(Boolean)).size,C=new Set(l.map(S=>{const T=S.data_lancamento||S.data;if(!T)return null;const $=String(T).match(/^(\d{4}-\d{2})/);return $?$[1]:null}).filter(Boolean)).size,y=Number(u?.receitas||0),D=Number(u?.despesas||0),M=y>0?(y-D)/y*100:0,x={totalTransactions:f,streak:g,uniqueCategories:h,activeMonths:C,savingsRate:Math.max(0,M)},A=m.calculatePoints(x),v=m.calculateLevel(A);return{...x,points:A,level:v}}};return{API:c,Notifications:d,Gamification:m}}function tt({STATE:r,DOM:e,Utils:t,API:a,Notifications:i,Renderers:n,Provisao:o,OptionalWidgets:s,invalidateDashboardOverview:c,getErrorMessage:d,logClientError:m}){const l={delete:async(g,h)=>{try{if(await i.ensureSwal(),!await i.confirm("Excluir lançamento?","Esta ação não pode ser desfeita."))return;i.loading("Excluindo..."),await a.deleteTransaction(Number(g)),i.close(),i.toast("success","Lançamento excluído com sucesso!"),h&&(h.style.opacity="0",h.style.transform="translateX(-20px)",setTimeout(()=>{h.remove(),e.tableBody.children.length===0&&(e.emptyState&&(e.emptyState.style.display="block"),e.table&&(e.table.style.display="none"))},300)),document.dispatchEvent(new CustomEvent("lukrato:data-changed",{detail:{resource:"transactions",action:"delete",id:Number(g)}}))}catch(p){console.error("Erro ao excluir lançamento:",p),await i.ensureSwal(),i.error("Erro",d(p,"Falha ao excluir lançamento"))}}},u={refresh:async({force:g=!1}={})=>{if(r.isLoading)return;r.isLoading=!0;const h=t.getCurrentMonth();r.currentMonth=h,g&&c(h);try{n.updateMonthLabel(h),await Promise.allSettled([n.renderKPIs(h),n.renderTable(h),n.renderTransactionsList(h),n.renderChart(h),o.render(h),s.render(h)])}catch(p){m("Erro ao atualizar dashboard",p,"Falha ao atualizar dashboard")}finally{r.isLoading=!1}},init:async()=>{await u.refresh({force:!1})}};return{TransactionManager:l,DashboardManager:u,EventListeners:{init:()=>{if(r.eventListenersInitialized)return;r.eventListenersInitialized=!0,e.tableBody?.addEventListener("click",async h=>{const p=h.target.closest(".btn-del");if(!p)return;const C=h.target.closest("tr"),y=p.getAttribute("data-id");y&&(p.disabled=!0,await l.delete(y,C),p.disabled=!1)}),e.cardsContainer?.addEventListener("click",async h=>{const p=h.target.closest(".btn-del");if(!p)return;const C=h.target.closest(".transaction-card"),y=p.getAttribute("data-id");y&&(p.disabled=!0,await l.delete(y,C),p.disabled=!1)}),e.transactionsList?.addEventListener("click",async h=>{const p=h.target.closest(".btn-del");if(!p)return;const C=h.target.closest(".dash-tx-item"),y=p.getAttribute("data-id");y&&(p.disabled=!0,await l.delete(y,C),p.disabled=!1)}),document.addEventListener("lukrato:data-changed",()=>{c(r.currentMonth||t.getCurrentMonth()),u.refresh({force:!1})}),document.addEventListener("lukrato:month-changed",()=>{u.refresh({force:!1})}),document.addEventListener("lukrato:theme-changed",()=>{n.renderChart(r.currentMonth||t.getCurrentMonth())});const g=document.getElementById("chartToggle");g&&g.addEventListener("click",h=>{const p=h.target.closest("[data-mode]");if(!p)return;const C=p.getAttribute("data-mode");g.querySelectorAll(".dash-chart-toggle__btn").forEach(y=>y.classList.remove("is-active")),p.classList.add("is-active"),n.renderChart(r.currentMonth||t.getCurrentMonth(),C)})}}}}const{API:R,Notifications:at}=et({CONFIG:F,getDashboardOverview:K,getApiPayload:$e,apiGet:ne,apiDelete:Ie,apiPost:pe,getErrorMessage:oe}),L={updateMonthLabel:r=>{_.monthLabel&&(_.monthLabel.textContent=b.formatMonth(r))},toggleAlertsSection:()=>{const r=document.getElementById("dashboardAlertsSection");r&&(r.style.display="none")},setSignedState:(r,e,t)=>{const a=document.getElementById(r),i=document.getElementById(e);!a||!i||(a.classList.remove("is-positive","is-negative","income","expense"),i.classList.remove("is-positive","is-negative"),t>0?(a.classList.add("is-positive"),i.classList.add("is-positive")):t<0&&(a.classList.add("is-negative"),i.classList.add("is-negative")))},formatSignedMoney:r=>{const e=Number(r||0);return`${e>=0?"+":"-"}${b.money(Math.abs(e))}`},renderStatusChip:(r,e,t)=>{r&&(r.innerHTML=`
            <i data-lucide="${e}" class="dashboard-status-chip-icon" style="width:16px;height:16px;"></i>
            <span>${t}</span>
        `,typeof window.lucide<"u"&&window.lucide.createIcons())},renderHeroNarrative:({saldo:r,receitas:e,despesas:t,resultado:a})=>{const i=document.getElementById("dashboardHeroStatus"),n=document.getElementById("dashboardHeroMessage"),o=Number(e||0),s=Number(t||0),c=Number.isFinite(Number(a))?Number(a):o-s;if(!(!i||!n)){if(i.className="dashboard-status-chip",n.className="dashboard-hero-message",s>o){i.classList.add("dashboard-status-chip--negative"),n.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(i,"triangle-alert",`Mês no vermelho (${L.formatSignedMoney(c)})`),n.textContent=`Atenção: você gastou mais do que ganhou (${L.formatSignedMoney(c)}).`;return}if(c>0){i.classList.add("dashboard-status-chip--positive"),n.classList.add("dashboard-hero-message--positive"),L.renderStatusChip(i,r>=0?"piggy-bank":"trending-up",r>=0?`Mês positivo (${L.formatSignedMoney(c)})`:`Recuperando o mês (${L.formatSignedMoney(c)})`),n.textContent=`Você está positivo este mês (${L.formatSignedMoney(c)}).`;return}if(c===0){i.classList.add("dashboard-status-chip--neutral"),L.renderStatusChip(i,"scale","Mês zerado (R$ 0,00)"),n.textContent=`Entrou ${b.money(o)} e saiu ${b.money(s)}. Seu saldo do mês está em R$ 0,00.`;return}i.classList.add("dashboard-status-chip--negative"),n.classList.add("dashboard-hero-message--negative"),L.renderStatusChip(i,"wallet",`Resultado do mês ${L.formatSignedMoney(c)}`),n.textContent=`Seu resultado mensal está em ${L.formatSignedMoney(c)}. Vale rever os gastos mais pesados agora.`}},renderHeroSparkline:async r=>{const e=document.getElementById("heroSparkline");if(!(!e||typeof ApexCharts>"u"))try{const t=await R.getOverview(r),a=Array.isArray(t.chart)?t.chart:[];if(a.length<2){e.innerHTML="";return}const i=a.map(c=>Number(c.resultado||0)),{isLightTheme:n}=de(),s=(i[i.length-1]||0)>=0?"#10b981":"#ef4444";I._heroSparkInstance&&(I._heroSparkInstance.destroy(),I._heroSparkInstance=null),I._heroSparkInstance=new ApexCharts(e,{chart:{type:"area",height:48,sparkline:{enabled:!0},background:"transparent"},series:[{data:i}],stroke:{width:2,curve:"smooth",colors:[s]},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.35,opacityTo:0,stops:[0,100],colorStops:[{offset:0,color:s,opacity:.25},{offset:100,color:s,opacity:0}]}},tooltip:{enabled:!0,fixed:{enabled:!1},x:{show:!1},y:{formatter:c=>b.money(c),title:{formatter:()=>""}},theme:n?"light":"dark"},colors:[s]}),I._heroSparkInstance.render()}catch{}},renderHeroContext:({receitas:r,despesas:e})=>{const t=document.getElementById("heroContext");if(!t)return;const a=Number(r||0),i=Number(e||0);if(a<=0){t.style.display="none";return}const n=(a-i)/a*100;let o,s,c;n>=20?(o="piggy-bank",s=`Você está economizando ${Math.round(n)}% da renda — excelente!`,c="dash-hero__context--positive"):n>=1?(o="target",s=`Economia de ${Math.round(n)}% da renda — meta ideal é 20%.`,c="dash-hero__context--neutral"):(o="alert-triangle",s="Sem margem de economia este mês. Revise seus gastos.",c="dash-hero__context--negative"),t.className=`dash-hero__context ${c}`,t.innerHTML=`<i data-lucide="${o}" style="width:14px;height:14px;"></i> ${s}`,t.style.display="",typeof window.lucide<"u"&&window.lucide.createIcons()},renderOverviewAlerts:({receitas:r,despesas:e})=>{const t=document.getElementById("dashboardAlertsOverview");if(!t)return;const a=document.getElementById("dashboardAlertsSection");a&&(a.style.display="none");const i=Number(r||0),n=Number(e||0),o=i-n;n>i?(t.innerHTML=`
                <a href="${F.BASE_URL}lancamentos?tipo=despesa" class="dashboard-alert dashboard-alert--danger">
                    <div class="dashboard-alert-icon">
                        <i data-lucide="triangle-alert" style="width:18px;height:18px;"></i>
                    </div>
                    <div class="dashboard-alert-content">
                        <strong>Atenção: você gastou mais do que ganhou</strong>
                        <span>Entrou ${b.money(i)} e saiu ${b.money(n)}. Diferença do mês: ${L.formatSignedMoney(o)}.</span>
                    </div>
                    <i data-lucide="arrow-right" class="dashboard-alert-arrow" style="width:16px;height:16px;"></i>
                </a>
            `,typeof window.lucide<"u"&&window.lucide.createIcons()):t.innerHTML="",L.toggleAlertsSection()},renderChartInsight:(r,e)=>{const t=document.getElementById("chartInsight");if(!t)return;if(!Array.isArray(e)||e.length===0||e.every(o=>Number(o)===0)){t.textContent="Seu historico aparece aqui conforme você usa o Lukrato mais vezes.";return}let a=0;e.forEach((o,s)=>{Number(o)<Number(e[a])&&(a=s)});const i=r[a],n=Number(e[a]||0);if(n<0){t.textContent=`Seu pior mes foi ${b.formatMonth(i)} (${b.money(n)}).`;return}t.textContent=`Seu pior mes foi ${b.formatMonth(i)} e mesmo assim fechou em ${b.money(n)}.`},renderKPIs:async r=>{try{const e=await R.getOverview(r),t=e?.metrics||{},a=Array.isArray(e?.accounts_balances)?e.accounts_balances:[],i=e?.meta||{},n={receitasValue:t.receitas||0,despesasValue:t.despesas||0,saldoMesValue:t.resultado||0};Object.entries(n).forEach(([f,g])=>{const h=document.getElementById(f);h&&(h.textContent=b.money(g))});const o=Number(t.saldoAcumulado??t.saldo??0),s=(Array.isArray(a)?a:[]).reduce((f,g)=>{const h=typeof g.saldoAtual=="number"?g.saldoAtual:g.saldoInicial||0;return f+(isFinite(h)?Number(h):0)},0),c=Array.isArray(a)&&a.length>0?s:o;_.saldoValue&&(_.saldoValue.textContent=b.money(c)),L.setSignedState("saldoValue","saldoCard",c),L.setSignedState("saldoMesValue","saldoMesCard",Number(t.resultado||0)),L.renderHeroNarrative({saldo:c,receitas:Number(t.receitas||0),despesas:Number(t.despesas||0),resultado:Number(t.resultado||0)}),L.renderHeroSparkline(r),L.renderHeroContext({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)}),L.renderOverviewAlerts({receitas:Number(t.receitas||0),despesas:Number(t.despesas||0)});const d=Number(i?.real_transaction_count??t.count??0),m=Number(i?.real_category_count??t.categories??0),l=Number(i?.real_account_count??a.length??0),u=Be(i,{accountCount:l});document.dispatchEvent(new CustomEvent("lukrato:dashboard-overview-rendered",{detail:{month:r,accountCount:l,transactionCount:d,categoryCount:m,hasData:d>0,primaryAction:u.actionType,ctaLabel:u.ctaLabel,ctaUrl:u.ctaUrl,isDemo:!!i?.is_demo}})),b.removeLoadingClass()}catch(e){N("Erro ao renderizar KPIs",e,"Falha ao carregar indicadores"),["saldoValue","receitasValue","despesasValue","saldoMesValue"].forEach(t=>{const a=document.getElementById(t);a&&(a.textContent="R$ 0,00",a.classList.remove("loading"))})}},renderTable:async r=>{try{const e=await R.getTransactions(r,F.TRANSACTIONS_LIMIT);_.tableBody&&(_.tableBody.innerHTML=""),_.cardsContainer&&(_.cardsContainer.innerHTML=""),Array.isArray(e)&&e.length>0&&e.forEach(a=>{const i=String(a.tipo||"").toLowerCase(),n=b.getTipoClass(i),o=String(a.tipo||"").replace(/_/g," "),s=a.categoria_nome??(typeof a.categoria=="string"?a.categoria:a.categoria?.nome)??null,c=s?k(s):'<span class="categoria-empty">Sem categoria</span>',d=k(b.getContaLabel(a)),m=k(a.descricao||"--"),l=k(o),u=Number(a.valor)||0,f=b.dateBR(a.data),g=document.createElement("tr");if(g.setAttribute("data-id",a.id),g.innerHTML=`
              <td data-label="Data">${f}</td>
              <td data-label="Tipo">
                <span class="badge-tipo ${n}">${l}</span>
              </td>
              <td data-label="Categoria">${c}</td>
              <td data-label="Conta">${d}</td>
              <td data-label="Descrição">${m}</td>
              <td data-label="Valor" class="valor-cell ${n}">${b.money(u)}</td>
              <td data-label="Ações" class="text-end">
                <div class="actions-cell">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              </td>
            `,_.tableBody&&_.tableBody.appendChild(g),_.cardsContainer){const h=document.createElement("div");h.className="transaction-card",h.setAttribute("data-id",a.id),h.innerHTML=`
                <div class="transaction-card-header">
                  <span class="transaction-date">${f}</span>
                  <span class="transaction-value ${n}">${b.money(u)}</span>
                </div>
                <div class="transaction-card-body">
                  <div class="transaction-info-row">
                    <span class="transaction-label">Tipo</span>
                    <span class="transaction-badge tipo-${n}">${l}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Categoria</span>
                    <span class="transaction-text">${c}</span>
                  </div>
                  <div class="transaction-info-row">
                    <span class="transaction-label">Conta</span>
                    <span class="transaction-text">${d}</span>
                  </div>
                  ${m!=="--"?`
                  <div class="transaction-info-row">
                    <span class="transaction-label">Descrição</span>
                    <span class="transaction-description">${m}</span>
                  </div>
                  `:""}
                </div>
                <div class="transaction-card-actions">
                  <button class="lk-btn danger btn-del" data-id="${a.id}" title="Excluir">
                    <i data-lucide="trash-2"></i>
                  </button>
                </div>
              `,_.cardsContainer.appendChild(h)}})}catch(e){N("Erro ao renderizar transações",e,"Falha ao carregar transações")}},renderTransactionsList:async r=>{if(_.transactionsList)try{const e=await R.getTransactions(r,F.TRANSACTIONS_LIMIT),t=Array.isArray(e)&&e.length>0;if(_.transactionsList.innerHTML="",_.emptyState&&(_.emptyState.style.display=t?"none":"flex"),!t)return;const a=new Date().toISOString().slice(0,10),i=new Date(Date.now()-864e5).toISOString().slice(0,10),n=new Map;e.forEach(o=>{const s=String(o.data||"").split(/[T\s]/)[0];n.has(s)||n.set(s,[]),n.get(s).push(o)});for(const[o,s]of n){let c;o===a?c="Hoje":o===i?c="Ontem":c=b.dateBR(o);const d=document.createElement("div");d.className="dash-tx-date-group",d.textContent=c,_.transactionsList.appendChild(d),s.forEach(m=>{const u=String(m.tipo||"").toLowerCase()==="receita",f=k(m.descricao||"--"),g=m.categoria_nome??(typeof m.categoria=="string"?m.categoria:m.categoria?.nome)??"Sem categoria",h=Number(m.valor)||0,p=!!m.pago,C=m.categoria_icone||(u?"arrow-down-left":"arrow-up-right"),y=document.createElement("div");y.className="dash-tx-item surface-card",y.setAttribute("data-id",m.id),y.innerHTML=`
                        <div class="dash-tx__left">
                            <div class="dash-tx__icon dash-tx__icon--${u?"income":"expense"}">
                                <i data-lucide="${k(C)}"></i>
                            </div>
                            <div class="dash-tx__info">
                                <span class="dash-tx__desc">${f}</span>
                                <span class="dash-tx__category">${k(g)}</span>
                            </div>
                        </div>
                        <div class="dash-tx__right">
                            <span class="dash-tx__amount dash-tx__amount--${u?"income":"expense"}">${u?"+":"-"}${b.money(Math.abs(h))}</span>
                            <span class="dash-tx__badge dash-tx__badge--${p?"paid":"pending"}">${p?"Pago":"Pendente"}</span>
                        </div>
                    `,_.transactionsList.appendChild(y)})}typeof window.lucide<"u"&&window.lucide.createIcons()}catch(e){N("Erro ao renderizar lista de transações",e,"Falha ao carregar transações"),_.emptyState&&(_.emptyState.style.display="flex")}},renderChart:async(r,e)=>{if(!(!_.categoryChart||typeof ApexCharts>"u")){e||(e=I._chartMode||"donut"),I._chartMode=e,_.chartLoading&&(_.chartLoading.style.display="flex");try{const t=await R.getOverview(r),a=Array.isArray(t.despesas_por_categoria)?t.despesas_por_categoria:[],{isLightTheme:i}=de(),n=i?"light":"dark";if(I.chartInstance&&(I.chartInstance.destroy(),I.chartInstance=null),a.length===0){const s=ge(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)});_.categoryChart.innerHTML=`
                    <div class="dash-chart-empty">
                        <i data-lucide="pie-chart"></i>
                        <strong>${k(s.chartEmptyTitle)}</strong>
                        <p>${k(s.chartEmptyDescription)}</p>
                        <button class="dash-btn dash-btn--ghost" type="button" id="dashboardChartEmptyCta">
                            <i data-lucide="plus"></i> ${k(s.chartEmptyButton)}
                        </button>
                    </div>
                `,document.getElementById("dashboardChartEmptyCta")?.addEventListener("click",()=>{fe(t?.meta||{},{accountCount:Number(t?.meta?.real_account_count??0)})}),typeof window.lucide<"u"&&window.lucide.createIcons();return}const o=["#E67E22","#2ecc71","#e74c3c","#3498db","#9b59b6","#1abc9c","#f39c12","#e91e63","#00bcd4","#8bc34a"];if(e==="compare"){const c=b.getPreviousMonths(r,2)[0];let d=[];try{const p=await R.getOverview(c);d=Array.isArray(p.despesas_por_categoria)?p.despesas_por_categoria:[]}catch{}const l=[...new Set([...a.map(p=>p.categoria),...d.map(p=>p.categoria)])],u=Object.fromEntries(a.map(p=>[p.categoria,Math.abs(Number(p.valor)||0)])),f=Object.fromEntries(d.map(p=>[p.categoria,Math.abs(Number(p.valor)||0)])),g=l.map(p=>u[p]||0),h=l.map(p=>f[p]||0);I.chartInstance=new ApexCharts(_.categoryChart,{chart:{type:"bar",height:300,background:"transparent",fontFamily:"Inter, Arial, sans-serif",toolbar:{show:!1}},series:[{name:b.formatMonthShort(r),data:g},{name:b.formatMonthShort(c),data:h}],colors:["#E67E22","rgba(230,126,34,0.35)"],xaxis:{categories:l,labels:{style:{colors:i?"#555":"#aaa",fontSize:"11px"},rotate:-35,trim:!0,maxHeight:80}},yaxis:{labels:{formatter:p=>b.money(p),style:{colors:i?"#555":"#aaa"}}},plotOptions:{bar:{borderRadius:4,columnWidth:"55%"}},dataLabels:{enabled:!1},legend:{position:"top",fontSize:"12px",labels:{colors:i?"#555":"#ccc"}},tooltip:{theme:n,y:{formatter:p=>b.money(p)}},grid:{borderColor:i?"#e5e5e5":"rgba(255,255,255,0.06)",strokeDashArray:3},theme:{mode:n}})}else{const s=a.map(d=>d.categoria),c=a.map(d=>Math.abs(Number(d.valor)||0));I.chartInstance=new ApexCharts(_.categoryChart,{chart:{type:"donut",height:280,background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:c,labels:s,colors:o.slice(0,s.length),stroke:{width:2,colors:[i?"#fff":"#1e1e1e"]},plotOptions:{pie:{donut:{size:"60%",labels:{show:!0,value:{formatter:d=>b.money(Number(d))},total:{show:!0,label:"Total",formatter:d=>b.money(d.globals.seriesTotals.reduce((m,l)=>m+l,0))}}}}},legend:{position:"bottom",fontSize:"13px",labels:{colors:i?"#555":"#ccc"}},tooltip:{theme:n,y:{formatter:d=>b.money(d)}},dataLabels:{enabled:!1},theme:{mode:n}})}I.chartInstance.render()}catch(t){N("Erro ao renderizar gráfico",t,"Falha ao carregar gráfico")}finally{_.chartLoading&&setTimeout(()=>{_.chartLoading.style.display="none"},300)}}}},st=Je({API:R,CONFIG:F,Utils:b,escapeHtml:k,logClientError:N}),nt=Ze({API:R,Utils:b,escapeHtml:k,logClientError:N}),{DashboardManager:ae,EventListeners:ot}=tt({STATE:I,DOM:_,Utils:b,API:R,Notifications:at,Renderers:L,Provisao:nt,OptionalWidgets:st,invalidateDashboardOverview:P,getErrorMessage:oe,logClientError:N}),it={toggleHealthScore:"sectionHealthScore",toggleAiTip:"sectionAiTip",toggleEvolucao:"sectionEvolucao",toggleAlertas:"sectionAlertas",toggleGrafico:"chart-section",togglePrevisao:"sectionPrevisao",toggleMetas:"sectionMetas",toggleCartoes:"sectionCartoes",toggleContas:"sectionContas",toggleOrcamentos:"sectionOrcamentos",toggleFaturas:"sectionFaturas",toggleGamificacao:"sectionGamificacao"},re={toggleHealthScore:!0,toggleAiTip:!0,toggleEvolucao:!0,toggleAlertas:!0,toggleGrafico:!0,togglePrevisao:!0,toggleMetas:!1,toggleCartoes:!1,toggleContas:!1,toggleOrcamentos:!1,toggleFaturas:!1,toggleGamificacao:!1},rt={...re,toggleHealthScore:!1,toggleAiTip:!1,toggleEvolucao:!1,togglePrevisao:!1};async function ct(){return He("dashboard")}async function lt(r){await Pe("dashboard",r)}function se(r){return!!r&&getComputedStyle(r).display!=="none"}function j(r,{hideWhenEmpty:e=!0}={}){if(!r)return 0;const t=Array.from(r.children).filter(se).length;return r.dataset.visibleCount=String(t),e&&(r.style.display=t>0?"":"none"),t}function Q(r,e){r&&(r.dataset.visibleCount=String(e),r.style.display=e>0?"":"none")}function dt(r=re){const e=document.querySelector(".dashboard-stage--overview"),t=document.querySelector(".dashboard-overview-top"),a=document.querySelector(".dashboard-overview-bottom"),i=document.getElementById("sectionAlertas"),n=document.getElementById("rowHealthAi"),o=document.getElementById("healthScoreInsights"),s=document.querySelector(".dashboard-stage--decision"),c=document.querySelector(".dash-duo-row--decision"),d=document.querySelector(".dash-duo-row--insights"),m=document.querySelector(".dashboard-stage--history"),l=document.getElementById("sectionEvolucao"),u=document.querySelector(".dashboard-stage--secondary"),f=document.getElementById("optionalGrid"),g=j(t,{hideWhenEmpty:!1});j(n),o&&(o.style.display=r.toggleHealthScore?"":"none");const h=[i,n,o].filter(se).length;a&&(a.dataset.visibleCount=String(h),a.style.display=h>0?"":"none");const p=j(c),C=j(d,{hideWhenEmpty:!1}),y=j(f);f&&(f.dataset.layout=y>0&&y<5?"fluid":"default"),Q(e,(g>0?1:0)+(h>0?1:0)),Q(s,(p>0?1:0)+(C>0?1:0)),Q(m,se(l)?1:0),Q(u,y>0?1:0)}const ut=Re({storageKey:"lk_dashboard_prefs",sectionMap:it,completeDefaults:re,essentialDefaults:rt,gridContainerId:"optionalGrid",gridToggleKeys:["toggleMetas","toggleCartoes","toggleContas","toggleOrcamentos","toggleFaturas"],loadPreferences:ct,savePreferences:lt,onApply:dt});function mt(){ut.init()}window.__LK_DASHBOARD_LOADER__||(window.__LK_DASHBOARD_LOADER__=!0,window.refreshDashboard=ae.refresh,window.LK=window.LK||{},window.LK.refreshDashboard=ae.refresh,(()=>{const e=()=>{ot.init(),ae.init(),mt()};document.readyState==="loading"?document.addEventListener("DOMContentLoaded",e):e()})());
