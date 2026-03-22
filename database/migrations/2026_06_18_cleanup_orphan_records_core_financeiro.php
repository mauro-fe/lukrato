<?php

declare(strict_types=1);

use Illuminate\Database\Capsule\Manager as Capsule;

return new class
{
    public function up(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasTable('cartoes_credito') && $schema->hasTable('contas')) {
            Capsule::statement("
                UPDATE cartoes_credito c
                INNER JOIN contas a ON a.id = c.conta_id
                SET c.user_id = a.user_id
                WHERE c.user_id <> a.user_id
            ");
        }

        if ($schema->hasTable('faturas') && $schema->hasTable('cartoes_credito')) {
            Capsule::statement("
                UPDATE faturas f
                INNER JOIN cartoes_credito c ON c.id = f.cartao_credito_id
                SET f.user_id = c.user_id
                WHERE f.user_id <> c.user_id
            ");
        }

        if ($schema->hasTable('faturas_cartao_itens') && $schema->hasTable('faturas')) {
            Capsule::statement("
                UPDATE faturas_cartao_itens i
                INNER JOIN faturas f ON f.id = i.fatura_id
                SET i.cartao_credito_id = f.cartao_credito_id
                WHERE i.cartao_credito_id <> f.cartao_credito_id
            ");

            Capsule::statement("
                UPDATE faturas_cartao_itens i
                INNER JOIN faturas f ON f.id = i.fatura_id
                SET i.user_id = f.user_id
                WHERE i.user_id <> f.user_id
            ");
        }

        if ($schema->hasTable('agendamentos') && $schema->hasTable('contas')) {
            Capsule::statement("
                UPDATE agendamentos ag
                INNER JOIN contas c ON c.id = ag.conta_id
                SET ag.user_id = c.user_id
                WHERE ag.user_id <> c.user_id
            ");
        }

        if ($schema->hasTable('notifications') && $schema->hasTable('message_campaigns')) {
            Capsule::statement("
                UPDATE notifications n
                LEFT JOIN message_campaigns c ON c.id = n.campaign_id
                SET n.campaign_id = NULL
                WHERE n.campaign_id IS NOT NULL AND c.id IS NULL
            ");
        }

        if ($schema->hasTable('faturas_cartao_itens') && $schema->hasTable('faturas')) {
            Capsule::statement("
                UPDATE faturas_cartao_itens i
                LEFT JOIN faturas f ON f.id = i.fatura_id
                SET i.fatura_id = NULL
                WHERE i.fatura_id IS NOT NULL AND f.id IS NULL
            ");
        }

        if ($schema->hasTable('agendamentos') && $schema->hasTable('contas')) {
            Capsule::statement("
                UPDATE agendamentos ag
                LEFT JOIN contas c ON c.id = ag.conta_id
                SET ag.conta_id = NULL
                WHERE ag.conta_id IS NOT NULL AND c.id IS NULL
            ");
        }

        if ($schema->hasTable('agendamentos') && $schema->hasTable('categorias')) {
            Capsule::statement("
                UPDATE agendamentos ag
                LEFT JOIN categorias c ON c.id = ag.categoria_id
                SET ag.categoria_id = NULL
                WHERE ag.categoria_id IS NOT NULL AND c.id IS NULL
            ");
        }

        if ($schema->hasTable('lancamentos') && $schema->hasTable('cartoes_credito')) {
            Capsule::statement("
                UPDATE lancamentos l
                LEFT JOIN cartoes_credito c ON c.id = l.cartao_credito_id
                SET l.cartao_credito_id = NULL
                WHERE l.cartao_credito_id IS NOT NULL AND c.id IS NULL
            ");
        }

        if ($schema->hasTable('notifications') && $schema->hasTable('usuarios')) {
            Capsule::statement("
                DELETE n
                FROM notifications n
                LEFT JOIN usuarios u ON u.id = n.user_id
                WHERE u.id IS NULL
            ");
        }

        if ($schema->hasTable('faturas_cartao_itens') && $schema->hasTable('usuarios') && $schema->hasTable('cartoes_credito')) {
            Capsule::statement("
                DELETE i
                FROM faturas_cartao_itens i
                LEFT JOIN usuarios u ON u.id = i.user_id
                LEFT JOIN cartoes_credito c ON c.id = i.cartao_credito_id
                WHERE u.id IS NULL OR c.id IS NULL
            ");
        }

        if ($schema->hasTable('faturas') && $schema->hasTable('usuarios') && $schema->hasTable('cartoes_credito')) {
            Capsule::statement("
                DELETE f
                FROM faturas f
                LEFT JOIN usuarios u ON u.id = f.user_id
                LEFT JOIN cartoes_credito c ON c.id = f.cartao_credito_id
                WHERE u.id IS NULL OR c.id IS NULL
            ");
        }

        if ($schema->hasTable('cartoes_credito') && $schema->hasTable('usuarios') && $schema->hasTable('contas')) {
            Capsule::statement("
                DELETE c
                FROM cartoes_credito c
                LEFT JOIN usuarios u ON u.id = c.user_id
                LEFT JOIN contas a ON a.id = c.conta_id
                WHERE u.id IS NULL OR a.id IS NULL
            ");
        }

        if ($schema->hasTable('agendamentos') && $schema->hasTable('usuarios')) {
            Capsule::statement("
                DELETE ag
                FROM agendamentos ag
                LEFT JOIN usuarios u ON u.id = ag.user_id
                WHERE u.id IS NULL
            ");
        }
    }

    public function down(): void
    {
        // Cleanup de dados eh irreversivel por natureza.
    }
};
