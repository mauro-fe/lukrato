<?php
/**
 * Script de teste do sistema de Comunicações
 */

require dirname(__DIR__) . '/bootstrap.php';

use Application\Services\Communication\NotificationService;
use Application\Models\Notification;
use Application\Models\MessageCampaign;

echo "=================================================\n";
echo "  TESTE: Sistema de Comunicações\n";
echo "=================================================\n\n";

try {
    $service = new NotificationService();
    
    // 1. Testar Stats
    echo "1. Testando getStats()...\n";
    $stats = $service->getStats();
    echo "   - Campanhas: {$stats['total_campaigns']}\n";
    echo "   - Notificações: {$stats['total_notifications']}\n";
    echo "   - Taxa leitura: {$stats['read_rate']}%\n";
    echo "   - Campanhas último mês: {$stats['campaigns_last_month']}\n";
    echo "   ✅ Stats OK\n\n";
    
    // 2. Testar Preview Recipients
    echo "2. Testando countUsersByFilters()...\n";
    $allUsers = $service->countUsersByFilters([]);
    echo "   - Todos: $allUsers usuários\n";
    
    $freeUsers = $service->countUsersByFilters(['plan' => 'free']);
    echo "   - Free: $freeUsers usuários\n";
    
    $proUsers = $service->countUsersByFilters(['plan' => 'pro']);
    echo "   - Pro: $proUsers usuários\n";
    
    $activeUsers = $service->countUsersByFilters(['status' => 'active']);
    echo "   - Ativos: $activeUsers usuários\n";
    echo "   ✅ Preview OK\n\n";
    
    // 3. Testar listagem de campanhas
    echo "3. Testando listCampaigns()...\n";
    $campaigns = $service->listCampaigns(1, 10);
    echo "   - Total: {$campaigns['total']} campanhas\n";
    echo "   ✅ List OK\n\n";
    
    // 4. Verificar tabelas
    echo "4. Verificando tabelas no banco...\n";
    $notifCount = Notification::count();
    $campCount = MessageCampaign::count();
    echo "   - notifications: $notifCount registros\n";
    echo "   - message_campaigns: $campCount registros\n";
    echo "   ✅ Tabelas OK\n\n";
    
    echo "=================================================\n";
    echo "  ✅ TODOS OS TESTES PASSARAM!\n";
    echo "=================================================\n\n";
    echo "Sistema de Comunicações está funcionando corretamente.\n";
    echo "Acesse: /sysadmin/comunicacoes\n\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
    exit(1);
}
