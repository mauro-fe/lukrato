<?php

declare(strict_types=1);

use Application\Core\Router;

// SysAdmin pages
Router::add('GET', '/super_admin', 'SysAdmin\\SuperAdminController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin', 'SysAdmin\\SuperAdminController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin/cupons', 'SysAdmin\\CupomViewController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin/comunicacoes', 'SysAdmin\\CommunicationController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin/blog', 'SysAdmin\\BlogViewController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin/ai', 'SysAdmin\\AiViewController@index', ['auth', 'sysadmin']);
Router::add('GET', '/sysadmin/ai/logs', 'SysAdmin\\AiLogsViewController@index', ['auth', 'sysadmin']);
