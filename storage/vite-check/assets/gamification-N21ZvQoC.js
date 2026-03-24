import{b as C,d as c,e as y}from"./api-Dkfcp6ON.js";import{a as A}from"./ui-H2yoVZe7.js";import{e as u}from"./utils-Bj4jxwhy.js";const p=window.GAMIFICATION,m=p.formatNumber.bind(p),h=p.formatDate.bind(p),l=C();function b(e){return{target:"#ef4444",flame:"#f97316",zap:"#eab308",calendar:"#3b82f6","bar-chart-3":"#06b6d4",palette:"#a855f7","user-check":"#22c55e",coins:"#eab308",hash:"#6366f1","graduation-cap":"#3b82f6",star:"#f59e0b",crown:"#f59e0b",gem:"#a855f7",trophy:"#f59e0b",award:"#f59e0b",sparkles:"#ec4899","file-text":"#64748b",library:"#92400e",landmark:"#3b82f6",sparkle:"#ec4899",orbit:"#6366f1",banknote:"#22c55e","piggy-bank":"#ec4899","building-2":"#64748b","trending-up":"#22c55e",crosshair:"#ef4444",medal:"#f59e0b","folder-open":"#f59e0b",folders:"#f59e0b","check-circle":"#22c55e","credit-card":"#3b82f6",receipt:"#14b8a6","calendar-check":"#22c55e",cake:"#ec4899","shield-check":"#22c55e","wand-sparkles":"#a855f7",sunrise:"#f97316",moon:"#6366f1","tree-pine":"#22c55e","party-popper":"#ef4444",swords:"#64748b",rocket:"#ef4444",handshake:"#3b82f6",users:"#3b82f6",megaphone:"#f97316",lock:"#94a3b8",check:"#22c55e"}[e]||"#f97316"}const i={userLevelLarge:document.getElementById("userLevelLarge"),totalPointsCard:document.getElementById("totalPointsCard"),currentLevelCard:document.getElementById("currentLevelCard"),currentStreakCard:document.getElementById("currentStreakCard"),achievementsCountCard:document.getElementById("achievementsCountCard"),nextLevel:document.getElementById("nextLevel"),progressPointsLarge:document.getElementById("progressPointsLarge"),progressFillLarge:document.getElementById("progressFillLarge"),achievementsGridPage:document.getElementById("achievementsGridPage"),pointsHistory:document.getElementById("pointsHistory"),leaderboardContainer:document.getElementById("leaderboardContainer")};let E="all",d=null;async function v(){try{const[e,s,t,a]=await Promise.all([c(`${l}api/gamification/progress`),c(`${l}api/gamification/achievements`),c(`${l}api/gamification/history`,{limit:20}),c(`${l}api/gamification/leaderboard`)]);k(e),L(s),T(t),S(a)}catch(e){console.error("[PAGE] Erro ao carregar dados:",e),A(y(e,"Nao foi possivel carregar os dados da gamificacao"))}}function k(e){if(!(e.success===!0))return;const t=e.data,a=t.current_level||1,n=t.total_points||0,r=t.current_streak||0;i.userLevelLarge&&(i.userLevelLarge.querySelector("span").textContent=`Nível ${a}`),i.totalPointsCard&&(i.totalPointsCard.textContent=n),i.currentLevelCard&&(i.currentLevelCard.textContent=a),i.currentStreakCard&&(i.currentStreakCard.textContent=r);const o=p.calculateProgress(a,n),f=a+1;i.nextLevel&&(i.nextLevel.textContent=o.isMaxLevel?"MAX":f),i.progressPointsLarge&&(i.progressPointsLarge.textContent=o.isMaxLevel?`${m(n)} pontos (Máximo!)`:`${m(o.current)} / ${m(o.needed)}`),i.progressFillLarge&&(i.progressFillLarge.style.width=`${o.percentage}%`)}function L(e){if(!(e.success===!0)||!e.data?.achievements)return;const t=e.data.achievements,n=(e.data.stats||{}).unlocked_count||t.filter(r=>r.unlocked).length;d=t,i.achievementsCountCard&&(i.achievementsCountCard.textContent=`${n}/${t.length}`),g(t)}function g(e){if(!i.achievementsGridPage)return;const s=_(e,E);i.achievementsGridPage.innerHTML=s.map(t=>{const a=t.unlocked;return`
            <div class="${a?"achievement-card unlocked":"achievement-card"}" data-achievement='${JSON.stringify(t).replace(/'/g,"&#39;")}' style="cursor: pointer;">
                <div class="achievement-icon" style="color:${b(t.icon)}"><i data-lucide="${t.icon}"></i></div>
                <div class="achievement-info">
                    <h3 class="achievement-title">${u(t.name)}</h3>
                    <p class="achievement-description">${u(t.description)}</p>
                    <div class="achievement-meta">
                        <span class="achievement-points">+${t.points_reward} pts</span>
                        ${a?`<span class="achievement-date"><i data-lucide="check" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> ${h(t.unlocked_at)}</span>`:'<span class="achievement-locked"><i data-lucide="lock" style="width:14px;height:14px;display:inline-block;vertical-align:middle;"></i> Bloqueada</span>'}
                    </div>
                </div>
            </div>
        `}).join(""),document.querySelectorAll(".achievement-card").forEach(t=>{t.addEventListener("click",function(){const a=JSON.parse(this.dataset.achievement);$(a)})}),window.lucide&&lucide.createIcons()}function _(e,s){switch(s){case"unlocked":return e.filter(t=>t.unlocked);case"locked":return e.filter(t=>!t.unlocked);default:return e}}function T(e){if(!(e.success===!0)||!e.data){i.pointsHistory&&(i.pointsHistory.innerHTML='<p class="empty-state">Nenhuma atividade recente</p>');return}const t=e.data.history||[];if(i.pointsHistory){if(t.length===0){i.pointsHistory.innerHTML='<p class="empty-state">Nenhuma atividade recente</p>';return}i.pointsHistory.innerHTML=t.map(a=>`
        <div class="history-item">
            <div class="history-icon"><i data-lucide="${x(a.action)}"></i></div>
            <div class="history-content">
                <div class="history-title">${a.description||I(a.action)}</div>
                <div class="history-date">${a.relative_time||h(a.created_at)}</div>
            </div>
            <div class="history-points ${a.points>=0?"positive":"negative"}">
                ${a.points>=0?"+":""}${a.points} pts
            </div>
        </div>
    `).join(""),window.lucide&&lucide.createIcons()}}function S(e){if(!(e.success===!0)||!e.data?.leaderboard)return;const t=e.data.leaderboard;if(i.leaderboardContainer){if(t.length===0){i.leaderboardContainer.innerHTML='<p class="empty-state">Nenhum usu&aacute;rio no ranking</p>';return}i.leaderboardContainer.innerHTML=`
        <table class="leaderboard-table">
            <thead><tr><th>Posi&ccedil;&atilde;o</th><th>Usu&aacute;rio</th><th>N&iacute;vel</th><th>Pontos</th></tr></thead>
            <tbody>
                ${t.map(a=>{const n=a.position<=3?`rank-${a.position}`:"",r=a.position===1?'<i data-lucide="medal" style="color:#fbbf24;"></i>':a.position===2?'<i data-lucide="medal" style="color:#94a3b8;"></i>':a.position===3?'<i data-lucide="medal" style="color:#d97706;"></i>':"",o=(a.user_name||"").trim().split(" ").slice(0,2).join(" "),f=a.avatar?`<img src="${u(a.avatar)}" alt="" class="leaderboard-avatar">`:`<span class="leaderboard-avatar leaderboard-avatar-fallback">${u((o||"U")[0].toUpperCase())}</span>`;return`
                        <tr class="${n}">
                            <td class="rank-cell" data-label="Posicao">
                                <span class="rank-pill">${r}<span>${a.position}&ordm;</span></span>
                            </td>
                            <td class="user-cell" data-label="Usuario">
                                <div class="user-info">
                                    ${f}
                                    <div class="user-meta">
                                        <strong>${u(o)}</strong>
                                        <span class="user-meta-label">Ranking global</span>
                                    </div>
                                </div>
                            </td>
                            <td class="level-cell" data-label="Nivel">
                                <span class="level-badge">N&iacute;vel ${a.current_level}</span>
                            </td>
                            <td class="points-cell" data-label="Pontos">
                                <strong>${m(a.total_points)}</strong>
                                <span>pts</span>
                            </td>
                        </tr>
                    `}).join("")}
            </tbody>
        </table>
    `,window.lucide&&lucide.createIcons()}}function $(e){if(typeof Swal>"u")return;const s=e.unlocked||e.unlocked_ever;let t="";e.unlocked?t=`<p style="color: #10b981; font-weight: 600; margin-top: 15px;"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Desbloqueada${e.unlocked_at?` em ${h(e.unlocked_at)}`:""}</p>`:e.unlocked_ever?t='<p style="color: #10b981; font-weight: 600; margin-top: 15px;"><i data-lucide="check" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Conquistada anteriormente</p>':t='<p style="color: #94a3b8; font-weight: 600; margin-top: 15px;"><i data-lucide="lock" style="width:16px;height:16px;display:inline-block;vertical-align:middle;"></i> Ainda não desbloqueada</p>';const a=e.is_pro_only?'<p style="color: #f59e0b; font-weight: 600; margin-top: 10px;"><i data-lucide="gem"></i> Conquista exclusiva PRO</p>':"";Swal.fire({title:e.name,html:`
            <div style="font-size:2.5rem;margin-bottom:10px;color:${b(e.icon)}"><i data-lucide="${e.icon}"></i></div>
            <p style="font-size: 16px; color: #64748b; margin-bottom: 15px;">${e.description}</p>
            <p style="font-size: 18px; color: #f59e0b; font-weight: 700;">
                <i data-lucide="star" style="width:18px;height:18px;display:inline-block;vertical-align:middle;"></i> ${e.points_reward} pontos
            </p>
            ${a}
            ${t}
        `,icon:s?"success":"info",confirmButtonText:"Fechar",confirmButtonColor:"#f97316",customClass:{popup:"achievement-modal"},didOpen:()=>{window.lucide&&lucide.createIcons()}})}function I(e){return{CREATE_LANCAMENTO:"Criou lançamento",CREATE_CATEGORIA:"Criou categoria",VIEW_REPORT:"Visualizou relatório",CREATE_META:"Criou meta",CLOSE_MONTH:"Fechou mês",DAILY_ACTIVITY:"Atividade diária",STREAK_3_DAYS:"Sequência de 3 dias",STREAK_7_DAYS:"Sequência de 7 dias",STREAK_30_DAYS:"Sequência de 30 dias",POSITIVE_MONTH:"Mês positivo",LEVEL_UP:"Subiu de nível"}[e]||e}function x(e){return{LAUNCH_CREATED:"coins",LAUNCH_EDITED:"pencil",LAUNCH_DELETED:"trash-2",CREATE_LANCAMENTO:"coins",FIRST_LAUNCH_DAY:"sunrise",CREATE_CATEGORIA:"tag",CATEGORY_CREATED:"tag",DAILY_LOGIN:"hand",DAILY_ACTIVITY:"check-circle",VIEW_REPORT:"bar-chart-3",CREATE_META:"target",META_ACHIEVED:"trophy",CLOSE_MONTH:"calendar",POSITIVE_MONTH:"heart",STREAK_BONUS:"flame",STREAK_3_DAYS:"flame",STREAK_7_DAYS:"flame",STREAK_30_DAYS:"flame",LEVEL_UP:"star",ACHIEVEMENT_UNLOCKED:"medal",CARD_CREATED:"credit-card",INVOICE_PAID:"receipt"}[e]||"circle-dot"}document.querySelectorAll(".filter-btn").forEach(e=>{e.addEventListener("click",function(){document.querySelectorAll(".filter-btn").forEach(s=>s.classList.remove("active")),this.classList.add("active"),E=this.dataset.filter,d?g(d):c(`${l}api/gamification/achievements`).then(s=>{s.data?.achievements&&(d=s.data.achievements,g(d))})})});document.readyState==="loading"?document.addEventListener("DOMContentLoaded",v):v();
