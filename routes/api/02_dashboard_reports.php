<?php

declare(strict_types=1);

use Application\Core\Router;

// Dashboard
Router::add('GET', '/api/dashboard/metrics',      'Api\\Financas\\MetricsController@metrics',      ['auth']);
Router::add('GET', '/api/dashboard/overview',     'Api\\Dashboard\\OverviewController@overview',      ['auth']);
Router::add('GET', '/api/dashboard/transactions', 'Api\\Dashboard\\TransactionsController@transactions',  ['auth']);
Router::add('GET', '/api/dashboard/comparativo-competencia', 'Api\\Dashboard\\OverviewController@comparativoCompetenciaCaixa', ['auth']);
Router::add('GET', '/api/dashboard/provisao',     'Api\\Dashboard\\OverviewController@provisao', ['auth']);
Router::add('GET', '/api/dashboard/health-score', 'Api\\Dashboard\\HealthController@healthScore', ['auth']);
Router::add('GET', '/api/dashboard/health-score/insights', 'Api\\Dashboard\\HealthController@healthScoreInsights', ['auth']);
Router::add('GET', '/api/dashboard/greeting-insight', 'Api\\Dashboard\\HealthController@greetingInsight', ['auth']);
Router::add('GET', '/api/dashboard/evolucao',     'Api\\Dashboard\\OverviewController@evolucao', ['auth']);
Router::add('GET', '/api/options',                'Api\\Financas\\MetricsController@options', ['auth']);

// Reports
Router::add('GET', '/api/reports',             'Api\\Report\\RelatoriosController@index', ['auth']);
Router::add('GET', '/api/reports/summary',     'Api\\Report\\RelatoriosController@summary', ['auth']);
Router::add('GET', '/api/reports/insights',    'Api\\Report\\RelatoriosController@insights', ['auth']);
Router::add('GET', '/api/reports/insights-teaser', 'Api\\Report\\RelatoriosController@insightsTeaser', ['auth']);
Router::add('GET', '/api/reports/comparatives', 'Api\\Report\\RelatoriosController@comparatives', ['auth']);
Router::add('GET', '/api/reports/card-details/{id}', 'Api\\Report\\RelatoriosController@cardDetails', ['auth']);
Router::add('GET', '/api/reports/export',      'Api\\Report\\RelatoriosController@export', ['auth', 'ratelimit']);
