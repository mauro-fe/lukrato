<?php

declare(strict_types=1);

use Application\Core\Router;

// Notifications (pt-BR endpoints)
Router::add('GET',  '/api/notificacoes', 'Api\\Notification\\NotificacaoController@index', ['auth']);
Router::add('GET',  '/api/notificacoes/unread', 'Api\\Notification\\NotificacaoController@unreadCount', ['auth']);
Router::add('POST', '/api/notificacoes/marcar', 'Api\\Notification\\NotificacaoController@marcarLida', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/notificacoes/marcar-todas', 'Api\\Notification\\NotificacaoController@marcarTodasLidas', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/notificacoes/referral-rewards', 'Api\\Notification\\NotificacaoController@getReferralRewards', ['auth']);
Router::add('POST', '/api/notificacoes/referral-rewards/seen', 'Api\\Notification\\NotificacaoController@markReferralRewardsSeen', ['auth', 'csrf', 'ratelimit']);

// User preferences
Router::add('GET',  '/api/user/theme', 'Api\\Configuracoes\\PreferenciaUsuarioController@show', ['auth']);
Router::add('POST', '/api/user/theme', 'Api\\Configuracoes\\PreferenciaUsuarioController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/user/display-name', 'Api\\Configuracoes\\PreferenciaUsuarioController@updateDisplayName', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/user/help-preferences', 'Api\\Configuracoes\\PreferenciaUsuarioController@showHelpPreferences', ['auth']);
Router::add('POST', '/api/user/help-preferences', 'Api\\Configuracoes\\PreferenciaUsuarioController@updateHelpPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/user/ui-preferences/{page}', 'Api\\Configuracoes\\PreferenciaUsuarioController@showUiPreferences', ['auth']);
Router::add('POST', '/api/user/ui-preferences/{page}', 'Api\\Configuracoes\\PreferenciaUsuarioController@updateUiPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/user/birthday-check', 'Api\\Configuracoes\\PreferenciaUsuarioController@birthdayCheck', ['auth']);
