<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Carregar .env
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Configurar Eloquent
require_once dirname(__DIR__) . '/config/config.php';

use Illuminate\Database\Capsule\Manager as DB;

echo "=== ADICIONANDO TODAS AS INSTITUIÇÕES FINANCEIRAS ===\n\n";

$instituicoes = [
    // Bancos Tradicionais
    ['nome' => 'Banco do Brasil', 'codigo' => 'bb', 'tipo' => 'banco', 'cor_primaria' => '#FFF100', 'cor_secundaria' => '#003882'],
    ['nome' => 'Caixa Econômica Federal', 'codigo' => 'caixa', 'tipo' => 'banco', 'cor_primaria' => '#0066A1', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Bradesco', 'codigo' => 'bradesco', 'tipo' => 'banco', 'cor_primaria' => '#CC092F', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Itaú', 'codigo' => 'itau', 'tipo' => 'banco', 'cor_primaria' => '#EC7000', 'cor_secundaria' => '#003A70'],
    ['nome' => 'Santander', 'codigo' => 'santander', 'tipo' => 'banco', 'cor_primaria' => '#EC0000', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Banrisul', 'codigo' => 'banrisul', 'tipo' => 'banco', 'cor_primaria' => '#0055A5', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Sicredi', 'codigo' => 'sicredi', 'tipo' => 'banco', 'cor_primaria' => '#00843D', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Sicoob', 'codigo' => 'sicoob', 'tipo' => 'banco', 'cor_primaria' => '#003641', 'cor_secundaria' => '#6FB43F'],
    ['nome' => 'Banco do Nordeste', 'codigo' => 'bnb', 'tipo' => 'banco', 'cor_primaria' => '#E30613', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Banco da Amazônia', 'codigo' => 'basa', 'tipo' => 'banco', 'cor_primaria' => '#006838', 'cor_secundaria' => '#FFFFFF'],

    // Bancos Digitais / Fintechs
    ['nome' => 'Nubank', 'codigo' => 'nubank', 'tipo' => 'fintech', 'cor_primaria' => '#8A05BE', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Banco Inter', 'codigo' => 'inter', 'tipo' => 'banco', 'cor_primaria' => '#FF7A00', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'C6 Bank', 'codigo' => 'c6', 'tipo' => 'banco', 'cor_primaria' => '#000000', 'cor_secundaria' => '#FFD500'],
    ['nome' => 'Banco Pan', 'codigo' => 'pan', 'tipo' => 'banco', 'cor_primaria' => '#00ADEF', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Neon', 'codigo' => 'neon', 'tipo' => 'fintech', 'cor_primaria' => '#00D6D6', 'cor_secundaria' => '#0C2C6D'],
    ['nome' => 'PagBank', 'codigo' => 'pagbank', 'tipo' => 'fintech', 'cor_primaria' => '#00A868', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Next', 'codigo' => 'next', 'tipo' => 'banco', 'cor_primaria' => '#00AB63', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Banco Original', 'codigo' => 'original', 'tipo' => 'banco', 'cor_primaria' => '#7ED957', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'BS2', 'codigo' => 'bs2', 'tipo' => 'banco', 'cor_primaria' => '#1E3A8A', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Agibank', 'codigo' => 'agibank', 'tipo' => 'banco', 'cor_primaria' => '#FF6B00', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Will Bank', 'codigo' => 'will', 'tipo' => 'banco', 'cor_primaria' => '#FFE600', 'cor_secundaria' => '#000000'],
    ['nome' => 'Banco Digio', 'codigo' => 'digio', 'tipo' => 'banco', 'cor_primaria' => '#0066FF', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'BTG Pactual', 'codigo' => 'btg', 'tipo' => 'banco', 'cor_primaria' => '#000000', 'cor_secundaria' => '#00A859'],

    // Carteiras Digitais / Pagamento
    ['nome' => 'Mercado Pago', 'codigo' => 'mercadopago', 'tipo' => 'carteira_digital', 'cor_primaria' => '#00B1EA', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'PicPay', 'codigo' => 'picpay', 'tipo' => 'carteira_digital', 'cor_primaria' => '#11C76F', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => '99Pay', 'codigo' => '99pay', 'tipo' => 'carteira_digital', 'cor_primaria' => '#FCE500', 'cor_secundaria' => '#000000'],
    ['nome' => 'Ame Digital', 'codigo' => 'ame', 'tipo' => 'carteira_digital', 'cor_primaria' => '#9A4FF3', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'RecargaPay', 'codigo' => 'recargapay', 'tipo' => 'carteira_digital', 'cor_primaria' => '#00D9A5', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'PayPal', 'codigo' => 'paypal', 'tipo' => 'carteira_digital', 'cor_primaria' => '#003087', 'cor_secundaria' => '#009CDE'],
    ['nome' => 'Nomad', 'codigo' => 'nomad', 'tipo' => 'fintech', 'cor_primaria' => '#6366f1', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Binance', 'codigo' => 'binance', 'tipo' => 'carteira_digital', 'cor_primaria' => '#F3BA2F', 'cor_secundaria' => '#000000'],

    // Corretoras
    ['nome' => 'XP Investimentos', 'codigo' => 'xp', 'tipo' => 'corretora', 'cor_primaria' => '#000000', 'cor_secundaria' => '#FCB316'],
    ['nome' => 'Rico', 'codigo' => 'rico', 'tipo' => 'corretora', 'cor_primaria' => '#F89728', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Clear', 'codigo' => 'clear', 'tipo' => 'corretora', 'cor_primaria' => '#00D4B4', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'ModalMais', 'codigo' => 'modalmais', 'tipo' => 'corretora', 'cor_primaria' => '#1E3A5F', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Easynvest', 'codigo' => 'easynvest', 'tipo' => 'corretora', 'cor_primaria' => '#00BFA5', 'cor_secundaria' => '#FFFFFF'],

    // Outros
    ['nome' => 'Dinheiro', 'codigo' => 'dinheiro', 'tipo' => 'fisica', 'cor_primaria' => '#4CAF50', 'cor_secundaria' => '#FFFFFF'],
    ['nome' => 'Outro', 'codigo' => 'outro', 'tipo' => 'outro', 'cor_primaria' => '#757575', 'cor_secundaria' => '#FFFFFF'],
];

try {
    $added = 0;
    $updated = 0;

    foreach ($instituicoes as $inst) {
        $exists = DB::table('instituicoes_financeiras')
            ->where('codigo', $inst['codigo'])
            ->exists();

        if (!$exists) {
            DB::table('instituicoes_financeiras')->insert([
                'nome' => $inst['nome'],
                'codigo' => $inst['codigo'],
                'tipo' => $inst['tipo'],
                'cor_primaria' => $inst['cor_primaria'],
                'cor_secundaria' => $inst['cor_secundaria'],
                'logo_path' => "/assets/img/banks/{$inst['codigo']}.svg",
                'ativo' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            echo "✓ Adicionada: {$inst['nome']}\n";
            $added++;
        } else {
            // Atualizar dados existentes
            DB::table('instituicoes_financeiras')
                ->where('codigo', $inst['codigo'])
                ->update([
                    'nome' => $inst['nome'],
                    'tipo' => $inst['tipo'],
                    'cor_primaria' => $inst['cor_primaria'],
                    'cor_secundaria' => $inst['cor_secundaria'],
                    'logo_path' => "/assets/img/banks/{$inst['codigo']}.svg",
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            echo "~ Atualizada: {$inst['nome']}\n";
            $updated++;
        }
    }

    echo "\n=== CONCLUÍDO ===\n";
    echo "Total adicionadas: $added\n";
    echo "Total atualizadas: $updated\n";

    // Listar todas ordenadas
    echo "\n=== INSTITUIÇÕES DISPONÍVEIS ===\n";
    $todas = DB::table('instituicoes_financeiras')->orderBy('tipo')->orderBy('nome')->get();

    $tipoAtual = '';
    foreach ($todas as $inst) {
        if ($tipoAtual !== $inst->tipo) {
            $tipoAtual = $inst->tipo;
            echo "\n📁 " . ucfirst($tipoAtual) . ":\n";
        }
        echo "   - {$inst->nome} ({$inst->codigo})\n";
    }

    echo "\nTotal: " . count($todas) . " instituições\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
