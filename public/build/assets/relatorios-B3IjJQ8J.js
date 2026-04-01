import{a as Ee}from"./utils-Bj4jxwhy.js";import{c as Se,p as xe,f as _e}from"./ui-preferences-COf1b1j3.js";import{e as me,d as O}from"./api-CpqPnVR7.js";const Y="Relatórios são exclusivos do plano Pro.",p={BASE_URL:window.LK?.getBase?.()||"/",CHART_COLORS:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C","#E74C3C","#3498DB"],FETCH_TIMEOUT:3e4,VIEWS:{CATEGORY:"category",BALANCE:"balance",COMPARISON:"comparison",ACCOUNTS:"accounts",CARDS:"cards",EVOLUTION:"evolution",ANNUAL_SUMMARY:"annual_summary",ANNUAL_CATEGORY:"annual_category"}},Ae=new Set([p.VIEWS.ANNUAL_SUMMARY,p.VIEWS.ANNUAL_CATEGORY]),z={[p.VIEWS.CATEGORY]:[{value:"despesas_por_categoria",label:"Despesas por categoria"},{value:"receitas_por_categoria",label:"Receitas por categoria"}],[p.VIEWS.ANNUAL_CATEGORY]:[{value:"despesas_anuais_por_categoria",label:"Despesas anuais por categoria"},{value:"receitas_anuais_por_categoria",label:"Receitas anuais por categoria"}]},R={ACTIVE_SECTION:"rel_active_section",ACTIVE_VIEW:"rel_active_view",CATEGORY_TYPE:"rel_category_type",ANNUAL_CATEGORY_TYPE:"rel_annual_category_type"},ne={overview:{kicker:"Painel consolidado",title:"Leia seu mes com contexto",description:"Veja seu pulso financeiro, identifique sinais importantes e acompanhe a evolucao do periodo em um resumo rapido."},relatorios:{kicker:"Relatorio ativo",title:"Transforme lancamentos em decisao",description:"Explore seus numeros por categoria, conta, cartao e evolucao para descobrir onde agir."},insights:{kicker:"Leitura automatica",title:"Insights que ajudam a agir",description:"Receba sinais claros sobre gastos, saldo, concentracoes e oportunidades sem precisar interpretar tudo manualmente."},comparativos:{kicker:"Comparacao temporal",title:"Compare e ajuste sua rota",description:"Entenda o que melhorou, piorou ou estagnou em relacao ao mes e ao ano anteriores."}},ie={[p.VIEWS.CATEGORY]:{title:"Categorias do periodo",description:"Encontre rapidamente onde seu dinheiro esta concentrado por categoria."},[p.VIEWS.BALANCE]:{title:"Saldo diario",description:"Acompanhe como seu caixa evolui ao longo do periodo."},[p.VIEWS.COMPARISON]:{title:"Receitas x despesas",description:"Compare entradas e saidas para entender pressao ou folga no caixa."},[p.VIEWS.ACCOUNTS]:{title:"Desempenho por conta",description:"Descubra quais contas concentram mais entradas e saidas."},[p.VIEWS.CARDS]:{title:"Saude dos cartoes",description:"Monitore faturas, uso de limite e sinais de atencao nos cartoes."},[p.VIEWS.EVOLUTION]:{title:"Evolucao em 12 meses",description:"Observe tendencia, sazonalidade e ritmo financeiro ao longo do ultimo ano."},[p.VIEWS.ANNUAL_SUMMARY]:{title:"Resumo anual",description:"Compare mes a mes como receitas, despesas e saldo se comportaram no ano."},[p.VIEWS.ANNUAL_CATEGORY]:{title:"Categorias do ano",description:"Veja quais categorias dominaram seu ano e onde houve maior concentracao."}};function he(){const e=new Date;return`${e.getFullYear()}-${String(e.getMonth()+1).padStart(2,"0")}`}const v=e=>String(e??"").replace(/[&<>"']/g,function(a){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"}[a]??a});function $e(e,a="#cccccc"){return/^#[0-9A-Fa-f]{6}$/.test(e)?e:a}const i={activeSection:"overview",currentView:p.VIEWS.CATEGORY,categoryType:"despesas_por_categoria",annualCategoryType:"despesas_anuais_por_categoria",currentMonth:he(),currentAccount:null,chart:null,accounts:[],accessRestricted:!1,lastReportError:null,activeDrilldown:null,reportDetails:null},_={getCurrentMonth:he,formatCurrency(e){return Ee(e)},formatMonthLabel(e){const[a,t]=e.split("-");return new Date(a,t-1).toLocaleDateString("pt-BR",{month:"long",year:"numeric"})},addMonths(e,a){const[t,s]=e.split("-").map(Number),r=new Date(t,s-1+a);return`${r.getFullYear()}-${String(r.getMonth()+1).padStart(2,"0")}`},hexToRgba(e,a=.25){const t=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),r=parseInt(e.slice(5,7),16);return`rgba(${t}, ${s}, ${r}, ${a})`},generateShades(e,a){const t=parseInt(e.slice(1,3),16),s=parseInt(e.slice(3,5),16),r=parseInt(e.slice(5,7),16),o=[];for(let n=0;n<a;n++){const l=.35-n/Math.max(a-1,1)*.7,c=u=>Math.min(255,Math.max(0,Math.round(u+(l>0?(255-u)*l:u*l)))),d=c(t),h=c(s),m=c(r);o.push(`#${d.toString(16).padStart(2,"0")}${h.toString(16).padStart(2,"0")}${m.toString(16).padStart(2,"0")}`)}return o},isYearlyView(e=i.currentView){return Ae.has(e)},extractFilename(e){if(!e)return null;const a=/filename\*=UTF-8''([^;]+)/i.exec(e);if(a)try{return decodeURIComponent(a[1])}catch{return a[1]}const t=/filename="?([^";]+)"?/i.exec(e);return t?t[1]:null},getCssVar(e,a=""){try{return(getComputedStyle(document.documentElement).getPropertyValue(e)||"").trim()||a}catch{return a}},isLightTheme(){try{return(document.documentElement?.getAttribute("data-theme")||"dark")==="light"}catch{return!1}},getReportType(){return{[p.VIEWS.CATEGORY]:i.categoryType,[p.VIEWS.ANNUAL_CATEGORY]:i.annualCategoryType,[p.VIEWS.BALANCE]:"saldo_mensal",[p.VIEWS.COMPARISON]:"receitas_despesas_diario",[p.VIEWS.ACCOUNTS]:"receitas_despesas_por_conta",[p.VIEWS.CARDS]:"cartoes_credito",[p.VIEWS.EVOLUTION]:"evolucao_12m",[p.VIEWS.ANNUAL_SUMMARY]:"resumo_anual"}[i.currentView]??i.categoryType},getActiveCategoryType(){return i.currentView===p.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType:i.categoryType}},M={},I=e=>_.formatCurrency(e),F=e=>String(e??"").replace(/[&<>"']/g,a=>({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#39;"})[a]||a);function H(){const e=(document.documentElement.getAttribute("data-theme")||"").toLowerCase()==="light"||_.isLightTheme?.();return{isLight:e,mode:e?"light":"dark",textColor:e?"#2c3e50":"#ffffff",textMuted:e?"#6c757d":"rgba(255, 255, 255, 0.7)",gridColor:e?"rgba(0, 0, 0, 0.08)":"rgba(255, 255, 255, 0.05)",surfaceColor:getComputedStyle(document.documentElement).getPropertyValue("--color-surface").trim()}}function Q(e=[]){return e.map(a=>{const t=Number(a);return Number.isFinite(t)?t:0})}function ce(e,a=380){const t=e?.closest(".chart-container")||e?.parentElement,s=t?getComputedStyle(t):null,r=s?Number.parseFloat(s.height):Number.NaN,o=s?Number.parseFloat(s.minHeight):Number.NaN,n=t?.getBoundingClientRect?.().height??Number.NaN,l=s?(Number.parseFloat(s.paddingTop)||0)+(Number.parseFloat(s.paddingBottom)||0):0,c=window.innerWidth<768?320:a,d=[r,n,o,c].find(h=>Number.isFinite(h)&&h>0)??c;return Math.max(260,Math.round(d-l))}const b={_currentEntries:null,destroy(){i.chart&&(Array.isArray(i.chart)?i.chart.forEach(e=>e?.destroy()):i.chart.destroy(),i.chart=null),b._drilldownChart&&(b._drilldownChart.destroy(),b._drilldownChart=null),i.activeDrilldown=null,i.reportDetails=null},setupDefaults(){const e=getComputedStyle(document.documentElement).getPropertyValue("--color-text").trim();window.Apex=window.Apex||{},window.Apex.chart={foreColor:e,fontFamily:"Inter, Arial, sans-serif"},window.Apex.grid={borderColor:"rgba(255, 255, 255, 0.1)"}},renderPie(e){const{labels:a=[],values:t=[],details:s=null,cat_ids:r=null}=e;if(!a.length||!t.some(f=>f>0))return M.UI.showEmptyState();let o=a.map((f,C)=>({label:f,value:Number(t[C])||0,color:p.CHART_COLORS[C%p.CHART_COLORS.length],catId:r?r[C]??null:null})).filter(f=>f.value>0).sort((f,C)=>C.value-f.value);!r&&s&&(o=o.map(f=>{const C=s.find(L=>L.label===f.label);return{...f,catId:C?.cat_id??null}}));const n=window.innerWidth<768;let l=o;if(n&&o.length>5){const f=o.slice(0,5),L=o.slice(5).reduce((V,G)=>V+G.value,0);l=[...f,{label:"Outros",value:L,color:"#95a5a6",isOthers:!0}]}const c=!n&&l.length>2,d=c?Math.ceil(l.length/2):l.length,h=c?[l.slice(0,d),l.slice(d)].filter(f=>f.length):[l],m=`
            <div class="chart-container chart-container-pie">
                <div class="chart-dual">
                    ${h.map((f,C)=>`
                        <div class="chart-wrapper chart-wrapper-pie">
                            <div id="chart${C}"></div>
                        </div>
                    `).join("")}
                </div>
            </div>
            <div id="subcategoryDrilldown" class="drilldown-panel" aria-hidden="true"></div>
            ${n?'<div id="categoryListMobile" class="category-list-mobile"></div>':""}
        `;M.UI.setContent(m),b.destroy(),i.reportDetails=s,i.activeDrilldown=null,b._currentEntries=l;const u=_.getActiveCategoryType(),S={receitas_por_categoria:"Receitas por Categoria",despesas_por_categoria:"Despesas por Categoria",receitas_anuais_por_categoria:"Receitas anuais por Categoria",despesas_anuais_por_categoria:"Despesas anuais por Categoria"}[u]||"Distribuição por Categoria",y=H();let A=0;i.chart=h.map((f,C)=>{const L=document.getElementById(`chart${C}`);if(!L)return null;const V=f.reduce((x,U)=>x+U.value,0),G=A;A+=f.length;const se=new ApexCharts(L,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif",events:{dataPointSelection:(x,U,oe)=>{const re=G+oe.dataPointIndex,K=l[re];!K||K.isOthers||b.handlePieClick(K,re,oe.dataPointIndex,C)},dataPointMouseEnter:x=>{x.target&&(x.target.style.cursor="pointer")},dataPointMouseLeave:x=>{x.target&&(x.target.style.cursor="default")}}},series:f.map(x=>x.value),labels:f.map(x=>x.label),colors:f.map(x=>x.color),stroke:{width:2,colors:[y.surfaceColor]},plotOptions:{pie:{donut:{size:"60%"},expandOnClick:!0}},legend:{show:!n,position:"bottom",labels:{colors:y.textColor},markers:{shape:"circle"}},title:{text:h.length>1?`${S} - Parte ${C+1}`:S,align:"center",style:{fontSize:"14px",fontWeight:"bold",color:y.textColor}},tooltip:{theme:y.mode,y:{formatter:x=>{const U=V>0?(x/V*100).toFixed(1):"0";return`${I(x)} (${U}%)`}}},dataLabels:{enabled:!1},theme:{mode:y.mode}});return se.render(),se}),n&&b.renderMobileCategoryList(l)},renderMobileCategoryList(e){const a=document.getElementById("categoryListMobile");if(!a)return;const t=e.reduce((o,n)=>o+n.value,0),s=!!i.reportDetails&&window.IS_PRO,r=e.map((o,n)=>{const l=(o.value/t*100).toFixed(1),c=s&&o.catId!=null?i.reportDetails.find(u=>u.cat_id===o.catId):null,d=c&&c.subcategories&&c.subcategories.filter(u=>u.id!==0).length>0,h=d?'<i data-lucide="chevron-down" class="category-chevron"></i>':"";let m="";if(d){const u=_.generateShades(o.color,c.subcategories.length);m=`
                    <div class="category-subcats-panel" id="mobileSubcatPanel-${n}" aria-hidden="true">
                        ${c.subcategories.map((g,S)=>{const y=c.total>0?(g.total/c.total*100).toFixed(1):"0.0";return`
                                <div class="drilldown-item drilldown-item-mobile">
                                    <div class="drilldown-indicator" style="background-color: ${u[S]}"></div>
                                    <div class="drilldown-info">
                                        <span class="drilldown-name">${F(g.label)}</span>
                                    </div>
                                    <div class="drilldown-values">
                                        <span class="drilldown-value">${I(g.total)}</span>
                                        <span class="drilldown-pct">${y}%</span>
                                    </div>
                                </div>
                            `}).join("")}
                    </div>
                `}return`
                <div class="category-item ${d?"has-subcats":""}"
                     ${d?`data-subcat-toggle="${n}"`:""}>
                    <div class="category-indicator" style="background-color: ${o.color}"></div>
                    <div class="category-info">
                        <span class="category-name">${F(o.label)}</span>
                        <span class="category-value">${I(o.value)}</span>
                    </div>
                    <span class="category-percentage">${l}%</span>
                    ${h}
                </div>
                ${m}
            `}).join("");a.innerHTML=`
            <button class="category-expand-btn" id="expandCategoriesBtn" aria-expanded="false">
                <span>Ver todas as categorias</span>
                <i data-lucide="chevron-down"></i>
            </button>
            <div class="category-expandable-card" id="expandableCard" aria-hidden="true">
                ${r}
            </div>
            ${s?"":`<p class="category-info-text">
                <i data-lucide="info"></i>
                Para visualizar todas as categorias detalhadamente, exporte este relatório em PDF.
            </p>`}
        `,window.lucide&&lucide.createIcons(),b.setupExpandToggle(),s&&b.setupMobileSubcatToggles()},setupMobileSubcatToggles(){document.querySelectorAll("[data-subcat-toggle]").forEach(e=>{e.addEventListener("click",function(){const a=this.dataset.subcatToggle,t=document.getElementById(`mobileSubcatPanel-${a}`),s=this.querySelector(".category-chevron");if(!t)return;t.getAttribute("aria-hidden")==="false"?(t.style.maxHeight="0px",t.setAttribute("aria-hidden","true"),s&&(s.style.transform="rotate(0deg)")):(t.style.maxHeight=t.scrollHeight+"px",t.setAttribute("aria-hidden","false"),s&&(s.style.transform="rotate(180deg)"))})})},setupExpandToggle(){const e=document.getElementById("expandCategoriesBtn"),a=document.getElementById("expandableCard");!e||!a||e.addEventListener("click",function(){e.getAttribute("aria-expanded")==="true"?(a.style.maxHeight="0px",a.setAttribute("aria-hidden","true"),e.setAttribute("aria-expanded","false"),e.querySelector("span").textContent="Ver todas as categorias",e.querySelector("i").style.transform="rotate(0deg)"):(a.style.maxHeight=a.scrollHeight+"px",a.setAttribute("aria-hidden","false"),e.setAttribute("aria-expanded","true"),e.querySelector("span").textContent="Ocultar categorias",e.querySelector("i").style.transform="rotate(180deg)")})},handlePieClick(e,a,t,s){if(!window.IS_PRO){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"O detalhamento por subcategorias é exclusivo do plano Pro."}).catch(()=>{}):window.Swal?.fire&&Swal.fire({icon:"info",title:"Recurso Premium",html:"O detalhamento por <b>subcategorias</b> é exclusivo do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:"Fazer Upgrade",showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(l=>{l.isConfirmed&&(window.location.href=p.BASE_URL+"billing")});return}if(!i.reportDetails)return;const r=e.catId,o=i.reportDetails.find(l=>l.cat_id===r);if(!o||!o.subcategories||o.subcategories.length===0)return;if(o.subcategories.filter(l=>l.id!==0).length===0){window.Swal?.fire&&Swal.fire({icon:"info",title:"Sem subcategorias",text:"Atribua subcategorias aos seus lançamentos para ver o detalhamento desta categoria.",confirmButtonText:"Entendi",confirmButtonColor:"#f59e0b",timer:5e3,timerProgressBar:!0});return}if(i.activeDrilldown===r){b.closeDrilldown();return}i.activeDrilldown=r,b.renderSubcategoryDrilldown(o,e.color)},closeDrilldown(){i.activeDrilldown=null;const e=document.getElementById("subcategoryDrilldown");e&&(e.style.maxHeight="0px",e.setAttribute("aria-hidden","true"),setTimeout(()=>{e.innerHTML=""},400))},renderSubcategoryDrilldown(e,a){const t=document.getElementById("subcategoryDrilldown");if(!t)return;const{label:s,total:r,subcategories:o}=e,n=_.generateShades(a,o.length),l=o.map((h,m)=>{const u=r>0?(h.total/r*100).toFixed(1):"0.0",g=r>0?(h.total/r*100).toFixed(0):"0";return`
                <div class="drilldown-item" style="animation-delay: ${m*.05}s">
                    <div class="drilldown-indicator" style="background-color: ${n[m]}"></div>
                    <div class="drilldown-info">
                        <span class="drilldown-name">${F(h.label)}</span>
                        <div class="drilldown-bar-bg">
                            <div class="drilldown-bar" style="width: ${g}%; background-color: ${n[m]}"></div>
                        </div>
                    </div>
                    <div class="drilldown-values">
                        <span class="drilldown-value">${I(h.total)}</span>
                        <span class="drilldown-pct">${u}%</span>
                    </div>
                </div>
            `}).join(""),c=window.innerWidth<768,d=c?"":`
            <div class="drilldown-mini-chart">
                <div id="drilldownMiniChart"></div>
            </div>
        `;t.innerHTML=`
            <div class="drilldown-header" style="border-left-color: ${a}">
                <div class="drilldown-title">
                    <span class="drilldown-cat-indicator" style="background-color: ${a}"></span>
                    <h4>${F(s)}</h4>
                    <span class="drilldown-total">${I(r)}</span>
                </div>
                <button class="drilldown-close" id="drilldownCloseBtn" aria-label="Fechar detalhamento">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="drilldown-body">
                ${d}
                <div class="drilldown-list">
                    ${l}
                </div>
            </div>
        `,t.setAttribute("aria-hidden","false"),requestAnimationFrame(()=>{t.style.maxHeight=t.scrollHeight+"px"}),document.getElementById("drilldownCloseBtn")?.addEventListener("click",()=>{b.closeDrilldown()}),c||b._renderDrilldownMiniChart(o,n),window.lucide&&lucide.createIcons()},_renderDrilldownMiniChart(e,a){const t=document.getElementById("drilldownMiniChart");if(!t)return;b._drilldownChart&&(b._drilldownChart.destroy(),b._drilldownChart=null);const s=H(),r=e.reduce((o,n)=>o+n.total,0);b._drilldownChart=new ApexCharts(t,{chart:{type:"donut",height:"100%",background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:e.map(o=>o.total),labels:e.map(o=>o.label),colors:a,stroke:{width:2,colors:[s.surfaceColor]},plotOptions:{pie:{donut:{size:"55%"}}},legend:{show:!1},tooltip:{theme:s.mode,y:{formatter:o=>{const n=r>0?(o/r*100).toFixed(1):"0";return`${I(o)} (${n}%)`}}},dataLabels:{enabled:!1},theme:{mode:s.mode}}),b._drilldownChart.render()},_drilldownChart:null,renderLine(e){const{labels:a=[],values:t=[]}={...e,values:Q(e?.values)};if(!a.length)return M.UI.showEmptyState();M.UI.setContent(`
            <div class="chart-container chart-container-line">
                <div class="chart-wrapper chart-wrapper-line">
                    <div id="chart0"></div>
                </div>
            </div>
        `),b.destroy();const s=getComputedStyle(document.documentElement).getPropertyValue("--color-primary").trim(),r=H(),o=document.getElementById("chart0"),n=ce(o,420),l=new ApexCharts(o,{chart:{type:"area",height:n,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Saldo Diário",data:t.map(Number)}],xaxis:{categories:a,labels:{style:{fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:r.isLight?"#000":"#fff",fontSize:"11px"},formatter:c=>I(c)}},colors:[s],stroke:{curve:"smooth",width:2.5},fill:{type:"gradient",gradient:{shadeIntensity:1,opacityFrom:.4,opacityTo:.05,stops:[0,100]}},markers:{size:4,hover:{size:6}},grid:{borderColor:r.gridColor,strokeDashArray:4},tooltip:{theme:r.mode,y:{formatter:c=>I(c)}},legend:{position:"bottom",labels:{colors:r.textColor}},title:{text:"Evolução do Saldo Mensal",align:"center",style:{fontSize:"16px",fontWeight:"bold",color:r.textColor}},dataLabels:{enabled:!1},theme:{mode:r.mode}});l.render(),i.chart=l},renderBar(e){const{labels:a=[],receitas:t=[],despesas:s=[]}={...e,receitas:Q(e?.receitas),despesas:Q(e?.despesas)};if(!a.length)return M.UI.showEmptyState();M.UI.setContent(`
            <div class="chart-container chart-container-bar">
                <div class="chart-wrapper chart-wrapper-bar">
                    <div id="chart0"></div>
                </div>
            </div>
        `),b.destroy();const r=_.getCssVar("--color-success","#2ecc71"),o=_.getCssVar("--color-danger","#e74c3c"),n=H(),l=document.getElementById("chart0"),c=ce(l,420),d=i.currentView===p.VIEWS.ACCOUNTS?"Receitas x Despesas por Conta":i.currentView===p.VIEWS.ANNUAL_SUMMARY?"Resumo Anual por Mês":"Receitas x Despesas",h=new ApexCharts(l,{chart:{type:"bar",height:c,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif",parentHeightOffset:0,redrawOnParentResize:!0,redrawOnWindowResize:!0},series:[{name:"Receitas",data:t.map(Number)},{name:"Despesas",data:s.map(Number)}],xaxis:{categories:a,labels:{style:{colors:n.textMuted,fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:n.isLight?"#000":"#fff",fontSize:"11px"},formatter:m=>I(m)}},colors:[r,o],plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:n.gridColor,strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n.mode,shared:!0,intersect:!1,y:{formatter:m=>I(m)}},legend:{position:"bottom",labels:{colors:n.textColor},markers:{shape:"circle"}},title:{text:d,align:"center",style:{fontSize:"16px",fontWeight:"bold",color:n.textColor}},dataLabels:{enabled:!1},theme:{mode:n.mode}});h.render(),i.chart=h}};M.ChartManager=b;const Te={toggleRelQuickStats:"relQuickStats",toggleRelOverviewCharts:"relOverviewChartsRow",toggleRelControls:"relControlsRow"},ge={toggleRelQuickStats:!0,toggleRelOverviewCharts:!0,toggleRelControls:!0},Ie={...ge,toggleRelQuickStats:!1,toggleRelControls:!1};async function Me(){return _e("relatorios")}async function Le(e){await xe("relatorios",e)}const Re=Se({storageKey:"lk_relatorios_prefs",sectionMap:Te,completeDefaults:ge,essentialDefaults:Ie,loadPreferences:Me,savePreferences:Le,modal:{overlayId:"relatoriosCustomizeModalOverlay",openButtonId:"btnCustomizeRelatorios",closeButtonId:"btnCloseCustomizeRelatorios",saveButtonId:"btnSaveCustomizeRelatorios",presetEssentialButtonId:"btnPresetEssencialRelatorios",presetCompleteButtonId:"btnPresetCompletoRelatorios"}});function Oe(){Re.init()}const w=e=>_.formatCurrency(e);function W(e,a,t,s=!1){const r=document.getElementById(e);if(!r)return;if(!t||t===0){r.innerHTML="",r.className="stat-trend";return}const o=(a-t)/Math.abs(t)*100,n=Math.abs(o).toFixed(1);if(Math.abs(o)<.5)r.className="stat-trend trend-neutral",r.textContent="— Sem alteração";else{const l=o>0,c=s?!l:l;r.className=`stat-trend ${c?"trend-positive":"trend-negative"}`;const d=l?"↑":"↓";r.textContent=`${d} ${n}% vs mês anterior`}}function ke(e){const a=document.querySelector(".chart-insight-line");if(a&&a.remove(),!e)return;let t="";switch(i.currentView){case p.VIEWS.CATEGORY:case p.VIEWS.ANNUAL_CATEGORY:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.reduce((l,c)=>l+Number(c),0);if(n>0){const l=e.values.reduce((d,h,m,u)=>Number(h)>Number(u[d])?m:d,0),c=(Number(e.values[l])/n*100).toFixed(0);t=`${e.labels[l]} lidera com ${c}% dos gastos (${w(e.values[l])})`}break}case p.VIEWS.BALANCE:{if(!e.labels||!e.values||e.values.length===0)break;const n=e.values.map(Number),l=Math.min(...n),c=n.indexOf(l);t=`Menor saldo: ${w(l)} em ${e.labels[c]}`;break}case p.VIEWS.COMPARISON:{if(!e.receitas||!e.despesas)break;const n=e.receitas.map(Number),l=e.despesas.map(Number);t=`Em ${n.filter((d,h)=>d>(l[h]||0)).length} de ${n.length} dias, receitas superaram despesas`;break}case p.VIEWS.ACCOUNTS:{if(!e.labels||!e.despesas||e.despesas.length===0)break;const n=e.despesas.map(Number),l=n.reduce((c,d,h,m)=>d>m[c]?h:c,0);t=`Maior gasto: ${e.labels[l]} com ${w(n[l])} em despesas`;break}case p.VIEWS.EVOLUTION:{if(!e.values||e.values.length<2)break;const n=e.values.map(Number),l=n[0],c=n[n.length-1];t=`Evolução nos últimos 12 meses: ${c>l?"tendência de alta":c<l?"tendência de queda":"estável"}`;break}case p.VIEWS.ANNUAL_SUMMARY:{if(!e.labels||!e.receitas||e.receitas.length===0)break;const n=e.receitas.map(Number),l=e.despesas.map(Number),c=n.map((m,u)=>m-(l[u]||0)),d=c.reduce((m,u,g,S)=>u>S[m]?g:m,0),h=c.reduce((m,u,g,S)=>u<S[m]?g:m,0);t=`Melhor mês: ${e.labels[d]}. Pior mês: ${e.labels[h]}`;break}}if(!t)return;const r=document.getElementById("reportArea");if(!r)return;const o=document.createElement("div");o.className="chart-insight-line",o.innerHTML=`<i data-lucide="sparkles"></i> <span>${v(t)}</span>`,r.appendChild(o),window.lucide&&lucide.createIcons()}function Be(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="bar-chart-3"></i> Top Categorias de Despesa</h3>
                <span class="comp-subtitle">Mês atual vs anterior</span>
            </div>
            <div class="cat-comp-list">
                <div class="cat-comp-header-row">
                    <span></span><span></span>
                    <span class="cat-comp-col-label">Atual / Anterior</span>
                    <span class="cat-comp-col-label">Variação</span>
                </div>
                ${e.map((t,s)=>{const r=t.variacao>0?"trend-negative":t.variacao<0?"trend-positive":"trend-neutral",o=t.variacao>0?"arrow-up":t.variacao<0?"arrow-down":"equal",n=Math.abs(t.variacao)<.1?"Sem alteração":`${t.variacao>0?"+":""}${t.variacao.toFixed(1)}%`,l=e.reduce((h,m)=>h+m.atual,0),c=l>0?(t.atual/l*100).toFixed(0):0;let d="";return t.subcategorias&&t.subcategorias.length>0&&(d=`<div class="cat-comp-subcats">${t.subcategorias.map(m=>{const u=m.variacao>0?"trend-negative":m.variacao<0?"trend-positive":"",g=Math.abs(m.variacao)<.1?"":`<span class="subcat-trend ${u}">${m.variacao>0?"↑":"↓"}${Math.abs(m.variacao).toFixed(0)}%</span>`;return`
                    <span class="cat-comp-subcat-pill">
                        ${v(m.nome)}
                        <span class="subcat-value">${w(m.atual)}</span>
                        ${g}
                    </span>
                `}).join("")}</div>`),`
            <div class="cat-comp-row" style="animation-delay: ${s*.06}s">
                <div class="cat-comp-rank">${s+1}</div>
                <div class="cat-comp-info">
                    <span class="cat-comp-name">${v(t.nome)}</span>
                    <div class="cat-comp-bar-bg">
                        <div class="cat-comp-bar" style="width: ${c}%"></div>
                    </div>
                    ${d}
                </div>
                <div class="cat-comp-values">
                    <span class="cat-comp-current">${w(t.atual)}</span>
                    <span class="cat-comp-prev">${w(t.anterior)}</span>
                </div>
                <div class="cat-comp-trend ${r}">
                    <i data-lucide="${o}"></i>
                    <span>${n}</span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function Ne(e){return!e||e.length===0?"":`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="line-chart"></i> Evolução dos Últimos 6 Meses</h3>
                <span class="comp-subtitle">Receitas, despesas e saldo ao longo do tempo</span>
            </div>
            <div class="evolucao-chart-wrapper">
                <div id="evolucaoMiniChart" style="min-height:220px;"></div>
            </div>
        </div>
    `}let P=null;function Pe(e){if(!e||e.length===0)return;const a=document.getElementById("evolucaoMiniChart");if(!a)return;const t=e.map(l=>l.label),r=getComputedStyle(document.documentElement).getPropertyValue("--color-text-muted").trim()||"#999",n=document.documentElement.getAttribute("data-theme")==="dark"?"dark":"light";P&&(P.destroy(),P=null),P=new ApexCharts(a,{chart:{type:"line",height:260,stacked:!1,toolbar:{show:!1},background:"transparent",fontFamily:"Inter, Arial, sans-serif"},series:[{name:"Receitas",type:"column",data:e.map(l=>l.receitas)},{name:"Despesas",type:"column",data:e.map(l=>l.despesas)},{name:"Saldo",type:"area",data:e.map(l=>l.saldo)}],xaxis:{categories:t,labels:{style:{colors:r}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{colors:r},formatter:l=>w(l)}},colors:["rgba(46, 204, 113, 0.85)","rgba(231, 76, 60, 0.85)","#3498db"],stroke:{width:[0,0,2.5],curve:"smooth"},fill:{opacity:[.85,.85,.1]},plotOptions:{bar:{borderRadius:6,columnWidth:"55%"}},grid:{borderColor:"rgba(128,128,128,0.1)",strokeDashArray:4,xaxis:{lines:{show:!1}}},tooltip:{theme:n,shared:!0,intersect:!1,y:{formatter:l=>w(l)}},legend:{position:"bottom",labels:{colors:r},markers:{shape:"circle"}},dataLabels:{enabled:!1},theme:{mode:n}}),P.render()}function De(e){if(!e)return"";const a=e.variacao>0?"trend-negative":e.variacao<0?"trend-positive":"trend-neutral",t=e.variacao>0?"arrow-up":e.variacao<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                <i data-lucide="calendar-clock"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Média Diária de Gastos</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${w(e.atual)}/dia</span>
                    <span class="comp-mini-prev">anterior: ${w(e.anterior)}/dia</span>
                </div>
                <div class="comp-mini-trend ${a}">
                    <i data-lucide="${t}"></i>
                    <span>${Math.abs(e.variacao).toFixed(1)}%</span>
                </div>
            </div>
        </div>
    `}function Ve(e){if(!e)return"";const a=e.atual>=0,t=e.diferenca>0?"trend-positive":e.diferenca<0?"trend-negative":"trend-neutral",s=e.diferenca>0?"arrow-up":e.diferenca<0?"arrow-down":"equal";return`
        <div class="comparative-card comp-mini-card surface-card surface-card--interactive">
            <div class="comp-mini-icon" style="background: linear-gradient(135deg, ${a?"#2ecc71, #27ae60":"#e74c3c, #c0392b"});">
                <i data-lucide="piggy-bank" style= "color: white"></i>
            </div>
            <div class="comp-mini-body">
                <span class="comp-mini-label">Taxa de Economia</span>
                <div class="comp-mini-values">
                    <span class="comp-mini-current">${e.atual.toFixed(1)}%</span>
                    <span class="comp-mini-prev">anterior: ${e.anterior.toFixed(1)}%</span>
                </div>
                <div class="comp-mini-trend ${t}">
                    <i data-lucide="${s}"></i>
                    <span>${e.diferenca>0?"+":""}${e.diferenca.toFixed(1)}pp</span>
                </div>
            </div>
        </div>
    `}function Ue(e){if(!e||e.length===0)return"";const a={Pix:"zap","Cartão de Crédito":"credit-card","Cartão de Débito":"credit-card",Dinheiro:"banknote",Boleto:"file-text",Depósito:"landmark",Transferência:"arrow-right-left",Estorno:"undo-2"},t=e.reduce((r,o)=>r+o.atual,0);return`
        <div class="comparative-card comp-full-width surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3><i data-lucide="wallet"></i> Formas de Pagamento</h3>
                <span class="comp-subtitle">Distribuição mês atual vs anterior</span>
            </div>
            <div class="forma-comp-list">
                ${e.map((r,o)=>{const n=t>0?(r.atual/t*100).toFixed(0):0,l=a[r.nome]||"wallet";return`
            <div class="forma-comp-row" style="animation-delay: ${o*.06}s">
                <div class="forma-comp-icon"><i data-lucide="${l}"></i></div>
                <div class="forma-comp-info">
                    <span class="forma-comp-name">${v(r.nome)}</span>
                    <div class="forma-comp-bar-bg">
                        <div class="forma-comp-bar" style="width: ${n}%"></div>
                    </div>
                </div>
                <div class="forma-comp-values">
                    <span class="forma-comp-current">${w(r.atual)} <small>(${r.atual_qtd}x)</small></span>
                    <span class="forma-comp-prev">${w(r.anterior)} <small>(${r.anterior_qtd}x)</small></span>
                </div>
            </div>
        `}).join("")}
            </div>
        </div>
    `}function le(e,a,t){const s=(c,d=!1)=>c>0?'<i data-lucide="arrow-up"></i>':c<0?'<i data-lucide="arrow-down"></i>':'<i data-lucide="equal"></i>',r=(c,d=!1)=>{if(d){if(c>0)return"trend-negative";if(c<0)return"trend-positive"}else{if(c>0)return"trend-positive";if(c<0)return"trend-negative"}return"trend-neutral"},o=(c,d=!1)=>Math.abs(c)<.1?"Sem alteração":c>0?`Aumentou ${Math.abs(c).toFixed(1)}%`:c<0?`Reduziu ${Math.abs(c).toFixed(1)}%`:"Sem alteração",n=()=>{if(t.includes("mês")){const[c,d]=i.currentMonth.split("-");return new Date(c,d-1).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return i.currentMonth.split("-")[0]},l=()=>{if(t.includes("mês")){const[c,d]=i.currentMonth.split("-");return new Date(c,d-2).toLocaleDateString("pt-BR",{month:"short",year:"numeric"})}else return(parseInt(i.currentMonth.split("-")[0])-1).toString()};return`
        <div class="comparative-card surface-card surface-card--interactive">
            <div class="comparative-header">
                <h3>${v(e)}</h3>
                <div class="period-labels">
                    <span class="period-current"><i data-lucide="calendar" style="color: white;"></i> ${n()}</span>
                    <span class="period-separator">vs</span>
                    <span class="period-previous">${l()}</span>
                </div>
            </div>
            
            <div class="comparative-grid-new">
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="trending-up" class="item-icon revenue"></i>
                        <span class="item-label">RECEITAS</span>
                    </div>
                    <div class="item-values">
                        <div class="value-current">
                            <span class="value-label">Atual</span>
                            <span class="value-amount">${w(a.current.receitas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${w(a.previous.receitas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${r(a.variation.receitas,!1)}">
                        ${s(a.variation.receitas,!1)}
                        <span>${o(a.variation.receitas,!1)}</span>
                    </div>
                </div>
                
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="trending-down" class="item-icon expense"></i>
                        <span class="item-label">DESPESAS</span>
                    </div>
                    <div class="item-values">
                        <div class="value-current">
                            <span class="value-label">Atual</span>
                            <span class="value-amount">${w(a.current.despesas)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${w(a.previous.despesas)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${r(a.variation.despesas,!0)}">
                        ${s(a.variation.despesas,!0)}
                        <span>${o(a.variation.despesas,!0)}</span>
                    </div>
                </div>
                
                <div class="comparative-item-new">
                    <div class="item-header">
                        <i data-lucide="wallet" class="item-icon balance"></i>
                        <span class="item-label">SALDO</span>
                    </div>
                    <div class="item-values">
                        <div class="value-current">
                            <span class="value-label">Atual</span>
                            <span class="value-amount">${w(a.current.saldo)}</span>
                        </div>
                        <div class="value-previous">
                            <span class="value-label">Anterior</span>
                            <span class="value-amount">${w(a.previous.saldo)}</span>
                        </div>
                    </div>
                    <div class="item-trend ${r(a.variation.saldo,!1)}">
                        ${s(a.variation.saldo,!1)}
                        <span>${o(a.variation.saldo,!1)}</span>
                    </div>
                </div>
            </div>
        </div>
    `}function Fe(e){const a=document.getElementById("reportArea");if(!a)return;const t=e.resumo_consolidado&&e.cards&&e.cards.length>0?`
        <div class="consolidated-summary">
            <div class="summary-header">
                <div class="summary-icon">
                    <i data-lucide="credit-card" style="color: white"></i>
                </div>
                <div class="summary-title">
                    <h3>Visão Geral dos Cartões</h3>
                    <p>Resumo consolidado de todos os seus cartões de crédito</p>
                </div>
            </div>
            
            <div class="summary-grid">
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
                        <i data-lucide="file-text" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Total em Faturas</span>
                        <span class="stat-value">${w(e.resumo_consolidado.total_faturas)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #3498db, #2980b9);">
                        <i data-lucide="wallet" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Limite Total</span>
                        <span class="stat-value">${w(e.resumo_consolidado.total_limites)}</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, ${e.resumo_consolidado.utilizacao_geral>70?"#e74c3c, #c0392b":e.resumo_consolidado.utilizacao_geral>50?"#f39c12, #e67e22":"#2ecc71, #27ae60"});">
                        <i data-lucide="pie-chart" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Utilização Geral</span>
                        <span class="stat-value">${e.resumo_consolidado.utilizacao_geral.toFixed(1)}%</span>
                    </div>
                </div>
                
                <div class="summary-stat">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #2ecc71, #27ae60);">
                        <i data-lucide="banknote" style="color: white"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Disponível</span>
                        <span class="stat-value">${w(e.resumo_consolidado.total_disponivel)}</span>
                    </div>
                </div>
            </div>
            
            ${e.resumo_consolidado.melhor_cartao||e.resumo_consolidado.requer_atencao?`
                <div class="summary-insights">
                    ${e.resumo_consolidado.melhor_cartao?`
                        <div class="insight-item success">
                            <i data-lucide="star"></i>
                            <span><strong>Melhor cartão:</strong> ${v(e.resumo_consolidado.melhor_cartao.nome)} (${e.resumo_consolidado.melhor_cartao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.requer_atencao?`
                        <div class="insight-item warning">
                            <i data-lucide="triangle-alert"></i>
                            <span><strong>Requer atenção:</strong> ${v(e.resumo_consolidado.requer_atencao.nome)} (${e.resumo_consolidado.requer_atencao.percentual.toFixed(1)}% de uso)</span>
                        </div>
                    `:""}
                    ${e.resumo_consolidado.total_parcelamentos>0?`
                        <div class="insight-item info">
                            <i data-lucide="calendar-check"></i>
                            <span><strong>${e.resumo_consolidado.total_parcelamentos} parcelamento${e.resumo_consolidado.total_parcelamentos>1?"s":""}</strong> comprometendo ${w(e.resumo_consolidado.valor_parcelamentos)}</span>
                        </div>
                    `:""}
                </div>
            `:""}
        </div>
    `:"";a.innerHTML=`
        <div class="cards-report-container">
            ${t}
            
            <div class="cards-grid">
                ${e.cards&&e.cards.length>0?e.cards.map(s=>{const r=$e(s.cor,"#E67E22");return`
                    <div class="card-item surface-card surface-card--interactive surface-card--clip ${s.status_saude.status}"
                         style="--card-color: ${r}; cursor: pointer;"
                         data-card-id="${s.id||""}"
                         data-card-nome="${v(s.nome)}"
                         data-card-cor="${r}"
                         data-card-month="${i.currentMonth}"
                         data-action="open-card-detail"
                         role="button"
                         tabindex="0">
                        <div class="card-header-gradient">
                            <div class="card-brand">
                                <div class="card-icon-wrapper" style="background: linear-gradient(135deg, ${r}, ${r}99);">
                                    <i data-lucide="credit-card" style="color: white"></i>
                                </div>
                                <div class="card-info">
                                    <h3 class="card-name">${v(s.nome)}</h3>
                                    <div class="card-meta">
                                        ${s.conta?`<span class="card-account"><i data-lucide="landmark"></i> ${v(s.conta)}</span>`:""}
                                        ${s.dia_vencimento?`<span class="card-due"><i data-lucide="calendar"></i> Vence dia ${s.dia_vencimento}</span>`:""}
                                    </div>
                                </div>
                            </div>
                            ${s.status_saude&&(s.status_saude.status==="critico"||s.status_saude.status==="alto_uso")?`
                                <div class="health-indicator ${s.status_saude.status}">
                                    <i data-lucide="triangle-alert"></i>
                                </div>
                            `:""}
                        </div>

                        ${s.historico_6_meses&&s.historico_6_meses.length>0?`
                            <div class="card-trend-compact">
                                <span class="trend-label">ÚLTIMOS 6 MESES</span>
                                <span class="trend-indicator ${s.tendencia}">
                                    ${s.tendencia==="subindo"?"↗":s.tendencia==="caindo"?"↘":"→"} ${s.tendencia==="subindo"?"Em alta":s.tendencia==="caindo"?"Em queda":"Estável"}
                                </span>
                            </div>
                        `:""}

                        ${s.alertas&&s.alertas.length>0?`
                            <div class="card-alerts">
                                ${s.alertas.map(o=>`
                                    <span class="alert-badge alert-${o.type}">
                                        <i data-lucide="${o.type==="danger"?"triangle-alert":o.type==="warning"?"circle-alert":"info"}"></i>
                                        ${v(o.message)}
                                    </span>
                                `).join("")}
                            </div>
                        `:""}


                        <div class="card-balance">
                            <div class="balance-main">
                                <span class="balance-label">FATURA DO MÊS</span>
                                <span class="balance-value">${w(s.fatura_atual||0)}</span>
                                ${s.media_historica>0&&Math.abs(s.fatura_atual-s.media_historica)>1?`
                                    <span class="balance-comparison">
                                        ${s.fatura_atual>s.media_historica?"↑":"↓"} ${(Math.abs(s.fatura_atual-s.media_historica)/s.media_historica*100).toFixed(0)}% vs média
                                    </span>
                                `:""}
                            </div>
                            <div class="balance-grid">
                                <div class="balance-item">
                                    <span class="balance-small-label">Limite</span>
                                    <span class="balance-small-value">${w(s.limite||0)}</span>
                                </div>
                                <div class="balance-item">
                                    <span class="balance-small-label">Disponível</span>
                                    <span class="balance-small-value">${w(s.disponivel||0)}</span>
                                </div>
                            </div>
                        </div>


                        <div class="card-usage-new">
                            <div class="usage-header">
                                <span class="usage-label">UTILIZAÇÃO DO LIMITE</span>
                                <span class="usage-percentage">${(s.percentual||0).toFixed(1)}%</span>
                            </div>
                            <div class="usage-bar-new">
                                <div class="usage-fill-new" 
                                     style="width: ${Math.min(s.percentual||0,100)}%"></div>
                            </div>
                        </div>

                        ${s.parcelamentos&&s.parcelamentos.ativos>0||s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(o=>o.valor>0)?`
                            <div class="card-quick-info">
                                ${s.parcelamentos&&s.parcelamentos.ativos>0?`
                                    <div class="quick-info-item">
                                        <i data-lucide="calendar-check"></i>
                                        <span>${s.parcelamentos.ativos} parcelamento${s.parcelamentos.ativos>1?"s":""}</span>
                                    </div>
                                `:""}
                                ${s.proximos_meses&&s.proximos_meses.length>0&&s.proximos_meses.some(o=>o.valor>0)?`
                                    <div class="quick-info-item">
                                        <i data-lucide="line-chart"></i>
                                        <span>Próximo: ${w(s.proximos_meses.find(o=>o.valor>0)?.valor||0)}</span>
                                    </div>
                                `:""}
                            </div>
                        `:""}
                        
                        <div class="card-footer">
                            <button class="card-action-btn primary full-width" data-action="open-card-detail" data-card-id="${s.id||""}" data-card-nome="${v(s.nome)}" data-card-cor="${r}" data-card-month="${i.currentMonth}" title="Ver relatório detalhado">
                                <i data-lucide="eye"></i>
                                <span>Ver Detalhes</span>
                            </button>
                        </div>
                    </div>
                `}).join(""):`
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i data-lucide="credit-card"></i>
                        </div>
                        <h3>Nenhum cartão de crédito cadastrado</h3>
                        <p>Cadastre seus cartões de crédito para visualizar relatórios detalhados de gastos e parcelamentos.</p>
                    </div>
                `}
            </div>
        </div>
    `,window.lucide&&lucide.createIcons()}const T=e=>_.formatCurrency(e),ve={"arrow-trend-up":"trending-up","arrow-trend-down":"trending-down","arrow-up":"arrow-up","arrow-down":"arrow-down","chart-line":"line-chart","chart-pie":"pie-chart","exclamation-triangle":"triangle-alert","exclamation-circle":"circle-alert","check-circle":"circle-check","info-circle":"info",lightbulb:"lightbulb",star:"star",bolt:"zap",wallet:"wallet","credit-card":"credit-card","calendar-check":"calendar-check",calendar:"calendar",crown:"crown",trophy:"trophy",leaf:"leaf","shield-alt":"shield","money-bill-wave":"banknote","trending-up":"trending-up","trending-down":"trending-down","shield-alert":"shield-alert",gauge:"gauge",target:"target",clock:"clock",receipt:"receipt",calculator:"calculator",layers:"layers","calendar-clock":"calendar-clock","pie-chart":"pie-chart","calendar-range":"calendar-range","list-plus":"list-plus","list-minus":"list-minus","file-text":"file-text","piggy-bank":"piggy-bank",banknote:"banknote"};let q=[];function He(){q.forEach(e=>{try{e.destroy()}catch{}}),q=[]}function We(e,a){if(!e)return;const t=a.saldo||0,s=t>=0?"var(--color-success)":"var(--color-danger)",r=t>=0?"positivo":"negativo";let o=`
        <p class="pulse-text">
            Neste mês você recebeu <strong>${T(a.totalReceitas)}</strong>
            e gastou <strong>${T(a.totalDespesas)}</strong>.
            Seu saldo é <strong style="color:${s}">${r} em ${T(Math.abs(t))}</strong>.
    `;a.totalCartoes>0&&(o+=` Faturas de cartões somam <strong>${T(a.totalCartoes)}</strong>.`),o+="</p>",e.innerHTML=o}function Ye(e,a){if(e){if(a?.insights?.length>0){e.innerHTML=a.insights.map(t=>{const s=ve[t.icon]||t.icon;return`
                <div class="insight-card insight-${t.type} surface-card surface-card--interactive">
                    <div class="insight-icon"><i data-lucide="${s}"></i></div>
                    <div class="insight-content">
                        <h4>${v(t.title)}</h4>
                        <p>${v(t.message)}</p>
                    </div>
                </div>`}).join("");return}e.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>'}}function ze(e,a){if(!e)return;if(!a?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de categorias</p>';return}e.innerHTML="";const t=5,s=a.labels.slice(0,t),r=a.values.slice(0,t).map(Number);if(a.labels.length>t){const n=a.values.slice(t).reduce((l,c)=>l+Number(c),0);s.push("Outros"),r.push(n)}const o=new ApexCharts(e,{chart:{type:"donut",height:220,background:"transparent"},series:r,labels:s,colors:["#E67E22","#2C3E50","#2ECC71","#F39C12","#9B59B6","#1ABC9C"],legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},dataLabels:{enabled:!1},plotOptions:{pie:{donut:{size:"60%"}}},stroke:{show:!1},tooltip:{y:{formatter:n=>T(n)}}});o.render(),q.push(o)}function qe(e,a){if(!e)return;if(!a?.labels?.length){e.innerHTML='<p class="empty-message" style="padding:var(--spacing-6)">Sem dados de movimentação</p>';return}e.innerHTML="";const t=(a.receitas||[]).map(Number),s=(a.despesas||[]).map(Number),r=[],o=[],n=[],l=7;for(let d=0;d<a.labels.length;d+=l){const h=Math.floor(d/l)+1;r.push(`Sem ${h}`),o.push(t.slice(d,d+l).reduce((m,u)=>m+u,0)),n.push(s.slice(d,d+l).reduce((m,u)=>m+u,0))}const c=new ApexCharts(e,{chart:{type:"bar",height:220,background:"transparent",toolbar:{show:!1}},series:[{name:"Receitas",data:o},{name:"Despesas",data:n}],colors:["#2ECC71","#E74C3C"],xaxis:{categories:r,labels:{style:{colors:"var(--color-text-muted)",fontSize:"11px"}},axisBorder:{show:!1},axisTicks:{show:!1}},yaxis:{labels:{style:{fontSize:"10px"},formatter:d=>T(d)}},plotOptions:{bar:{columnWidth:"60%",borderRadius:4}},dataLabels:{enabled:!1},legend:{position:"bottom",fontSize:"11px",labels:{colors:"var(--color-text-muted)"}},grid:{borderColor:"rgba(255,255,255,0.05)"},tooltip:{shared:!0,intersect:!1,y:{formatter:d=>T(d)}}});c.render(),q.push(c)}function je({API:e}){async function a(){const o=document.getElementById("overviewPulse"),n=document.getElementById("overviewInsights"),l=document.getElementById("overviewCategoryChart"),c=document.getElementById("overviewComparisonChart");He();const[d,h,m,u]=await Promise.all([e.fetchSummaryStats(),e.fetchInsightsTeaser(),e.fetchReportDataForType("despesas_por_categoria",{accountId:null}),e.fetchReportDataForType("receitas_despesas_diario",{accountId:null})]);We(o,d),Ye(n,h),ze(l,m),qe(c,u),window.lucide&&lucide.createIcons()}async function t(){const o=document.getElementById("insightsContainer");if(!o)return;const n=window.IS_PRO?await e.fetchInsights():await e.fetchInsightsTeaser();if(!n||!n.insights||n.insights.length===0){o.innerHTML='<p class="empty-message">Nenhum insight disponível no momento</p>';return}const l=n.insights.map(c=>{const d=ve[c.icon]||c.icon;return`
                <div class="insight-card insight-${c.type} surface-card surface-card--interactive">
                    <div class="insight-icon">
                        <i data-lucide="${d}"></i>
                    </div>
                    <div class="insight-content">
                        <h4>${v(c.title)}</h4>
                        <p>${v(c.message)}</p>
                    </div>
                </div>
            `}).join("");if(o.innerHTML=l,!window.IS_PRO&&n.isTeaser){const c=Math.max(0,(n.totalCount||0)-n.insights.length),d=c>0?`Desbloqueie mais ${c} insights com PRO`:"Desbloqueie todos os insights com PRO";o.insertAdjacentHTML("beforeend",`
                <div class="insights-teaser-overlay">
                    <div class="teaser-blur-mask"></div>
                    <div class="teaser-cta">
                        <i data-lucide="crown"></i>
                        <h4>${d}</h4>
                        <p>Tenha uma visão completa da sua saúde financeira com análises detalhadas.</p>
                        <a href="${p.BASE_URL}billing" class="btn-upgrade-cta surface-button surface-button--upgrade">
                            <i data-lucide="crown"></i> Fazer Upgrade
                        </a>
                    </div>
                </div>
            `)}window.lucide&&lucide.createIcons()}async function s(){const o=document.getElementById("comparativesContainer");if(!o)return;const n=await e.fetchComparatives();if(!n){o.innerHTML='<p class="empty-message">Dados de comparação não disponíveis</p>';return}const l=le("Comparativo Mensal",n.monthly,"mês anterior"),c=le("Comparativo Anual",n.yearly,"ano anterior"),d=Be(n.categories||[]),h=Ne(n.evolucao||[]),m=De(n.mediaDiaria),u=Ve(n.taxaEconomia),g=Ue(n.formasPagamento||[]);o.innerHTML=`<div class="comp-top-row">${l}${c}</div><div class="comp-duo-grid">${m}${u}</div>`+d+h+g,window.lucide&&lucide.createIcons(),Pe(n.evolucao||[])}async function r(){const o=await e.fetchSummaryStats(),n=document.getElementById("totalReceitas"),l=document.getElementById("totalDespesas"),c=document.getElementById("saldoMes"),d=document.getElementById("totalCartoes");if(n&&(n.textContent=T(o.totalReceitas||0)),l&&(l.textContent=T(o.totalDespesas||0)),c){const g=o.saldo||0;c.textContent=T(g),c.style.color=g>=0?"var(--color-success)":"var(--color-danger)"}d&&(d.textContent=T(o.totalCartoes||0)),W("trendReceitas",o.totalReceitas,o.prevReceitas,!1),W("trendDespesas",o.totalDespesas,o.prevDespesas,!0),W("trendSaldo",o.saldo,o.prevSaldo,!1),W("trendCartoes",o.totalCartoes,o.prevCartoes,!0);const h=document.getElementById("section-overview");h&&h.classList.contains("active")&&await a();const m=document.getElementById("section-insights");m&&m.classList.contains("active")&&await t();const u=document.getElementById("section-comparativos");u&&u.classList.contains("active")&&await s()}return{updateSummaryCards:r,updateInsightsSection:t,updateOverviewSection:a,updateComparativesSection:s}}function Ge({getReportType:e,showRestrictionAlert:a,handleRestrictedAccess:t}){return async function(){if(!window.IS_PRO)return a("Exportação de relatórios é exclusiva do plano PRO.");const r=e()||"despesas_por_categoria",{value:o}=await Swal.fire({title:"Exportar Relatório",html:`
                <div style="text-align:left;display:flex;flex-direction:column;gap:12px;padding-top:8px;">
                    <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Tipo de Relatório</label>
                    <select id="swalExportType" class="swal2-select" style="width:100%;font-size:0.9rem;">
                        <option value="despesas_por_categoria" ${r==="despesas_por_categoria"?"selected":""}>Despesas por Categoria</option>
                        <option value="receitas_por_categoria" ${r==="receitas_por_categoria"?"selected":""}>Receitas por Categoria</option>
                        <option value="saldo_mensal" ${r==="saldo_mensal"?"selected":""}>Saldo Diário</option>
                        <option value="receitas_despesas_diario" ${r==="receitas_despesas_diario"?"selected":""}>Receitas x Despesas Diário</option>
                        <option value="evolucao_12m" ${r==="evolucao_12m"?"selected":""}>Evolução 12 Meses</option>
                        <option value="receitas_despesas_por_conta" ${r==="receitas_despesas_por_conta"?"selected":""}>Receitas x Despesas por Conta</option>
                        <option value="cartoes_credito" ${r==="cartoes_credito"?"selected":""}>Relatório de Cartões</option>
                        <option value="resumo_anual" ${r==="resumo_anual"?"selected":""}>Resumo Anual</option>
                        <option value="despesas_anuais_por_categoria" ${r==="despesas_anuais_por_categoria"?"selected":""}>Despesas Anuais por Categoria</option>
                        <option value="receitas_anuais_por_categoria" ${r==="receitas_anuais_por_categoria"?"selected":""}>Receitas Anuais por Categoria</option>
                    </select>
                    <label style="font-weight:600;font-size:0.85rem;color:var(--color-text-muted);">Formato</label>
                    <select id="swalExportFormat" class="swal2-select" style="width:100%;font-size:0.9rem;">
                        <option value="pdf">PDF</option>
                        <option value="excel">Excel (.xlsx)</option>
                    </select>
                </div>
            `,showCancelButton:!0,confirmButtonText:"Exportar",cancelButtonText:"Cancelar",confirmButtonColor:"#e67e22",preConfirm:()=>({type:document.getElementById("swalExportType").value,format:document.getElementById("swalExportFormat").value})});if(!o)return;const n=document.getElementById("exportBtn"),l=n?n.innerHTML:"";n&&(n.disabled=!0,n.innerHTML=`
                <div class="spinner" style="width: 1rem; height: 1rem; border-width: 2px;"></div>
                <span>Exportando...</span>
            `);try{const c=o.type,d=o.format,h=new URLSearchParams({type:c,format:d,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]});i.currentAccount&&h.set("account_id",i.currentAccount);const m=await fetch(`${p.BASE_URL}api/reports/export?${h}`,{credentials:"include"});if(await t(m))return;if(!m.ok){let f="Erro ao exportar relatório.";try{const C=await m.json();C?.message?f=C.message:C?.errors&&(f=Object.values(C.errors).flat().join(", "))}catch{}throw new Error(f)}const u=await m.blob(),g=m.headers.get("Content-Disposition"),S=_.extractFilename(g)||(d==="excel"?"relatorio.xlsx":"relatorio.pdf"),y=URL.createObjectURL(u),A=document.createElement("a");A.href=y,A.download=S,document.body.appendChild(A),A.click(),A.remove(),URL.revokeObjectURL(y),typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"success",title:"Relatório exportado!",text:S,showConfirmButton:!1,timer:3e3,timerProgressBar:!0})}catch(c){console.error("Export error:",c);const d=me(c,"Erro ao exportar relatório. Tente novamente.");typeof Swal<"u"?Swal.fire({toast:!0,position:"top-end",icon:"error",title:"Erro ao exportar",text:d,showConfirmButton:!1,timer:3e3}):alert(d)}finally{n&&(n.disabled=!1,n.innerHTML=l)}}}const fe=e=>_.formatMonthLabel(e),D=e=>_.isYearlyView(e),Ke=()=>_.getReportType(),be=()=>_.getActiveCategoryType();function Z(e=i.currentAccount){return e?i.accounts.find(a=>String(a.id)===String(e))?.name||`Conta #${e}`:null}function J(){return D()?`Ano ${i.currentMonth.split("-")[0]}`:fe(i.currentMonth)}function de(){const e=be();return(z[i.currentView]||[]).find(t=>t.value===e)?.label||null}function Qe(e=i.activeSection){return e==="relatorios"||e==="comparativos"}function Ze(e=i.activeSection){return ne[e]||ne.overview}function X(e=i.currentView){return ie[e]||ie[p.VIEWS.CATEGORY]}function we(){try{localStorage.setItem(R.ACTIVE_VIEW,i.currentView),localStorage.setItem(R.CATEGORY_TYPE,i.categoryType),localStorage.setItem(R.ANNUAL_CATEGORY_TYPE,i.annualCategoryType)}catch{}}function ae(){typeof window.openBillingModal=="function"?window.openBillingModal():location.href=`${p.BASE_URL}billing`}async function ye(e){const a=e||Y;window.PlanLimits?.promptUpgrade?await window.PlanLimits.promptUpgrade({context:"relatorios",message:a}):window.LKFeedback?.upgradePrompt?await window.LKFeedback.upgradePrompt({context:"relatorios",message:a}):window.Swal?.fire?(await Swal.fire({title:"Recurso exclusivo",text:a,icon:"info",showCancelButton:!0,confirmButtonText:"Assinar plano Pro",cancelButtonText:"Agora não",reverseButtons:!0,focusConfirm:!0})).isConfirmed&&ae():confirm(`${a}

Deseja ir para a página de planos agora?`)&&ae()}async function k(e){if(!e)return!1;const a=Number(e.status||e?.data?.status||0);if(a===401){const t=encodeURIComponent(location.pathname+location.search);return location.href=`${p.BASE_URL}login?return=${t}`,!0}if(a===403){let t=Y;if(e?.data?.message)t=e.data.message;else if(typeof e?.clone=="function")try{const s=await e.clone().json();s?.message&&(t=s.message)}catch{}return i.accessRestricted||(i.accessRestricted=!0,await ye(t)),E.showPaywall(t),!0}return!1}function Je(e){typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:e,showConfirmButton:!1,timer:4e3,timerProgressBar:!0})}const j={async fetchReportData(){i.lastReportError=null;const e=new AbortController,a=setTimeout(()=>e.abort(),p.FETCH_TIMEOUT);try{const t=await O(`${p.BASE_URL}api/reports`,{type:_.getReportType(),year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1],account_id:i.currentAccount||void 0});return clearTimeout(a),i.accessRestricted=!1,i.lastReportError=null,t.data||t}catch(t){return clearTimeout(a),await k(t)||(i.lastReportError=t.name==="AbortError"?"A requisição demorou demais. Tente novamente em instantes.":"Não foi possível carregar o relatório agora. Verifique a conexão e tente novamente.",console.error("Error fetching report data:",t),Je(me(t,"Erro ao carregar relatório. Verifique sua conexão."))),null}},async fetchReportDataForType(e,a={}){const t=new URLSearchParams({type:e,year:i.currentMonth.split("-")[0],month:i.currentMonth.split("-")[1]}),s=Object.prototype.hasOwnProperty.call(a,"accountId")?a.accountId:i.currentAccount;s&&t.set("account_id",s);try{const r=await O(`${p.BASE_URL}api/reports`,Object.fromEntries(t.entries()));return r.data||r}catch{return null}},async fetchAccounts(){try{const e=await O(`${p.BASE_URL}api/contas`);i.accessRestricted=!1;const a=e.data||e.items||e||[];return(Array.isArray(a)?a:[]).map(t=>({id:Number(t.id),name:t.nome||t.apelido||t.instituicao||`Conta #${t.id}`}))}catch(e){return await k(e)?[]:(console.error("Error fetching accounts:",e),[])}},async fetchSummaryStats(){const[e,a]=i.currentMonth.split("-"),t=new AbortController,s=setTimeout(()=>t.abort(),p.FETCH_TIMEOUT);try{const r=await O(`${p.BASE_URL}api/reports/summary`,{year:e,month:a});return clearTimeout(s),r.data||r}catch(r){return clearTimeout(s),await k(r)?{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0}:(console.error("Error fetching summary stats:",r),{totalReceitas:0,totalDespesas:0,saldo:0,totalCartoes:0})}},async fetchInsights(){const[e,a]=i.currentMonth.split("-"),t=new AbortController,s=setTimeout(()=>t.abort(),p.FETCH_TIMEOUT);try{const r=await O(`${p.BASE_URL}api/reports/insights`,{year:e,month:a});return clearTimeout(s),r.data||r}catch(r){return clearTimeout(s),await k(r)?{insights:[]}:(console.error("Error fetching insights:",r),{insights:[]})}},async fetchInsightsTeaser(){const[e,a]=i.currentMonth.split("-"),t=new AbortController,s=setTimeout(()=>t.abort(),p.FETCH_TIMEOUT);try{const r=await O(`${p.BASE_URL}api/reports/insights-teaser`,{year:e,month:a});return clearTimeout(s),r.data||r}catch(r){return clearTimeout(s),console.error("Error fetching insights teaser:",r),{insights:[],totalCount:0,isTeaser:!0}}},async fetchComparatives(){const[e,a]=i.currentMonth.split("-"),t=new URLSearchParams({year:e,month:a});i.currentAccount&&t.set("account_id",i.currentAccount);const s=new AbortController,r=setTimeout(()=>s.abort(),p.FETCH_TIMEOUT);try{const o=await O(`${p.BASE_URL}api/reports/comparatives`,Object.fromEntries(t.entries()));return clearTimeout(r),o.data||o}catch(o){return clearTimeout(r),await k(o)||console.error("Error fetching comparatives:",o),null}}};M.API=j;const E={setContent(e){const a=document.getElementById("reportArea");a&&(a.innerHTML=e,a.setAttribute("aria-busy","false"),window.lucide&&lucide.createIcons())},showLoading(){const e=document.getElementById("reportArea");e&&(e.setAttribute("aria-busy","true"),e.innerHTML=`
                <div class="lk-loading-state">
                    <i data-lucide="loader-2"></i>
                    <p>Carregando relatório...</p>
                </div>
            `,window.lucide&&lucide.createIcons())},showEmptyState(){const e=Z(),a=X(),t=J(),s=e?`Nenhum dado foi encontrado para ${e} em ${t}.`:`Não há lançamentos suficientes para montar este recorte em ${t}.`;E.setContent(`
            <div class="empty-state report-empty-state">
                <i data-lucide="pie-chart"></i>
                <h3>${v(a.title)}</h3>
                <p>${v(s)}</p>
                <div class="report-state-actions">
                    <a href="${p.BASE_URL}lancamentos" class="empty-cta">
                        <i data-lucide="plus"></i>
                        <span>Adicionar lançamento</span>
                    </a>
                    ${e?`
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Mostrar todas as contas</span>
                        </button>
                    `:""}
                </div>
            </div>
        `)},showErrorState(e){const a=v(e||"Não foi possível carregar este relatório.");E.setContent(`
            <div class="error-state report-error-state">
                <i data-lucide="triangle-alert"></i>
                <p class="error-message">${a}</p>
                <div class="report-state-actions">
                    <button type="button" class="btn btn-primary btn-retry" data-action="retry-report">
                        <i data-lucide="refresh-cw"></i>
                        <span>Tentar novamente</span>
                    </button>
                    ${i.currentAccount?`
                        <button type="button" class="btn btn-secondary" data-action="clear-report-account">
                            <i data-lucide="layers"></i>
                            <span>Voltar para todas as contas</span>
                        </button>
                    `:""}
                </div>
            </div>
        `)},showPaywall(e=Y){const a=document.getElementById("reportArea");if(!a)return;const t=v(e||Y);a.setAttribute("aria-busy","false"),a.innerHTML=`
            <div class="paywall-message" role="alert">
                <i data-lucide="crown" aria-hidden="true"></i>
                <h3>Recurso Premium</h3>
                <p>${t}</p>
                <button type="button" class="btn-upgrade surface-button surface-button--upgrade surface-button--lg" data-action="go-pro">
                    Fazer Upgrade para PRO
                </button>
            </div>
        `,window.lucide&&lucide.createIcons();const s=a.querySelector('[data-action="go-pro"]');s&&s.addEventListener("click",ae)},updateMonthLabel(){const e=document.getElementById("monthLabel");e&&(e.textContent=D()?i.currentMonth.split("-")[0]:fe(i.currentMonth))},updatePageContext(){const e=document.getElementById("reportsContextKicker"),a=document.getElementById("reportsContextTitle"),t=document.getElementById("reportsContextDescription"),s=document.getElementById("reportsContextChips"),r=document.getElementById("reportsContextActions");if(!e||!a||!t||!s||!r)return;const o=Ze(),n=X(),l=J(),c=Z(),d=Qe(),h=de(),m=!window.IS_PRO&&i.activeSection==="insights";e.textContent=o.kicker,a.textContent=i.activeSection==="relatorios"?n.title:o.title,t.textContent=i.activeSection==="relatorios"?n.description:o.description;const u=[`<span class="context-chip surface-chip"><i data-lucide="calendar-range"></i><span>${v(l)}</span></span>`];i.activeSection==="relatorios"&&h&&u.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="filter"></i><span>${v(h)}</span></span>`),c&&d?u.push(`<span class="context-chip surface-chip surface-chip--highlight context-chip-highlight"><i data-lucide="landmark"></i><span>${v(c)}</span></span>`):c&&!d?u.push(`<span class="context-chip surface-chip"><i data-lucide="bookmark"></i><span>Filtro salvo: ${v(c)}</span></span>`):u.push('<span class="context-chip surface-chip"><i data-lucide="layers"></i><span>Consolidado</span></span>'),m&&u.push('<span class="context-chip surface-chip surface-chip--pro context-chip-pro"><i data-lucide="crown"></i><span>Preview PRO</span></span>'),s.innerHTML=u.join(""),r.innerHTML=c?`
            <button type="button" class="context-action-btn surface-button surface-button--subtle" data-action="clear-report-account">
                <i data-lucide="eraser"></i>
                <span>Limpar filtro de conta</span>
            </button>
        `:"",window.lucide&&lucide.createIcons()},updateReportFilterSummary(){const e=document.getElementById("reportFilterSummary"),a=document.getElementById("reportScopeNote");if(!e||!a)return;const t=[`<span class="report-filter-chip surface-chip"><i data-lucide="calendar-range"></i><span>${v(J())}</span></span>`,`<span class="report-filter-chip surface-chip"><i data-lucide="bar-chart-3"></i><span>${v(X().title)}</span></span>`],s=de();s&&t.push(`<span class="report-filter-chip surface-chip"><i data-lucide="filter"></i><span>${v(s)}</span></span>`),i.currentAccount?t.push(`<span class="report-filter-chip surface-chip surface-chip--highlight report-filter-chip-highlight"><i data-lucide="landmark"></i><span>${v(Z())}</span></span>`):t.push('<span class="report-filter-chip surface-chip"><i data-lucide="layers"></i><span>Todas as contas</span></span>'),e.innerHTML=t.join(""),a.classList.remove("hidden"),a.innerHTML=i.currentAccount?'<i data-lucide="info"></i><span>O resumo do topo continua consolidado. O filtro por conta afeta este gráfico e a aba Comparativos.</span>':'<i data-lucide="info"></i><span>Use o filtro de conta para analisar um recorte específico sem perder o consolidado do topo.</span>',window.lucide&&lucide.createIcons()},updateControls(){const e=document.getElementById("typeSelectWrapper"),a=[p.VIEWS.CATEGORY,p.VIEWS.ANNUAL_CATEGORY].includes(i.currentView);e&&(e.classList.toggle("hidden",!a),a&&E.syncTypeSelect());const t=document.getElementById("accountSelectWrapper");t&&t.classList.remove("hidden")},syncTypeSelect(){const e=document.getElementById("reportType");if(!e)return;const a=z[i.currentView];if(!a)return;(e.options.length!==a.length||a.some((s,r)=>e.options[r]?.value!==s.value))&&(e.innerHTML=a.map(s=>`<option value="${s.value}">${s.label}</option>`).join("")),e.value=be()},setActiveTab(e){document.querySelectorAll(".tab-btn").forEach(a=>{const t=a.dataset.view===e;a.classList.toggle("active",t),a.setAttribute("aria-selected",t)})}};M.UI=E;const Xe=()=>j.fetchReportData(),et=()=>E.showLoading(),ee=()=>E.showEmptyState(),tt=e=>E.showErrorState(e),at=()=>E.updateMonthLabel(),B=()=>E.updatePageContext(),N=()=>E.updateReportFilterSummary(),st=()=>E.updateControls(),ot=e=>E.setActiveTab(e),rt=e=>b.renderPie(e),nt=e=>b.renderLine(e),it=e=>b.renderBar(e);async function $(){B(),N(),et(),ct();const e=await Xe();if(!i.accessRestricted){if(i.lastReportError)return tt(i.lastReportError);if(i.currentView===p.VIEWS.CARDS){if(!e||!Array.isArray(e.cards))return ee();Fe(e);return}if(!e||!e.labels||e.labels.length===0)return ee();switch(i.currentView){case p.VIEWS.CATEGORY:case p.VIEWS.ANNUAL_CATEGORY:rt(e);break;case p.VIEWS.BALANCE:case p.VIEWS.EVOLUTION:nt(e);break;case p.VIEWS.COMPARISON:case p.VIEWS.ACCOUNTS:case p.VIEWS.ANNUAL_SUMMARY:it(e);break;default:ee()}ke(e)}}const{updateSummaryCards:ct,updateInsightsSection:lt,updateOverviewSection:dt,updateComparativesSection:ut}=je({API:j}),pt=Ge({getReportType:Ke,showRestrictionAlert:ye,handleRestrictedAccess:k});async function mt(e){e==="overview"?await dt():e==="relatorios"?await $():e==="insights"?await lt():e==="comparativos"&&await ut()}function Ce(){const e=D();if(window.LukratoHeader?.setPickerMode?.(e?"year":"month"),e){const a=window.LukratoHeader?.getYear?.();if(a){const[,t="01"]=i.currentMonth.split("-"),s=String(t).padStart(2,"0");i.currentMonth=`${a}-${s}`}}}function ue(e){i.currentView=e,ot(e),st(),B(),N(),Ce(),we(),$()}function pe(e){i.currentView===p.VIEWS.ANNUAL_CATEGORY?i.annualCategoryType=e:i.categoryType=e,B(),N(),we(),$()}function te(e){i.currentAccount=e||null,B(),N(),$()}function ht(e){!e?.detail?.month||D()||i.currentMonth!==e.detail.month&&(i.currentMonth=e.detail.month,at(),B(),N(),$())}function gt(e){if(!D()||!e?.detail?.year)return;const[,a="01"]=i.currentMonth.split("-"),t=String(a).padStart(2,"0"),s=`${e.detail.year}-${t}`;i.currentMonth!==s&&(i.currentMonth=s,B(),N(),$())}if(!window.__LK_REPORTS_LOADED__){let e=function(){try{const t=localStorage.getItem(R.ACTIVE_VIEW);t&&Object.values(p.VIEWS).includes(t)&&(i.currentView=t);const s=localStorage.getItem(R.CATEGORY_TYPE);s&&z[p.VIEWS.CATEGORY]?.some(o=>o.value===s)&&(i.categoryType=s);const r=localStorage.getItem(R.ANNUAL_CATEGORY_TYPE);r&&z[p.VIEWS.ANNUAL_CATEGORY]?.some(o=>o.value===r)&&(i.annualCategoryType=r)}catch{}};window.__LK_REPORTS_LOADED__=!0;async function a(){Oe(),b.setupDefaults(),i.accounts=await j.fetchAccounts();const t=document.getElementById("accountFilter");t&&i.accounts.forEach(u=>{const g=document.createElement("option");g.value=u.id,g.textContent=u.name,t.appendChild(g)}),e(),document.querySelectorAll(".tab-btn").forEach(u=>{u.addEventListener("click",()=>ue(u.dataset.view))});const s=u=>{i.activeSection=u,document.querySelectorAll(".rel-section-tab").forEach(y=>{y.classList.remove("active"),y.setAttribute("aria-selected","false")}),document.querySelectorAll(".rel-section-panel").forEach(y=>y.classList.remove("active"));const g=document.querySelector(`.rel-section-tab[data-section="${u}"]`);g&&(g.classList.add("active"),g.setAttribute("aria-selected","true"));const S=document.getElementById("section-"+u);S&&S.classList.add("active"),localStorage.setItem(R.ACTIVE_SECTION,u),E.updatePageContext(),mt(u),window.lucide&&window.lucide.createIcons()},r=["comparativos"];document.querySelectorAll(".rel-section-tab").forEach(u=>{u.addEventListener("click",()=>{const g=u.dataset.section;if(!window.IS_PRO&&r.includes(g)){window.PlanLimits?.promptUpgrade?window.PlanLimits.promptUpgrade({context:"relatorios",message:"Esta funcionalidade é exclusiva do plano Pro."}).catch(()=>{}):window.LKFeedback?.upgradePrompt?window.LKFeedback.upgradePrompt({context:"relatorios",message:"Esta funcionalidade é exclusiva do plano Pro."}).catch(()=>{}):Swal.fire({icon:"info",title:"Recurso Premium",html:"Esta funcionalidade é exclusiva do <b>plano Pro</b>.<br>Faça upgrade para desbloquear!",confirmButtonText:'<i class="lucide-crown" style="margin-right:6px"></i> Fazer Upgrade',showCancelButton:!0,cancelButtonText:"Agora não",confirmButtonColor:"#f59e0b",cancelButtonColor:"#64748b"}).then(S=>{S.isConfirmed&&(window.location.href=(window.BASE_URL||"/")+"billing")});return}s(g)})}),E.setActiveTab(i.currentView),E.updateControls(),E.updatePageContext();const o=localStorage.getItem(R.ACTIVE_SECTION);o&&document.getElementById("section-"+o)?!window.IS_PRO&&r.includes(o)?s("overview"):s(o):s("overview");const n=document.getElementById("reportType");n&&n.addEventListener("change",u=>pe(u.target.value)),t&&t.addEventListener("change",u=>te(u.target.value));const l=document.getElementById("btnLimparFiltrosRel"),c=document.getElementById("clearFiltersWrapper"),d=()=>{if(!c)return;const u=n&&n.selectedIndex>0,g=t&&t.value!=="";c.style.display=u||g?"flex":"none"};n&&n.addEventListener("change",d),t&&t.addEventListener("change",d),l&&l.addEventListener("click",()=>{n&&(n.selectedIndex=0,pe(n.value)),t&&(t.value="",te("")),d()}),d(),document.addEventListener("lukrato:theme-changed",()=>{b.setupDefaults(),$()});const h=window.LukratoHeader?.getMonth?.();h&&(i.currentMonth=h),document.addEventListener("lukrato:month-changed",ht),document.addEventListener("lukrato:year-changed",gt);const m=document.getElementById("exportBtn");m&&m.addEventListener("click",pt),document.addEventListener("click",u=>{if(u.target.closest('[data-action="retry-report"]')){u.preventDefault(),$();return}if(u.target.closest('[data-action="clear-report-account"]')){u.preventDefault(),t&&(t.value=""),te(""),d();return}const y=u.target.closest('[data-action="open-card-detail"]');if(!y)return;u.stopPropagation();const A=parseInt(y.dataset.cardId,10),f=y.dataset.cardNome||"",C=y.dataset.cardCor||"#E67E22",L=y.dataset.cardMonth||i.currentMonth;A&&(window.LK_CardDetail?.open?window.LK_CardDetail.open(A,f,C,L):(console.error("[Relatórios] LK_CardDetail module not loaded"),typeof Swal<"u"&&Swal.fire({toast:!0,position:"top-end",icon:"error",title:"Módulo de detalhes não carregado",text:"Recarregue a página.",showConfirmButton:!1,timer:3e3})))}),Ce(),E.updateMonthLabel(),E.updateControls(),$()}document.readyState==="loading"?document.addEventListener("DOMContentLoaded",a):a(),window.ReportsAPI={setMonth:t=>{/^\d{4}-\d{2}$/.test(t)&&(i.currentMonth=t,E.updateMonthLabel(),$())},setView:t=>{Object.values(p.VIEWS).includes(t)&&ue(t)},refresh:()=>$(),getState:()=>({...i})}}
