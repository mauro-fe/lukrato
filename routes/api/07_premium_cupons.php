<?php

declare(strict_types=1);

use Application\Core\Router;

// Premium
Router::add('POST', '/premium/checkout', 'PremiumController@checkout', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/premium/cancel',   'PremiumController@cancel', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('GET',  '/premium/check-payment/{paymentId}', 'PremiumController@checkPayment', ['auth']);
Router::add('GET',  '/premium/pending-payment', 'PremiumController@getPendingPayment', ['auth']);
Router::add('GET',  '/premium/pending-pix', 'PremiumController@getPendingPix', ['auth']);
Router::add('POST', '/premium/cancel-pending', 'PremiumController@cancelPendingPayment', ['auth', 'csrf', 'ratelimit_strict']);

// Discount coupons
Router::add('GET',    '/api/cupons', 'SysAdmin\\CupomController@index', ['auth', 'sysadmin']);
Router::add('POST',   '/api/cupons', 'SysAdmin\\CupomController@store', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('PUT',    '/api/cupons', 'SysAdmin\\CupomController@update', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/cupons', 'SysAdmin\\CupomController@destroy', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',    '/api/cupons/validar', 'SysAdmin\\CupomController@validar', ['auth']);
Router::add('GET',    '/api/cupons/estatisticas', 'SysAdmin\\CupomController@estatisticas', ['auth', 'sysadmin']);
