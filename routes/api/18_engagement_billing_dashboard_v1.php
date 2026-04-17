<?php

declare(strict_types=1);

use Application\Core\Router;

/**
 * Additive v1 aliases for engagement, billing and small dashboard helpers
 * still consumed by global/admin UI flows.
 */

Router::add('GET', '/api/v1/dashboard/evolucao', 'Api\Dashboard\OverviewController@evolucao', ['auth']);

Router::add('GET', '/api/v1/plan/limits', 'Api\Plan\PlanController@limits', ['auth']);
Router::add('GET', '/api/v1/plan/features', 'Api\Plan\PlanController@features', ['auth']);
Router::add('GET', '/api/v1/plan/can-create/{resource}', 'Api\Plan\PlanController@canCreate', ['auth']);
Router::add('GET', '/api/v1/plan/history-restriction', 'Api\Plan\PlanController@historyRestriction', ['auth']);
Router::add('POST', '/api/v1/premium/checkout', 'PremiumController@checkout', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('POST', '/api/v1/premium/cancel', 'PremiumController@cancel', ['auth', 'csrf', 'ratelimit_strict']);
Router::add('GET', '/api/v1/premium/check-payment/{paymentId}', 'PremiumController@checkPayment', ['auth']);
Router::add('GET', '/api/v1/premium/pending-payment', 'PremiumController@getPendingPayment', ['auth']);
Router::add('GET', '/api/v1/premium/pending-pix', 'PremiumController@getPendingPix', ['auth']);
Router::add('POST', '/api/v1/premium/cancel-pending', 'PremiumController@cancelPendingPayment', ['auth', 'csrf', 'ratelimit_strict']);

Router::add('GET', '/api/v1/referral/info', 'Api\Referral\ReferralController@getInfo');
Router::add('GET', '/api/v1/referral/validate', 'Api\Referral\ReferralController@validateCode');
Router::add('GET', '/api/v1/referral/stats', 'Api\Referral\ReferralController@getStats', ['auth']);
Router::add('GET', '/api/v1/referral/code', 'Api\Referral\ReferralController@getCode', ['auth']);
Router::add('GET', '/api/v1/referral/ranking', 'Api\Referral\ReferralController@getRanking', ['auth']);

Router::add('POST', '/api/v1/feedback', 'Api\Feedback\FeedbackController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/v1/feedback/check-nps', 'Api\Feedback\FeedbackController@checkNps', ['auth']);
Router::add('GET', '/api/v1/feedback/can-micro', 'Api\Feedback\FeedbackController@canMicro', ['auth']);

Router::add('GET', '/api/v1/cupons/validar', 'SysAdmin\CupomController@validar', ['auth']);
