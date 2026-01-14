#!/usr/bin/env php
<?php
require_once dirname(__DIR__) . '/bootstrap.php';

use Application\Models\User;
use Application\Models\UserProgress;

// Pegar o último usuário criado (provavelmente o Guilherme)
$user = User::orderBy('id', 'desc')->first();

if ($user) {
    echo "Último usuário cadastrado:\n";
    echo "ID: {$user->id}\n";
    echo "Nome: {$user->nome}\n";
    echo "Email: {$user->email}\n\n";

    $progress = UserProgress::where('user_id', $user->id)->first();

    if ($progress) {
        echo "Progresso:\n";
        echo "Total pontos: {$progress->total_points}\n";
        echo "Nível: {$progress->current_level}\n";
        echo "Pontos para próximo: {$progress->points_to_next_level}\n";
    } else {
        echo "❌ SEM PROGRESSO CRIADO!\n";
    }
}
