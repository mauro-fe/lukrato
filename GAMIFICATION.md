# 🎮 Sistema de Gamificação - Lukrato

## 📋 Visão Geral

O Lukrato agora possui um **sistema completo de gamificação** para aumentar o engajamento dos usuários. Através de pontos, níveis, conquistas e streaks, transformamos o controle financeiro em uma experiência mais motivadora e divertida.

---

## ✨ Funcionalidades Principais

### 1. **Sistema de Pontos**

Usuários ganham pontos por diversas ações:

| Ação             | Pontos  | Descrição                                  |
| ---------------- | ------- | ------------------------------------------ |
| Criar Lançamento | 5 pts   | Registrar uma receita ou despesa           |
| Criar Categoria  | 10 pts  | Criar uma nova categoria personalizada     |
| Atividade Diária | 10 pts  | Acesso diário ao sistema (uma vez por dia) |
| Streak 7 Dias    | 30 pts  | Manter atividade por 7 dias consecutivos   |
| Streak 30 Dias   | 100 pts | Manter atividade por 30 dias consecutivos  |
| Mês Positivo     | 50 pts  | Terminar o mês com saldo positivo          |
| Subir de Nível   | 0 pts\* | \*Bônus indireto das conquistas            |

### 2. **Sistema de Níveis**

Progressão de 5 níveis baseada em pontos acumulados:

| Nível      | Pontos Necessários | Badge             |
| ---------- | ------------------ | ----------------- |
| 🥉 Nível 1 | 0 pts              | Iniciante         |
| 🥈 Nível 2 | 100 pts            | Aprendiz          |
| 🥇 Nível 3 | 250 pts            | Intermediário     |
| 💎 Nível 4 | 500 pts            | Avançado          |
| 👑 Nível 5 | 1000 pts           | Mestre Financeiro |

### 3. **Sistema de Streaks** 🔥

Recompensa consistência com:

- **Streak Atual**: Dias consecutivos com atividade
- **Melhor Streak**: Recorde histórico do usuário
- **Bônus de Marcos**: Pontos extras aos 7 e 30 dias consecutivos
- **Reset Automático**: Streak zera se pular um dia

### 4. **Conquistas Desbloqueáveis** 🏆

#### Conquistas de Início

- **🚀 Primeiro Passo** (20 pts) - Registre seu primeiro lançamento
- **📂 Organizador** (50 pts) - Crie 10 categorias personalizadas

#### Conquistas de Consistência

- **🔥 Semana de Fogo** (50 pts) - Mantenha streak de 7 dias
- **⚡ Mês Imparável** (150 pts) - Mantenha streak de 30 dias

#### Conquistas Financeiras

- **💰 Mês no Verde** (75 pts) - Termine um mês com saldo positivo
- **📈 Saldo Positivo** (80 pts) - Mantenha saldo geral positivo

#### Conquistas de Progresso

- **👑 Mestre Financeiro** (200 pts) - Alcance o nível 5
- **📊 Contador Expert** (100 pts) - Registre 100 lançamentos

---

## 🛠️ Arquitetura Técnica

### Estrutura de Tabelas

```sql
user_progress
├── user_id (FK → usuarios)
├── total_points
├── current_level (1-5)
├── points_to_next_level
├── current_streak
├── best_streak
└── last_activity_date

achievements
├── id
├── code (unique)
├── name
├── description
├── icon (FontAwesome)
├── points_reward
├── category
└── active

user_achievements
├── user_id (FK → usuarios)
├── achievement_id (FK → achievements)
├── unlocked_at
└── notification_seen

points_log
├── user_id (FK → usuarios)
├── action
├── points
├── description
├── metadata (JSON)
├── related_id
└── related_type
```

### Componentes do Sistema

#### 📁 **Models** (`Application/Models/`)

- `UserProgress.php` - Progresso individual do usuário
- `Achievement.php` - Catálogo de conquistas
- `UserAchievement.php` - Conquistas desbloqueadas
- `PointsLog.php` - Histórico completo de pontos

#### 📁 **Enums** (`Application/Enums/`)

- `GamificationAction.php` - 7 ações que geram pontos
- `AchievementType.php` - 8 tipos de conquistas

#### 📁 **Services** (`Application/Services/`)

- `GamificationService.php` - Lógica central (~500 linhas)
  - `addPoints()` - Adicionar pontos com anti-duplicação
  - `updateStreak()` - Gerenciar streaks diários
  - `recalculateLevel()` - Calcular progressão de nível
  - `checkAchievements()` - Verificar conquistas desbloqueáveis
  - `unlockAchievement()` - Desbloquear e premiar conquista

#### 📁 **Controllers** (`Application/Controllers/Api/`)

- `LancamentosController.php` - Integrado com gamificação
- `CategoriaController.php` - Integrado com gamificação
- `GamificationController.php` - 4 endpoints dedicados

---

## 🌐 API Endpoints

### **GET** `/api/gamification/progress`

Retorna progresso completo do usuário.

