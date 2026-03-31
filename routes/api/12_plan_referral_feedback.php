<?php

declare(strict_types=1);

use Application\Core\Router;

// Plan and limits
Router::add('GET', '/api/plan/limits', 'Api\\Plan\\PlanController@limits', ['auth']);
Router::add('GET', '/api/plan/features', 'Api\\Plan\\PlanController@features', ['auth']);
Router::add('GET', '/api/plan/can-create/{resource}', 'Api\\Plan\\PlanController@canCreate', ['auth']);
Router::add('GET', '/api/plan/history-restriction', 'Api\\Plan\\PlanController@historyRestriction', ['auth']);

// Referral
Router::add('GET', '/api/referral/info', 'Api\\Referral\\ReferralController@getInfo');
Router::add('GET', '/api/referral/validate', 'Api\\Referral\\ReferralController@validateCode');
Router::add('GET', '/api/referral/stats', 'Api\\Referral\\ReferralController@getStats', ['auth']);
Router::add('GET', '/api/referral/code', 'Api\\Referral\\ReferralController@getCode', ['auth']);
Router::add('GET', '/api/referral/ranking', 'Api\\Referral\\ReferralController@getRanking', ['auth']);

// Feedback
Router::add('POST', '/api/feedback', 'Api\\Feedback\\FeedbackController@store', ['auth', 'csrf', 'ratelimit']);
Router::add('GET', '/api/feedback/check-nps', 'Api\\Feedback\\FeedbackController@checkNps', ['auth']);
Router::add('GET', '/api/feedback/can-micro', 'Api\\Feedback\\FeedbackController@canMicro', ['auth']);
