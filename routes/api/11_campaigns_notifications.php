<?php

declare(strict_types=1);

use Application\Core\Router;

// Campaigns (sysadmin)
Router::add('GET',  '/api/campaigns', 'Api\\Notification\\CampaignController@index', ['auth', 'sysadmin']);
Router::add('POST', '/api/campaigns', 'Api\\Notification\\CampaignController@store', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/campaigns/preview', 'Api\\Notification\\CampaignController@preview', ['auth', 'sysadmin']);
Router::add('GET',  '/api/campaigns/stats', 'Api\\Notification\\CampaignController@stats', ['auth', 'sysadmin']);
Router::add('GET',  '/api/campaigns/options', 'Api\\Notification\\CampaignController@options', ['auth', 'sysadmin']);
Router::add('GET',  '/api/campaigns/birthdays', 'Api\\Notification\\CampaignController@birthdays', ['auth', 'sysadmin']);
Router::add('POST', '/api/campaigns/birthdays/send', 'Api\\Notification\\CampaignController@sendBirthdays', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('POST', '/api/campaigns/process-due', 'Api\\Notification\\CampaignController@processDue', ['auth', 'sysadmin', 'csrf', 'ratelimit']);
Router::add('GET',  '/api/campaigns/{id}', 'Api\\Notification\\CampaignController@show', ['auth', 'sysadmin']);
Router::add('POST', '/api/campaigns/{id}/cancel', 'Api\\Notification\\CampaignController@cancelScheduled', ['auth', 'sysadmin', 'csrf', 'ratelimit']);

// Notifications (en endpoints)
Router::add('GET',    '/api/notifications', 'Api\\Notification\\NotificationController@index', ['auth']);
Router::add('GET',    '/api/notifications/count', 'Api\\Notification\\NotificationController@count', ['auth']);
Router::add('POST',   '/api/notifications/{id}/read', 'Api\\Notification\\NotificationController@markAsRead', ['auth', 'csrf', 'ratelimit']);
Router::add('POST',   '/api/notifications/read-all', 'Api\\Notification\\NotificationController@markAllAsRead', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/notifications/{id}', 'Api\\Notification\\NotificationController@destroy', ['auth', 'csrf', 'ratelimit']);
Router::add('DELETE', '/api/notifications/read', 'Api\\Notification\\NotificationController@deleteRead', ['auth', 'csrf', 'ratelimit']);
