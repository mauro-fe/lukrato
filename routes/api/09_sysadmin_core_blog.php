<?php

declare(strict_types=1);

use Application\Core\Router;

// SysAdmin core
Router::add('GET', '/api/sysadmin/users', 'Api\\Admin\\SysAdminController@listUsers', ['auth', 'sysadmin']);
Router::add('GET', '/api/sysadmin/users/{id}', 'Api\\Admin\\SysAdminController@getUser', ['auth', 'sysadmin']);
Router::add('PUT', '/api/sysadmin/users/{id}', 'Api\\Admin\\SysAdminController@updateUser', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/sysadmin/users/{id}', 'Api\\Admin\\SysAdminController@deleteUser', ['auth', 'sysadmin', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/sysadmin/grant-access', 'Api\\Admin\\SysAdminController@grantAccess', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/sysadmin/revoke-access', 'Api\\Admin\\SysAdminController@revokeAccess', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/sysadmin/stats', 'Api\\Admin\\SysAdminController@getStats', ['auth', 'sysadmin']);
Router::add('POST', '/api/sysadmin/maintenance', 'Api\\Admin\\SysAdminController@toggleMaintenance', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET', '/api/sysadmin/maintenance', 'Api\\Admin\\SysAdminController@maintenanceStatus', ['auth', 'sysadmin']);

// Error logs
Router::add('GET',    '/api/sysadmin/error-logs', 'Api\\Admin\\SysAdminController@errorLogs', ['auth', 'sysadmin']);
Router::add('GET',    '/api/sysadmin/error-logs/summary', 'Api\\Admin\\SysAdminController@errorLogsSummary', ['auth', 'sysadmin']);
Router::add('PUT',    '/api/sysadmin/error-logs/{id}/resolve', 'Api\\Admin\\SysAdminController@resolveErrorLog', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/sysadmin/error-logs/cleanup', 'Api\\Admin\\SysAdminController@cleanupErrorLogs', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// Cache management
Router::add('POST', '/api/sysadmin/clear-cache', 'Api\\Admin\\SysAdminController@clearCache', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// Feedback
Router::add('GET', '/api/sysadmin/feedback', 'Api\\Admin\\FeedbackAdminController@index', ['auth', 'sysadmin']);
Router::add('GET', '/api/sysadmin/feedback/stats', 'Api\\Admin\\FeedbackAdminController@stats', ['auth', 'sysadmin']);
Router::add('GET', '/api/sysadmin/feedback/export', 'Api\\Admin\\FeedbackAdminController@export', ['auth', 'sysadmin']);

// Blog
Router::add('GET',    '/api/sysadmin/blog/posts', 'SysAdmin\\BlogController@index', ['auth', 'sysadmin']);
Router::add('POST',   '/api/sysadmin/blog/posts', 'SysAdmin\\BlogController@store', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/sysadmin/blog/posts/{id}', 'SysAdmin\\BlogController@show', ['auth', 'sysadmin']);
Router::add('PUT',    '/api/sysadmin/blog/posts/{id}', 'SysAdmin\\BlogController@update', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/sysadmin/blog/posts/{id}', 'SysAdmin\\BlogController@delete', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/sysadmin/blog/upload', 'SysAdmin\\BlogController@upload', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/sysadmin/blog/categorias', 'SysAdmin\\BlogController@categorias', ['auth', 'sysadmin']);
