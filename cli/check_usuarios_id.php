<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

$cols = DB::select('SHOW COLUMNS FROM usuarios');
foreach ($cols as $c) {
    if ($c->Field === 'id') {
        echo 'ID Type: ' . $c->Type . PHP_EOL;
        echo 'Null: ' . $c->Null . PHP_EOL;
        echo 'Key: ' . $c->Key . PHP_EOL;
        echo 'Extra: ' . $c->Extra . PHP_EOL;
        break;
    }
}
