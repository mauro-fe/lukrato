<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for sysadmin, blog, AI admin, campaigns, and coupon admin flows.
 */

Router::add('GET', '/api/v1/sysadmin/users', 'Api\Admin\SysAdminController@listUsers', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/users/{id}', 'Api\Admin\SysAdminController@getUser', ['auth', 'sysadmin']);
Router::add('PUT', '/api/v1/sysadmin/users/{id}', 'Api\Admin\SysAdminController@updateUser', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/sysadmin/users/{id}', 'Api\Admin\SysAdminController@deleteUser', ['auth', 'sysadmin', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/v1/sysadmin/grant-access', 'Api\Admin\SysAdminController@grantAccess', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/sysadmin/revoke-access', 'Api\Admin\SysAdminController@revokeAccess', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/sysadmin/stats', 'Api\Admin\SysAdminController@getStats', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/sysadmin/maintenance', 'Api\Admin\SysAdminController@toggleMaintenance', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/sysadmin/maintenance', 'Api\Admin\SysAdminController@maintenanceStatus', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/error-logs', 'Api\Admin\SysAdminController@errorLogs', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/error-logs/summary', 'Api\Admin\SysAdminController@errorLogsSummary', ['auth', 'sysadmin']);
Router::add('PUT', '/api/v1/sysadmin/error-logs/{id}/resolve', 'Api\Admin\SysAdminController@resolveErrorLog', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/sysadmin/error-logs/cleanup', 'Api\Admin\SysAdminController@cleanupErrorLogs', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/sysadmin/clear-cache', 'Api\Admin\SysAdminController@clearCache', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/sysadmin/feedback', 'Api\Admin\FeedbackAdminController@index', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/feedback/stats', 'Api\Admin\FeedbackAdminController@stats', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/feedback/export', 'Api\Admin\FeedbackAdminController@export', ['auth', 'sysadmin']);

Router::add('GET', '/api/v1/sysadmin/blog/posts', 'SysAdmin\BlogController@index', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/sysadmin/blog/posts', 'SysAdmin\BlogController@store', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/sysadmin/blog/posts/{id}', 'SysAdmin\BlogController@show', ['auth', 'sysadmin']);
Router::add('PUT', '/api/v1/sysadmin/blog/posts/{id}', 'SysAdmin\BlogController@update', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/sysadmin/blog/posts/{id}', 'SysAdmin\BlogController@delete', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/sysadmin/blog/upload', 'SysAdmin\BlogController@upload', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/sysadmin/blog/categorias', 'SysAdmin\BlogController@categorias', ['auth', 'sysadmin']);

Router::add('GET', '/api/v1/sysadmin/ai/health-proxy', 'SysAdmin\AiApiController@healthProxy', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/ai/quota', 'SysAdmin\AiApiController@quota', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/sysadmin/ai/chat', 'SysAdmin\AiApiController@chat', ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/v1/sysadmin/ai/suggest-category', 'SysAdmin\AiApiController@suggestCategory', ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/v1/sysadmin/ai/analyze-spending', 'SysAdmin\AiApiController@analyzeSpending', ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);
Router::add('GET', '/api/v1/sysadmin/ai/logs', 'SysAdmin\AiLogsApiController@index', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/ai/logs/summary', 'SysAdmin\AiLogsApiController@summary', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/sysadmin/ai/logs/quality', 'SysAdmin\AiLogsApiController@quality', ['auth', 'sysadmin']);
Router::add('DELETE', '/api/v1/sysadmin/ai/logs/cleanup', 'SysAdmin\AiLogsApiController@cleanup', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

Router::add('GET', '/api/v1/campaigns', 'Api\Notification\CampaignController@index', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/campaigns', 'Api\Notification\CampaignController@store', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/campaigns/preview', 'Api\Notification\CampaignController@preview', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/campaigns/stats', 'Api\Notification\CampaignController@stats', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/campaigns/options', 'Api\Notification\CampaignController@options', ['auth', 'sysadmin']);
Router::add('GET', '/api/v1/campaigns/birthdays', 'Api\Notification\CampaignController@birthdays', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/campaigns/birthdays/send', 'Api\Notification\CampaignController@sendBirthdays', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/campaigns/process-due', 'Api\Notification\CampaignController@processDue', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/campaigns/{id}', 'Api\Notification\CampaignController@show', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/campaigns/{id}/cancel', 'Api\Notification\CampaignController@cancelScheduled', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

Router::add('GET', '/api/v1/cupons', 'SysAdmin\CupomController@index', ['auth', 'sysadmin']);
Router::add('POST', '/api/v1/cupons', 'SysAdmin\CupomController@store', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('PUT', '/api/v1/cupons', 'SysAdmin\CupomController@update', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/cupons', 'SysAdmin\CupomController@destroy', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/cupons/estatisticas', 'SysAdmin\CupomController@estatisticas', ['auth', 'sysadmin']);
