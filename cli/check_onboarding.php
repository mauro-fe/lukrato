<?php
require __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;

$users = Usuario::select('id', 'nome', 'is_admin', 'onboarding_completed_at', 'onboarding_mode')->get();
foreach ($users as $u) {
    echo 'ID: ' . $u->id . ' | ' . $u->nome . ' | admin: ' . ($u->is_admin ? 'SIM' : 'NAO') . ' | onboarding: ' . ($u->onboarding_completed_at ?? 'NULL') . ' | mode: ' . ($u->onboarding_mode ?? 'NULL') . PHP_EOL;
}
