<?php

/**
 * Script para corrigir indicações que não aplicaram recompensas corretamente
 * 
 * Uso: php cli/fix_referral_rewards.php
 */

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\Indicacao;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;

echo "=== Correção de Recompensas de Indicação ===\n\n";

// Busca o plano PRO
$planoPro = Plano::where('code', 'pro')->first();

if (!$planoPro) {
    echo "❌ Erro: Plano PRO não encontrado!\n";
    exit(1);
}

echo "✓ Plano PRO encontrado (ID: {$planoPro->id})\n\n";

// Busca usuários que foram indicados (têm referred_by preenchido)
$usuariosIndicados = Usuario::whereNotNull('referred_by')->get();

echo "Encontrados " . count($usuariosIndicados) . " usuários indicados.\n\n";

$corrigidos = 0;
$jaCorretos = 0;
$erros = 0;

foreach ($usuariosIndicados as $usuario) {
    echo "Processando: {$usuario->email} (ID: {$usuario->id})...\n";

    // Verifica se tem assinatura PRO ativa
    $assinaturaAtiva = AssinaturaUsuario::where('user_id', $usuario->id)
        ->where('status', AssinaturaUsuario::ST_ACTIVE)
        ->first();

    if (!$assinaturaAtiva) {
        echo "  ⚠️ Sem assinatura ativa, criando PRO...\n";

        AssinaturaUsuario::create([
            'user_id' => $usuario->id,
            'plano_id' => $planoPro->id,
            'gateway' => 'referral',
            'status' => AssinaturaUsuario::ST_ACTIVE,
            'renova_em' => now()->addDays(7),
        ]);

        $corrigidos++;
        echo "  ✓ Assinatura PRO criada (7 dias)\n";
        continue;
    }

    if ($assinaturaAtiva->plano_id === $planoPro->id) {
        $jaCorretos++;
        echo "  ✓ Já tem PRO ativo (expira em: {$assinaturaAtiva->renova_em})\n";
        continue;
    }

    // Tem assinatura FREE, precisa atualizar para PRO
    echo "  ⚠️ Assinatura FREE encontrada, atualizando para PRO...\n";

    $assinaturaAtiva->plano_id = $planoPro->id;
    $assinaturaAtiva->gateway = 'referral';
    $assinaturaAtiva->renova_em = now()->addDays(7);
    $assinaturaAtiva->save();

    $corrigidos++;
    echo "  ✓ Atualizado para PRO (7 dias)\n";
}

echo "\n=== Resumo ===\n";
echo "Total processados: " . count($usuariosIndicados) . "\n";
echo "Corrigidos: {$corrigidos}\n";
echo "Já corretos: {$jaCorretos}\n";
echo "Erros: {$erros}\n";

// Agora verifica também quem indicou (referrer)
echo "\n=== Verificando quem indicou (referrers) ===\n\n";

$indicacoes = Indicacao::where('status', Indicacao::STATUS_COMPLETED)
    ->where('referrer_rewarded', true)
    ->get();

echo "Encontradas " . count($indicacoes) . " indicações completadas.\n\n";

$referrersCorrigidos = 0;

foreach ($indicacoes as $indicacao) {
    $referrer = Usuario::find($indicacao->referrer_id);

    if (!$referrer) {
        echo "⚠️ Referrer ID {$indicacao->referrer_id} não encontrado\n";
        continue;
    }

    $assinaturaReferrer = AssinaturaUsuario::where('user_id', $referrer->id)
        ->where('status', AssinaturaUsuario::ST_ACTIVE)
        ->first();

    if (!$assinaturaReferrer || $assinaturaReferrer->plano_id !== $planoPro->id) {
        echo "Referrer {$referrer->email}: ";

        if ($assinaturaReferrer) {
            // Tem assinatura FREE, atualizar
            $assinaturaReferrer->plano_id = $planoPro->id;
            $assinaturaReferrer->gateway = 'referral';
            $novaData = $assinaturaReferrer->renova_em
                ? $assinaturaReferrer->renova_em->addDays(15)
                : now()->addDays(15);
            $assinaturaReferrer->renova_em = $novaData;
            $assinaturaReferrer->save();
        } else {
            // Sem assinatura, criar PRO
            AssinaturaUsuario::create([
                'user_id' => $referrer->id,
                'plano_id' => $planoPro->id,
                'gateway' => 'referral',
                'status' => AssinaturaUsuario::ST_ACTIVE,
                'renova_em' => now()->addDays(15),
            ]);
        }

        $referrersCorrigidos++;
        echo "✓ PRO aplicado/atualizado\n";
    }
}

echo "\nReferrers corrigidos: {$referrersCorrigidos}\n";
echo "\n✅ Correção finalizada!\n";
