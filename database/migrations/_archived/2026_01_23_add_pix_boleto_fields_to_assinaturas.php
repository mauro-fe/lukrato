<?php

/**
 * Migration: Adicionar campos para suporte a PIX e Boleto nas assinaturas
 * 
 * Campos adicionados:
 * - external_payment_id: ID do pagamento avulso no Asaas (para PIX/Boleto)
 * - billing_type: Tipo de pagamento (CREDIT_CARD, PIX, BOLETO)
 */

use Illuminate\Database\Capsule\Manager as Capsule;

return new class {
    public function up(): void
    {
        $schema = Capsule::schema();

        // Adicionar external_payment_id se não existir
        if (!$schema->hasColumn('assinaturas_usuarios', 'external_payment_id')) {
            $schema->table('assinaturas_usuarios', function ($table) {
                $table->string('external_payment_id', 100)->nullable()->after('external_subscription_id');
            });
        }

        // Adicionar billing_type como ENUM se não existir
        if (!$schema->hasColumn('assinaturas_usuarios', 'billing_type')) {
            Capsule::statement("ALTER TABLE assinaturas_usuarios ADD COLUMN billing_type ENUM('CREDIT_CARD', 'PIX', 'BOLETO') NOT NULL DEFAULT 'CREDIT_CARD' AFTER external_payment_id");
        }

        // Adicionar índice para external_payment_id
        $indexes = Capsule::select("SHOW INDEX FROM assinaturas_usuarios WHERE Key_name = 'idx_external_payment_id'");
        if (empty($indexes)) {
            Capsule::statement('CREATE INDEX idx_external_payment_id ON assinaturas_usuarios (external_payment_id)');
        }
    }

    public function down(): void
    {
        $schema = Capsule::schema();

        if ($schema->hasColumn('assinaturas_usuarios', 'external_payment_id')) {
            $schema->table('assinaturas_usuarios', function ($table) {
                $table->dropColumn('external_payment_id');
            });
        }

        if ($schema->hasColumn('assinaturas_usuarios', 'billing_type')) {
            $schema->table('assinaturas_usuarios', function ($table) {
                $table->dropColumn('billing_type');
            });
        }
    }
};
