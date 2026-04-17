<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Non-breaking v1 aliases for the first frontend separation pilot.
 * Existing endpoints remain untouched; new clients can adopt the versioned paths incrementally.
 */

// Session management
Router::add('GET',  '/api/v1/session/status',    'Api\\User\\SessionController@status');
Router::add('POST', '/api/v1/session/renew',     'Api\\User\\SessionController@renew', ['ratelimit']);
Router::add('POST', '/api/v1/session/heartbeat', 'Api\\User\\SessionController@heartbeat', ['auth', 'csrf']);

// Contact/support
Router::add('POST', '/api/v1/contato/enviar', 'Api\\User\\ContactController@send', ['ratelimit']);
Router::add('POST', '/api/v1/suporte/enviar', 'Api\\User\\SupportController@send', ['auth', 'csrf', 'ratelimit']);

// Profile
Router::add('GET', '/api/v1/perfil', 'Api\\Perfil\\PerfilController@show', ['auth']);
Router::add('POST', '/api/v1/perfil', 'Api\\Perfil\\PerfilController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/perfil/senha', 'Api\\Perfil\\PerfilController@updatePassword', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/v1/perfil/tema', 'Api\\Perfil\\PerfilController@updateTheme', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/perfil/avatar', 'Api\\Perfil\\PerfilController@uploadAvatar', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/perfil/avatar/preferences', 'Api\\Perfil\\PerfilController@updateAvatarPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/perfil/dashboard-preferences', 'Api\\Perfil\\PerfilController@getDashboardPreferences', ['auth']);
Router::add('POST', '/api/v1/perfil/dashboard-preferences', 'Api\\Perfil\\PerfilController@updateDashboardPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/perfil/avatar', 'Api\\Perfil\\PerfilController@removeAvatar', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/v1/perfil/delete', 'Api\\Perfil\\PerfilController@delete', ['auth', 'csrf', 'ratelimit_strict']);

// User preferences
Router::add('GET', '/api/v1/user/theme', 'Api\\Configuracoes\\PreferenciaUsuarioController@show', ['auth']);
Router::add('POST', '/api/v1/user/theme', 'Api\\Configuracoes\\PreferenciaUsuarioController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/user/bootstrap', 'Api\\User\\BootstrapController@show', ['auth']);
Router::add('POST', '/api/v1/user/display-name', 'Api\\Configuracoes\\PreferenciaUsuarioController@updateDisplayName', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/user/help-preferences', 'Api\\Configuracoes\\PreferenciaUsuarioController@showHelpPreferences', ['auth']);
Router::add('POST', '/api/v1/user/help-preferences', 'Api\\Configuracoes\\PreferenciaUsuarioController@updateHelpPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/user/ui-preferences/{page}', 'Api\\Configuracoes\\PreferenciaUsuarioController@showUiPreferences', ['auth']);
Router::add('POST', '/api/v1/user/ui-preferences/{page}', 'Api\\Configuracoes\\PreferenciaUsuarioController@updateUiPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/user/birthday-check', 'Api\\Configuracoes\\PreferenciaUsuarioController@birthdayCheck', ['auth']);

// Notifications
Router::add('GET', '/api/v1/notificacoes', 'Api\\Notification\\NotificacaoController@index', ['auth']);
Router::add('GET', '/api/v1/notificacoes/unread', 'Api\\Notification\\NotificacaoController@unreadCount', ['auth']);
Router::add('POST', '/api/v1/notificacoes/marcar', 'Api\\Notification\\NotificacaoController@marcarLida', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/v1/notificacoes/marcar-todas', 'Api\\Notification\\NotificacaoController@marcarTodasLidas', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/notificacoes/referral-rewards', 'Api\\Notification\\NotificacaoController@getReferralRewards', ['auth']);
Router::add('POST', '/api/v1/notificacoes/referral-rewards/seen', 'Api\\Notification\\NotificacaoController@markReferralRewardsSeen', ['auth', 'csrf', 'ratelimit']);
