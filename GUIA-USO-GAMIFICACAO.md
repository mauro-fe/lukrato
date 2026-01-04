# 🚀 GUIA DE USO - SISTEMA DE GAMIFICAÇÃO

## 📝 INSTRUÇÕES DE INSTALAÇÃO

### 1. Executar Migrations

Execute os comandos via terminal no diretório do projeto:

```bash
# Windows (PowerShell)
cd c:\xampp\htdocs\lukrato
php database\migrations\2026_01_04_add_streak_protection_fields.php
php database\migrations\2026_01_04_add_plan_type_to_achievements.php
php database\migrations\seed_achievements.php
```

### 2. Verificar Arquivos

Certifique-se de que os seguintes arquivos foram criados/atualizados:

#### Backend:

- `Application/Enums/GamificationAction.php`
- `Application/Enums/AchievementType.php`
- `Application/Services/GamificationService.php`
- `Application/Services/StreakService.php` (NOVO)
- `Application/Services/AchievementService.php` (NOVO)
- `Application/Models/UserProgress.php`
- `Application/Models/Achievement.php`
- `Application/Controllers/Api/GamificationController.php`

#### Frontend:

- `views/admin/dashboard/index.php`
- `views/admin/partials/header.php`
- `public/assets/css/gamification.css` (NOVO)
- `public/assets/js/gamification-dashboard.js` (NOVO)

### 3. Limpar Cache (se aplicável)

```bash
# Limpar cache de views se o sistema usar cache
php cli/clear_cache.php
```

---

## 🎮 COMO USAR

### Para Desenvolvedores

#### Adicionar Pontos Manualmente

```php
use Application\Services\GamificationService;
use Application\Enums\GamificationAction;

$gamificationService = new GamificationService();

// Adicionar pontos por criar lançamento
$result = $gamificationService->addPoints(
    $userId,
    GamificationAction::CREATE_LANCAMENTO,
    $lancamentoId,
    'lancamento'
);

// Retorna:
// [
//     'success' => true,
//     'points_gained' => 15,  // (Pro: 15, Free: 10)
//     'total_points' => 350,
//     'level' => 2,
//     'level_up' => true,     // Se subiu de nível
//     'new_achievements' => [...],
// ]
```

#### Atualizar Streak

```php
use Application\Services\StreakService;

$streakService = new StreakService();

$result = $streakService->updateStreak($userId);

// Retorna:
// [
//     'success' => true,
//     'streak' => 5,
//     'best_streak' => 10,
//     'was_consecutive' => true,
//     'used_protection' => false,
//     'message' => 'Streak atualizado para 5 dias 🔥'
// ]
```

#### Verificar Conquistas

```php
use Application\Services\AchievementService;

$achievementService = new AchievementService();

// Verificar e desbloquear automaticamente
$newAchievements = $achievementService->checkAndUnlockAchievements($userId);

// Retorna array de conquistas desbloqueadas:
// [
//     [
//         'id' => 1,
//         'code' => 'FIRST_LAUNCH',
//         'name' => 'Início',
//         'description' => 'Registre seu primeiro lançamento',
//         'icon' => '🎯',
//         'points_reward' => 20
//     ]
// ]
```

#### Obter Dados de Gamificação

```php
use Application\Models\UserProgress;

$progress = UserProgress::where('user_id', $userId)->first();

// Acessar propriedades:
$progress->total_points;
$progress->current_level;
$progress->current_streak;
$progress->best_streak;
$progress->progress_percentage; // Calculado automaticamente
```

---

### Para Frontend

#### Notificar Ganho de Pontos

```javascript
// Após criar lançamento via AJAX
window.notifyPointsGained(15, "Lançamento criado!");
```

#### Notificar Conquista Desbloqueada

```javascript
// Quando API retornar nova conquista
window.notifyAchievementUnlocked({
  icon: "🎯",
  name: "Início",
  description: "Primeiro lançamento criado",
  points_reward: 20,
});
```

#### Notificar Subida de Nível

```javascript
// Quando usuário subir de nível
window.notifyLevelUp(3);
```

---

## 🔗 INTEGRAÇÃO COM CONTROLLERS EXISTENTES

