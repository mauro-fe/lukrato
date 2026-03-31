<?php

declare(strict_types=1);

use Application\Core\Router;

// Gamification
Router::add('GET',  '/api/gamification/progress', 'Api\\Gamification\\GamificationController@getProgress', ['auth']);
Router::add('GET',  '/api/gamification/achievements', 'Api\\Gamification\\GamificationController@getAchievements', ['auth']);
Router::add('GET',  '/api/gamification/achievements/pending', 'Api\\Gamification\\GamificationController@getPendingAchievements', ['auth']);
Router::add('GET',  '/api/gamification/stats', 'Api\\Gamification\\GamificationController@getStats', ['auth']);
Router::add('GET',  '/api/gamification/history', 'Api\\Gamification\\GamificationController@getHistory', ['auth']);
Router::add('POST', '/api/gamification/achievements/mark-seen', 'Api\\Gamification\\GamificationController@markAchievementsSeen', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/gamification/leaderboard', 'Api\\Gamification\\GamificationController@getLeaderboard', ['auth']);
Router::add('GET',  '/api/gamification/missions', 'Api\\Gamification\\GamificationController@getMissions', ['auth']);

// Finance goals and budgets
Router::add('GET',    '/api/financas/resumo', 'Api\\Financas\\ResumoController@resumo', ['auth']);
Router::add('GET',    '/api/financas/metas', 'Api\\Metas\\MetasController@index', ['auth']);
Router::add('POST',   '/api/financas/metas', 'Api\\Metas\\MetasController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/financas/metas/{id}', 'Api\\Metas\\MetasController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/financas/metas/{id}/aporte', 'Api\\Metas\\MetasController@aporte', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/financas/metas/{id}', 'Api\\Metas\\MetasController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/financas/metas/templates', 'Api\\Metas\\MetasController@templates', ['auth']);
Router::add('GET',    '/api/financas/orcamentos', 'Api\\Orcamentos\\OrcamentosController@index', ['auth']);
Router::add('POST',   '/api/financas/orcamentos', 'Api\\Orcamentos\\OrcamentosController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/financas/orcamentos/bulk', 'Api\\Orcamentos\\OrcamentosController@bulk', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/financas/orcamentos/{id}', 'Api\\Orcamentos\\OrcamentosController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/financas/orcamentos/sugestoes', 'Api\\Orcamentos\\OrcamentosController@sugestoes', ['auth']);
Router::add('POST',   '/api/financas/orcamentos/aplicar-sugestoes', 'Api\\Orcamentos\\OrcamentosController@aplicarSugestoes', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/financas/orcamentos/copiar-mes', 'Api\\Orcamentos\\OrcamentosController@copiarMes', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/financas/insights', 'Api\\Financas\\ResumoController@insights', ['auth']);