**Resposta:**

```json
{
  "success": true,
  "data": {
    "total_points": 145,
    "current_level": 2,
    "points_to_next_level": 105,
    "progress_percentage": 45,
    "current_streak": 7,
    "best_streak": 12,
    "last_activity_date": "2024-12-24"
  }
}
```

### **GET** `/api/gamification/achievements`

Lista todas as conquistas com status de desbloqueio.

**Resposta:**

```json
{
  "success": true,
  "data": {
    "achievements": [
      {
        "id": 1,
        "code": "first_launch",
        "name": "Primeiro Passo",
        "description": "Registre seu primeiro lançamento financeiro",
        "icon": "fa-rocket",
        "points_reward": 20,
        "category": "usage",
        "unlocked": true,
        "unlocked_at": "2024-12-24 10:30:15",
        "notification_seen": false
      }
    ],
    "stats": {
      "total_achievements": 8,
      "unlocked_count": 3,
      "completion_percentage": 37.5
    }
  }
}
```

### **POST** `/api/gamification/achievements/mark-seen`

Marca conquistas como vistas (remove badge "NEW").

**Request:**

```json
{
  "achievement_ids": [1, 2, 3]
}
```

**Resposta:**

```json
{
  "success": true,
  "data": {
    "marked_count": 3
  }
}
```

### **GET** `/api/gamification/leaderboard`

Retorna ranking dos top 10 usuários.

**Resposta:**

```json
{
  "success": true,
  "data": {
    "leaderboard": [
      {
        "position": 1,
        "user_id": 5,
        "user_name": "João Silva",
        "total_points": 2340,
        "current_level": 5,
        "best_streak": 45
      }
    ],
    "user_position": 23
  }
}
```

---

## 🔧 Integração nos Controllers

### Exemplo: `LancamentosController@store`

```php
// Após criar o lançamento com sucesso
$gamificationResult = [];
try {
    $gamificationService = new GamificationService();

    // Adicionar pontos por criar lançamento
    $pointsResult = $gamificationService->addPoints(
        $this->userId,
        GamificationAction::CREATE_LANCAMENTO,
        $lancamento->id,
        'lancamento'
    );

    // Atualizar streak diário
    $streakResult = $gamificationService->updateStreak($this->userId);

    $gamificationResult = [
        'points' => $pointsResult,
        'streak' => $streakResult,
    ];
} catch (\Exception $e) {
    error_log("🎮 [GAMIFICATION] Erro: " . $e->getMessage());
}

// Retornar com dados de gamificação
Response::success([
    'lancamento' => $lancamento->fresh(),
    'gamification' => $gamificationResult,
], 'Lançamento criado com sucesso', 201);
```

---

## 🎨 Guia de Integração Frontend

### 1. **Badge de Pontos e Nível**

Exibir no cabeçalho/navbar:

```javascript
// Buscar progresso
const response = await fetch("/api/gamification/progress");
const { data } = await response.json();

// Exibir badge
const badge = `
  <div class="gamification-badge">
    <span class="level">Nível ${data.current_level}</span>
    <span class="points">${data.total_points} pts</span>
  </div>
`;
```

### 2. **Indicador de Streak**

Exibir com ícone de fogo:

```javascript
const streakIndicator = `
  <div class="streak-indicator">
    🔥 ${data.current_streak} dias consecutivos
    <small>(Recorde: ${data.best_streak} dias)</small>
  </div>
`;
```

### 3. **Barra de Progresso**

Mostrar evolução para próximo nível:

```javascript
const progressBar = `
  <div class="progress-bar">
    <div class="progress-fill" style="width: ${
      data.progress_percentage
    }%"></div>
    <span class="progress-text">
      ${data.progress_percentage}% para Nível ${data.current_level + 1}
    </span>
  </div>
`;
```

### 4. **Notificações de Conquistas**

Exibir toast quando houver novas conquistas:

```javascript
// Ao criar lançamento/categoria
const { gamification } = responseData;

if (gamification.points?.new_achievements?.length > 0) {
  gamification.points.new_achievements.forEach((achievement) => {
    showToast({
      title: "🏆 Nova Conquista!",
      message: `${achievement.name} (+${achievement.points_reward} pts)`,
      type: "success",
      duration: 5000,
    });
  });
}

// Se subiu de nível
if (gamification.points?.level_up) {
  showToast({
    title: "🎉 Subiu de Nível!",
    message: `Você alcançou o Nível ${gamification.points.level}!`,
    type: "success",
    duration: 5000,
  });
}

// Se ganhou pontos
if (gamification.points?.points_gained > 0) {
  showMiniNotification(`+${gamification.points.points_gained} pts`);
}
```

### 5. **Modal de Conquistas**

Página/modal dedicado para exibir todas as conquistas:

