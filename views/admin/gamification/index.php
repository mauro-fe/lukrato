<?php
$firstName = '';
if (!empty($currentUser->nome)) {
    $firstName = explode(' ', trim($currentUser->nome))[0];
} elseif (!empty($username)) {
    $firstName = explode(' ', trim($username))[0];
}
$firstName = htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8');

$layoutPageCapabilities = isset($pageCapabilities) && is_array($pageCapabilities)
    ? $pageCapabilities
    : [];

$gamificationPageCapabilities = (string) ($layoutPageCapabilities['pageKey'] ?? '') === 'gamification'
    ? $layoutPageCapabilities
    : [];

$gamificationCustomizerCapabilities = is_array($gamificationPageCapabilities['customizer'] ?? null)
    ? $gamificationPageCapabilities['customizer']
    : [];

$gamificationForcedPreferences = is_array($gamificationCustomizerCapabilities['forcedPreferences'] ?? null)
    ? $gamificationCustomizerCapabilities['forcedPreferences']
    : [];

$showGamHeader = (bool) ($gamificationForcedPreferences['toggleGamHeader'] ?? true);
$showGamProgress = (bool) ($gamificationForcedPreferences['toggleGamProgress'] ?? true);
$showGamAchievements = (bool) ($gamificationForcedPreferences['toggleGamAchievements'] ?? true);
$showGamHistory = (bool) ($gamificationForcedPreferences['toggleGamHistory'] ?? true);
$showGamLeaderboard = (bool) ($gamificationForcedPreferences['toggleGamLeaderboard'] ?? true);
$gamificationTrigger = is_array($gamificationCustomizerCapabilities['trigger'] ?? null)
    ? $gamificationCustomizerCapabilities['trigger']
    : [];
$gamificationTriggerLabel = (string) ($gamificationTrigger['label'] ?? 'Personalizar gamificação');
?>
<div class="gamification-page">
    <?php include __DIR__ . '/sections/customize-trigger.php'; ?>
    <?php include __DIR__ . '/sections/header.php'; ?>
    <?php include __DIR__ . '/sections/insight-banner.php'; ?>
    <?php include __DIR__ . '/sections/progress.php'; ?>
    <?php include __DIR__ . '/sections/missions.php'; ?>
    <?php include __DIR__ . '/sections/insight-before-achievements.php'; ?>
    <?php include __DIR__ . '/sections/achievements.php'; ?>
    <?php include __DIR__ . '/sections/history.php'; ?>
    <?php include __DIR__ . '/sections/insight-before-ranking.php'; ?>
    <?php include __DIR__ . '/sections/leaderboard.php'; ?>
    <?php include __DIR__ . '/sections/customize-modal.php'; ?>
</div>

<!-- JS carregado via Vite (loadPageJs) -->