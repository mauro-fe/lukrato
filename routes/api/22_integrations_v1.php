<?php

declare(strict_types=1);

use Application\Core\Router;

// WhatsApp link
Router::add('POST', '/api/v1/whatsapp/link', 'Api\\AI\\WhatsAppLinkController@requestLink', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/whatsapp/verify', 'Api\\AI\\WhatsAppLinkController@verify', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/whatsapp/unlink', 'Api\\AI\\WhatsAppLinkController@unlink', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/v1/whatsapp/status', 'Api\\AI\\WhatsAppLinkController@status', ['auth']);

// Telegram link
Router::add('POST', '/api/v1/telegram/link', 'Api\\AI\\TelegramLinkController@requestLink', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/telegram/unlink', 'Api\\AI\\TelegramLinkController@unlink', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/v1/telegram/status', 'Api\\AI\\TelegramLinkController@status', ['auth']);
