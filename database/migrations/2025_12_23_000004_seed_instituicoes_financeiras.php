<?php

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        $instituicoes = [
            // Bancos Tradicionais
            ['nome' => 'Nubank', 'codigo' => 'nubank', 'tipo' => 'fintech', 'cor_primaria' => '#8A05BE', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Banco Itaú', 'codigo' => 'itau', 'tipo' => 'banco', 'cor_primaria' => '#EC7000', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Banco do Brasil', 'codigo' => 'bb', 'tipo' => 'banco', 'cor_primaria' => '#FFF100', 'cor_secundaria' => '#003882'],
            ['nome' => 'Bradesco', 'codigo' => 'bradesco', 'tipo' => 'banco', 'cor_primaria' => '#CC092F', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Caixa Econômica', 'codigo' => 'caixa', 'tipo' => 'banco', 'cor_primaria' => '#0066A1', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Santander', 'codigo' => 'santander', 'tipo' => 'banco', 'cor_primaria' => '#EC0000', 'cor_secundaria' => '#FFFFFF'],
            
            // Bancos Digitais / Fintechs
            ['nome' => 'Banco Inter', 'codigo' => 'inter', 'tipo' => 'banco', 'cor_primaria' => '#FF7A00', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'C6 Bank', 'codigo' => 'c6', 'tipo' => 'banco', 'cor_primaria' => '#000000', 'cor_secundaria' => '#FFD500'],
            ['nome' => 'BTG Pactual', 'codigo' => 'btg', 'tipo' => 'banco', 'cor_primaria' => '#000000', 'cor_secundaria' => '#00A859'],
            ['nome' => 'Next', 'codigo' => 'next', 'tipo' => 'banco', 'cor_primaria' => '#00AB63', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Neon', 'codigo' => 'neon', 'tipo' => 'fintech', 'cor_primaria' => '#00D6D6', 'cor_secundaria' => '#0C2C6D'],
            ['nome' => 'PagBank', 'codigo' => 'pagbank', 'tipo' => 'fintech', 'cor_primaria' => '#00A868', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Banco Original', 'codigo' => 'original', 'tipo' => 'banco', 'cor_primaria' => '#7ED957', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Will Bank', 'codigo' => 'will', 'tipo' => 'banco', 'cor_primaria' => '#FF6B00', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => '99Pay', 'codigo' => '99pay', 'tipo' => 'fintech', 'cor_primaria' => '#FCE500', 'cor_secundaria' => '#000000'],
            
            // Carteiras Digitais / Pagamento
            ['nome' => 'PicPay', 'codigo' => 'picpay', 'tipo' => 'carteira_digital', 'cor_primaria' => '#11C76F', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Mercado Pago', 'codigo' => 'mercadopago', 'tipo' => 'carteira_digital', 'cor_primaria' => '#00B1EA', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'PayPal', 'codigo' => 'paypal', 'tipo' => 'carteira_digital', 'cor_primaria' => '#003087', 'cor_secundaria' => '#009CDE'],
            ['nome' => 'Ame Digital', 'codigo' => 'ame', 'tipo' => 'carteira_digital', 'cor_primaria' => '#9A4FF3', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Recarga Pay', 'codigo' => 'recargapay', 'tipo' => 'carteira_digital', 'cor_primaria' => '#00D9A5', 'cor_secundaria' => '#FFFFFF'],
            
            // Corretoras
            ['nome' => 'XP Investimentos', 'codigo' => 'xp', 'tipo' => 'corretora', 'cor_primaria' => '#000000', 'cor_secundaria' => '#FCB316'],
            ['nome' => 'Rico', 'codigo' => 'rico', 'tipo' => 'corretora', 'cor_primaria' => '#F89728', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Clear', 'codigo' => 'clear', 'tipo' => 'corretora', 'cor_primaria' => '#00D4B4', 'cor_secundaria' => '#FFFFFF'],
            
            // Outros
            ['nome' => 'Dinheiro', 'codigo' => 'dinheiro', 'tipo' => 'fisica', 'cor_primaria' => '#4CAF50', 'cor_secundaria' => '#FFFFFF'],
            ['nome' => 'Outro', 'codigo' => 'outro', 'tipo' => 'outro', 'cor_primaria' => '#757575', 'cor_secundaria' => '#FFFFFF'],
        ];

        foreach ($instituicoes as $inst) {
            Capsule::table('instituicoes_financeiras')->insert([
                'nome' => $inst['nome'],
                'codigo' => $inst['codigo'],
                'tipo' => $inst['tipo'],
                'cor_primaria' => $inst['cor_primaria'],
                'cor_secundaria' => $inst['cor_secundaria'],
                'logo_path' => "/assets/img/banks/{$inst['codigo']}.svg",
                'ativo' => true,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down(): void
    {
        Capsule::table('instituicoes_financeiras')->truncate();
    }
};
