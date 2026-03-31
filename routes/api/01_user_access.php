<?php

declare(strict_types=1);

use Application\Core\Router;

Router::add('POST', '/api/tour/complete', 'Api\\User\\TourController@complete', ['auth', 'csrf', 'ratelimit']);

Router::add('POST', '/api/csrf/refresh', 'Api\\User\\SecurityController@refreshCsrf', ['ratelimit']);

// Session management
Router::add('GET',  '/api/session/status',    'Api\\User\\SessionController@status');
Router::add('POST', '/api/session/renew',     'Api\\User\\SessionController@renew', ['ratelimit']);
Router::add('POST', '/api/session/heartbeat', 'Api\\User\\SessionController@heartbeat', ['auth', 'csrf']);

// Contact/support
Router::add('POST', '/api/contato/enviar', 'Api\\User\\ContactController@send', ['ratelimit']);
Router::add('POST', '/api/suporte/enviar', 'Api\\User\\SupportController@send', ['auth', 'csrf', 'ratelimit']);

// Profile
Router::add('GET',  '/api/perfil', 'Api\\Perfil\\PerfilController@show',   ['auth']);
Router::add('POST', '/api/perfil', 'Api\\Perfil\\PerfilController@update', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/perfil/senha', 'Api\\Perfil\\PerfilController@updatePassword', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/perfil/tema', 'Api\\Perfil\\PerfilController@updateTheme', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/perfil/avatar', 'Api\\Perfil\\PerfilController@uploadAvatar', ['auth', 'csrf', 'ratelimit']);
Router::add('POST', '/api/perfil/avatar/preferences', 'Api\\Perfil\\PerfilController@updateAvatarPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/perfil/dashboard-preferences', 'Api\\Perfil\\PerfilController@getDashboardPreferences', ['auth']);
Router::add('POST', '/api/perfil/dashboard-preferences', 'Api\\Perfil\\PerfilController@updateDashboardPreferences', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/perfil/avatar', 'Api\\Perfil\\PerfilController@removeAvatar', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/perfil/delete', 'Api\\Perfil\\PerfilController@delete', ['auth', 'csrf', 'ratelimit_strict']);
