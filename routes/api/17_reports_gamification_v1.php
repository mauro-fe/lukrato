<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for reports and gamification flows used across admin.
 */

Router::add('GET', '/api/v1/reports', 'Api\Report\RelatoriosController@index', ['auth']);
Router::add('GET', '/api/v1/reports/summary', 'Api\Report\RelatoriosController@summary', ['auth']);
Router::add('GET', '/api/v1/reports/insights', 'Api\Report\RelatoriosController@insights', ['auth']);
Router::add('GET', '/api/v1/reports/insights-teaser', 'Api\Report\RelatoriosController@insightsTeaser', ['auth']);
Router::add('GET', '/api/v1/reports/comparatives', 'Api\Report\RelatoriosController@comparatives', ['auth']);
Router::add('GET', '/api/v1/reports/card-details/{id}', 'Api\Report\RelatoriosController@cardDetails', ['auth']);
Router::add('GET', '/api/v1/reports/export', 'Api\Report\RelatoriosController@export', ['auth', 'ratelimit']);

Router::add('GET', '/api/v1/gamification/progress', 'Api\Gamification\GamificationController@getProgress', ['auth']);
Router::add('GET', '/api/v1/gamification/achievements', 'Api\Gamification\GamificationController@getAchievements', ['auth']);
Router::add('GET', '/api/v1/gamification/achievements/pending', 'Api\Gamification\GamificationController@getPendingAchievements', ['auth']);
Router::add('GET', '/api/v1/gamification/stats', 'Api\Gamification\GamificationController@getStats', ['auth']);
Router::add('GET', '/api/v1/gamification/history', 'Api\Gamification\GamificationController@getHistory', ['auth']);
Router::add('POST', '/api/v1/gamification/achievements/mark-seen', 'Api\Gamification\GamificationController@markAchievementsSeen', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/gamification/leaderboard', 'Api\Gamification\GamificationController@getLeaderboard', ['auth']);
Router::add('GET', '/api/v1/gamification/missions', 'Api\Gamification\GamificationController@getMissions', ['auth']);