### LancamentoController

Adicione após criar um lançamento com sucesso:

```php
// No método store() ou create()
if ($lancamento) {
    // Adicionar pontos
    $gamificationService = new \Application\Services\GamificationService();
    $gamificationService->addPoints(
        $this->userId,
        \Application\Enums\GamificationAction::CREATE_LANCAMENTO,
        $lancamento->id,
        'lancamento'
    );

    // Atualizar streak
    $streakService = new \Application\Services\StreakService();
    $streakService->updateStreak($this->userId);

    // Verificar conquistas
    $achievementService = new \Application\Services\AchievementService();
    $achievementService->checkAndUnlockAchievements($this->userId);
}
```

### CategoriaController

```php
// Após criar categoria
$gamificationService->addPoints(
    $this->userId,
    \Application\Enums\GamificationAction::CREATE_CATEGORIA,
    $categoria->id,
    'categoria'
);
```

### RelatoriosController

```php
// Ao visualizar relatório
$gamificationService->addPoints(
    $this->userId,
    \Application\Enums\GamificationAction::VIEW_REPORT
);
```

---

## 🎯 ENDPOINTS DISPONÍVEIS

### GET /api/gamification/progress

Retorna progresso completo do usuário.

**Resposta:**

```json
{
  "success": true,
  "data": {
    "total_points": 350,
    "current_level": 2,
    "points_to_next_level": 150,
    "progress_percentage": 60.0,
    "current_streak": 5,
    "best_streak": 10,
    "last_activity_date": "2026-01-04",
    "is_pro": true,
    "streak_protection_available": true,
    "streak_protection_used": false
  }
}
```

### GET /api/gamification/achievements

Lista todas as conquistas com status.

### GET /api/gamification/stats

Estatísticas gerais do usuário.

### GET /api/gamification/leaderboard

Ranking dos top 10 usuários.

---

## 🧪 TESTES

### Teste 1: Criar Primeiro Lançamento

1. Acesse o dashboard
2. Crie um lançamento
3. **Esperado:**
   - Ganhar 10 pontos (Free) ou 15 pontos (Pro)
   - Streak = 1
   - Conquista "Início" desbloqueada
   - Toast de notificação aparece

### Teste 2: Streak Consecutivo

1. Dia 1: Criar lançamento
2. Dia 2: Criar lançamento
3. Dia 3: Criar lançamento
4. **Esperado:**
   - Streak = 3
   - Conquista "3 Dias Seguidos" desbloqueada

### Teste 3: Proteção Pro

1. Faça upgrade para Pro
2. Crie lançamentos em dias alternados (pule 1 dia)
3. **Esperado:**
   - Streak mantido com proteção
   - Badge "Proteção disponível" visível
   - Após usar, badge desaparece até próximo mês

### Teste 4: Subida de Nível

1. Acumule 300 pontos
2. **Esperado:**
   - Subir para nível 2
   - Modal "⭐ Subiu de Nível!" aparece
   - Badge de nível atualizado

---

## 🔧 TROUBLESHOOTING

### Pontos não aparecem no dashboard

1. Verificar se o JavaScript está carregando:

   ```javascript
   console.log("Gamification loaded");
   ```

2. Verificar no console do navegador se há erros

3. Verificar se a rota da API está funcionando:
   ```
   GET https://seusite.com/api/gamification/progress
   ```

### Conquistas não desbloqueiam

1. Verificar se as conquistas foram populadas no banco:

   ```sql
   SELECT * FROM achievements;
   ```

2. Verificar se o AchievementService está sendo chamado:
   ```php
   error_log('Checking achievements for user: ' . $userId);
   ```

### Streak reseta incorretamente

1. Verificar timezone do servidor
2. Verificar se `last_activity_date` está sendo salvo corretamente
3. Para Pro, verificar se `streak_protection_available` está correto

---

## 📞 SUPORTE

Para dúvidas ou problemas:

1. Verifique o `storage/logs/app-YYYY-MM-DD.log` para erros
2. Consulte a documentação em `GAMIFICACAO-IMPLEMENTACAO-COMPLETA.md`
3. Revise o código comentado nos services

---

**Bom uso! 🎮🚀**
