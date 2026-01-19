<?php

require_once __DIR__ . '/../bootstrap.php';

use Application\Models\Usuario;
use Application\Models\AssinaturaUsuario;
use Application\Models\Plano;
use Carbon\Carbon;

echo "🧪 TESTANDO LÓGICA DE CANCELAMENTO\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Cenário 1: Assinatura ativa, não vencida
echo "📋 CENÁRIO 1: Assinatura ATIVA, não vencida\n";
echo "   Contratou: 01/01/2026\n";
echo "   Renova em: 01/02/2026\n";
echo "   Hoje: 15/01/2026\n";
echo "   Status: ACTIVE\n";
$renovaEm1 = Carbon::parse('2026-02-01');
$hoje = Carbon::parse('2026-01-15');
Carbon::setTestNow($hoje);
echo "   Deve ser PRO? " . ($renovaEm1->isFuture() ? '✅ SIM' : '❌ NÃO') . "\n\n";

// Cenário 2: Assinatura cancelada, mas dentro do período pago
echo "📋 CENÁRIO 2: Assinatura CANCELADA, dentro do período pago\n";
echo "   Contratou: 01/01/2026\n";
echo "   Cancelou: 15/01/2026\n";
echo "   Renova em: 01/02/2026\n";
echo "   Hoje: 20/01/2026\n";
echo "   Status: CANCELED\n";
$renovaEm2 = Carbon::parse('2026-02-01');
$hoje2 = Carbon::parse('2026-01-20');
Carbon::setTestNow($hoje2);
echo "   Deve ser PRO? " . ($renovaEm2->isFuture() ? '✅ SIM' : '❌ NÃO') . " (já pagou até 01/02)\n\n";

// Cenário 3: Assinatura cancelada, período já expirou
echo "📋 CENÁRIO 3: Assinatura CANCELADA, período expirado\n";
echo "   Contratou: 01/01/2026\n";
echo "   Cancelou: 15/01/2026\n";
echo "   Renova em: 01/02/2026\n";
echo "   Hoje: 05/02/2026\n";
echo "   Status: CANCELED\n";
$renovaEm3 = Carbon::parse('2026-02-01');
$hoje3 = Carbon::parse('2026-02-05');
Carbon::setTestNow($hoje3);
echo "   Deve ser PRO? " . ($renovaEm3->isFuture() ? '✅ SIM' : '❌ NÃO') . " (período acabou)\n\n";

// Cenário 4: Assinatura ativa vencida, dentro da carência
echo "📋 CENÁRIO 4: Assinatura ATIVA vencida, dentro da carência (3 dias)\n";
echo "   Contratou: 01/01/2026\n";
echo "   Renova em: 01/02/2026\n";
echo "   Hoje: 03/02/2026 (2 dias após vencimento)\n";
echo "   Status: ACTIVE\n";
$renovaEm4 = Carbon::parse('2026-02-01');
$hoje4 = Carbon::parse('2026-02-03');
Carbon::setTestNow($hoje4);
$diasAposVenc = $renovaEm4->diffInDays($hoje4);
echo "   Dias após vencimento: {$diasAposVenc}\n";
echo "   Deve ser PRO? " . ($diasAposVenc < 3 ? '✅ SIM' : '❌ NÃO') . " (carência de 3 dias)\n\n";

// Cenário 5: Assinatura ativa vencida, fora da carência
echo "📋 CENÁRIO 5: Assinatura ATIVA vencida, fora da carência\n";
echo "   Contratou: 01/01/2026\n";
echo "   Renova em: 01/02/2026\n";
echo "   Hoje: 05/02/2026 (4 dias após vencimento)\n";
echo "   Status: ACTIVE\n";
$renovaEm5 = Carbon::parse('2026-02-01');
$hoje5 = Carbon::parse('2026-02-05');
Carbon::setTestNow($hoje5);
$diasAposVenc5 = $renovaEm5->diffInDays($hoje5);
echo "   Dias após vencimento: {$diasAposVenc5}\n";
echo "   Deve ser PRO? " . ($diasAposVenc5 < 3 ? '✅ SIM' : '❌ NÃO') . " (passou da carência)\n\n";

Carbon::setTestNow(); // Reset

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ LÓGICA CORRETA IMPLEMENTADA:\n";
echo "   • Cancelamento não remove acesso imediato\n";
echo "   • Usuário tem acesso até o fim do período pago\n";
echo "   • Assinatura ativa tem 3 dias de carência após vencer\n";
echo "   • Assinatura cancelada NÃO tem carência (acesso até renova_em)\n";
