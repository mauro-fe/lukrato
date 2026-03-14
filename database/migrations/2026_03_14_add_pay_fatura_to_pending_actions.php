<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        if (!Capsule::schema()->hasTable('pending_ai_actions')) {
            echo "• Tabela pending_ai_actions não existe — ignorando\n";
            return;
        }

        Capsule::connection()->statement(
            "ALTER TABLE pending_ai_actions MODIFY COLUMN `action_type` ENUM(
                'create_lancamento',
                'create_meta',
                'create_orcamento',
                'create_categoria',
                'create_subcategoria',
                'create_conta',
                'pay_fatura'
            ) NOT NULL"
        );

        echo "✅ Enum pending_ai_actions.action_type atualizado com pay_fatura\n";
    }

    public function down(): void
    {
        if (!Capsule::schema()->hasTable('pending_ai_actions')) {
            return;
        }

        Capsule::connection()->statement(
            "ALTER TABLE pending_ai_actions MODIFY COLUMN `action_type` ENUM(
                'create_lancamento',
                'create_meta',
                'create_orcamento',
                'create_categoria',
                'create_subcategoria',
                'create_conta'
            ) NOT NULL"
        );
    }
};
