<?php

declare(strict_types=1);

use Application\Core\Router;

// Digital card
Router::add('GET', '/card', 'Site\\CardController@index');
Router::add('GET', '/links', 'Site\\CardController@index');

// Legal pages
Router::add('GET', '/termos', 'Site\\LegalController@terms');
Router::add('GET', '/privacidade', 'Site\\LegalController@privacy');
Router::add('GET', '/lgpd', 'Site\\LegalController@lgpd');