```javascript
const response = await fetch("/api/gamification/achievements");
const { achievements, stats } = await response.json().then((r) => r.data);

achievements.forEach((achievement) => {
  const card = `
    <div class="achievement-card ${
      achievement.unlocked ? "unlocked" : "locked"
    }">
      <i class="fas ${achievement.icon}"></i>
      <h3>${achievement.name}</h3>
      <p>${achievement.description}</p>
      <span class="points">+${achievement.points_reward} pts</span>
      ${
        achievement.notification_seen === false
          ? '<span class="badge-new">NEW</span>'
          : ""
      }
    </div>
  `;
});

// Exibir estatísticas
const stats = `
  <div class="achievements-stats">
    Conquistas Desbloqueadas: ${stats.unlocked_count}/${stats.total_achievements}
    (${stats.completion_percentage}%)
  </div>
`;
```

### 6. **Ranking/Leaderboard**

Exibir top usuários:

```javascript
const response = await fetch("/api/gamification/leaderboard");
const { leaderboard, user_position } = await response
  .json()
  .then((r) => r.data);

leaderboard.forEach((user, index) => {
  const row = `
    <tr class="${user.user_id === currentUserId ? "current-user" : ""}">
      <td>${user.position}º</td>
      <td>${user.user_name}</td>
      <td>Nível ${user.current_level}</td>
      <td>${user.total_points} pts</td>
      <td>🔥 ${user.best_streak} dias</td>
    </tr>
  `;
});
```

---

## 🧪 Testes

### Script CLI de Teste

Execute o script completo de validação:

```bash
php cli/test_gamification.php
```

**O script testa:**

- ✅ Estrutura do banco de dados
- ✅ Criação/recuperação de progresso do usuário
- ✅ Adição de pontos por diferentes ações
- ✅ Sistema de streaks
- ✅ Desbloqueio de conquistas
- ✅ Anti-duplicação de pontos
- ✅ Histórico de pontos (audit trail)
- ✅ Progressão de níveis
- ✅ Integridade de dados

### Testando Endpoints

Use o terminal ou Postman:

```bash
# Progresso do usuário
curl -X GET http://localhost/api/gamification/progress \
  -H "Cookie: session_token=..."

# Conquistas
curl -X GET http://localhost/api/gamification/achievements \
  -H "Cookie: session_token=..."

# Leaderboard
curl -X GET http://localhost/api/gamification/leaderboard \
  -H "Cookie: session_token=..."
```

---

## 📊 Métricas e Analytics

### Queries Úteis

**Usuários mais engajados:**

```sql
SELECT u.nome, up.total_points, up.current_level, up.current_streak
FROM user_progress up
JOIN usuarios u ON u.id = up.user_id
ORDER BY up.total_points DESC
LIMIT 10;
```

**Conquistas mais desbloqueadas:**

```sql
SELECT a.name, COUNT(*) as unlocks
FROM user_achievements ua
JOIN achievements a ON a.id = ua.achievement_id
GROUP BY a.id
ORDER BY unlocks DESC;
```

**Distribuição de níveis:**

```sql
SELECT current_level, COUNT(*) as users
FROM user_progress
GROUP BY current_level
ORDER BY current_level;
```

---

## 🔮 Próximas Melhorias (Roadmap)

### Curto Prazo

- [ ] Sistema de recompensas tangíveis (descontos, recursos premium)
- [ ] Notificações push quando conquistar achievements
- [ ] Compartilhamento social de conquistas
- [ ] Badges visuais customizados

### Médio Prazo

- [ ] Desafios semanais/mensais
- [ ] Torneios entre usuários
- [ ] Sistema de XP separado de pontos
- [ ] Títulos e ranks especiais

### Longo Prazo

- [ ] Sistema de clãs/grupos
- [ ] Missões diárias personalizadas
- [ ] Loja de itens cosméticos
- [ ] Temporadas competitivas

---

## 🛡️ Segurança e Performance

### Anti-Duplicação

- ✅ Verificação por `(user_id, action, related_id, related_type, date)`
- ✅ Ações diárias limitadas a uma vez por dia
- ✅ Logs completos de todas as transações

### Performance

- ✅ Índices em colunas críticas (`user_id`, `total_points`, `current_level`)
- ✅ Queries otimizadas com Eloquent
- ✅ Cache de progresso (considerar implementação futura)
- ✅ Try-catch para não quebrar funcionalidade principal

### Auditoria

- ✅ Tabela `points_log` registra todas as mudanças
- ✅ Metadados JSON para contexto adicional
- ✅ Timestamps automáticos
- ✅ Rastreabilidade completa

---

## 📝 Licença

Este sistema é parte integrante do **Lukrato** e segue a mesma licença do projeto principal.

---

## 👨‍💻 Autores

- **Equipe Lukrato** - Desenvolvimento inicial
- **Contribuidores** - Melhorias contínuas

---

## 📞 Suporte

Para dúvidas ou problemas com o sistema de gamificação:

- Abra uma issue no repositório
- Entre em contato com a equipe de desenvolvimento
- Consulte a documentação técnica completa

---

**🎮 Transforme finanças em diversão com Lukrato!**
