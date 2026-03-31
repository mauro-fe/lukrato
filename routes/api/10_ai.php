<?php

declare(strict_types=1);

use Application\Core\Router;

// AI (user)
Router::add('POST', '/api/ai/chat', 'Api\\AI\\UserAiController@chat', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/ai/suggest-category', 'Api\\AI\\UserAiController@suggestCategory', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/ai/analyze', 'Api\\AI\\UserAiController@analyze', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('POST', '/api/ai/extract-transaction', 'Api\\AI\\UserAiController@extractTransaction', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);

Router::add('GET',    '/api/ai/quota', 'Api\\AI\\UserAiController@getQuota', ['auth']);
Router::add('GET',    '/api/ai/conversations', 'Api\\AI\\UserAiController@listConversations', ['auth']);
Router::add('POST',   '/api/ai/conversations', 'Api\\AI\\UserAiController@createConversation', ['auth', 'csrf', 'ai.ratelimit']);
Router::add('GET',    '/api/ai/conversations/{id}/messages', 'Api\\AI\\UserAiController@getMessages', ['auth']);
Router::add('POST',   '/api/ai/conversations/{id}/messages', 'Api\\AI\\UserAiController@sendMessage', ['auth', 'csrf', 'ai.ratelimit', 'ai.quota']);
Router::add('DELETE', '/api/ai/conversations/{id}', 'Api\\AI\\UserAiController@deleteConversation', ['auth', 'csrf', 'ai.ratelimit']);

Router::add('POST', '/api/ai/actions/{id}/confirm', 'Api\\AI\\UserAiController@confirmAction', ['auth', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/ai/actions/{id}/reject', 'Api\\AI\\UserAiController@rejectAction', ['auth', 'csrf', 'ai.ratelimit']);

// WhatsApp link
Router::add('POST', '/api/whatsapp/link', 'Api\\AI\\WhatsAppLinkController@requestLink', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/whatsapp/verify', 'Api\\AI\\WhatsAppLinkController@verify', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/whatsapp/unlink', 'Api\\AI\\WhatsAppLinkController@unlink', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/whatsapp/status', 'Api\\AI\\WhatsAppLinkController@status', ['auth']);

// Telegram link
Router::add('POST', '/api/telegram/link', 'Api\\AI\\TelegramLinkController@requestLink', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/telegram/unlink', 'Api\\AI\\TelegramLinkController@unlink', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/telegram/status', 'Api\\AI\\TelegramLinkController@status', ['auth']);

// AI (sysadmin)
Router::add('GET',  '/api/sysadmin/ai/health-proxy', 'SysAdmin\\AiApiController@healthProxy', ['auth', 'sysadmin']);
Router::add('GET',  '/api/sysadmin/ai/quota', 'SysAdmin\\AiApiController@quota', ['auth', 'sysadmin']);
Router::add('POST', '/api/sysadmin/ai/chat', 'SysAdmin\\AiApiController@chat', ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/sysadmin/ai/suggest-category', 'SysAdmin\\AiApiController@suggestCategory', ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);
Router::add('POST', '/api/sysadmin/ai/analyze-spending', 'SysAdmin\\AiApiController@analyzeSpending', ['auth', 'sysadmin', 'csrf', 'ai.ratelimit']);

Router::add('GET',    '/api/sysadmin/ai/logs', 'SysAdmin\\AiLogsApiController@index', ['auth', 'sysadmin']);
Router::add('GET',    '/api/sysadmin/ai/logs/summary', 'SysAdmin\\AiLogsApiController@summary', ['auth', 'sysadmin']);
Router::add('GET',    '/api/sysadmin/ai/logs/quality', 'SysAdmin\\AiLogsApiController@quality', ['auth', 'sysadmin']);
Router::add('DELETE', '/api/sysadmin/ai/logs/cleanup', 'SysAdmin\\AiLogsApiController@cleanup', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
