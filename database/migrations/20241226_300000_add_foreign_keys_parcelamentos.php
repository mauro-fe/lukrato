<?php

use Illuminate\Database\Capsule\Manager as DB;

return new class {
    public function up()
    {
        // Adicionar foreign keys na tabela parcelamentos
        DB::statement('
            ALTER TABLE parcelamentos
            ADD CONSTRAINT parcelamentos_usuario_id_foreign 
            FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
        ');

        DB::statement('
            ALTER TABLE parcelamentos
            ADD CONSTRAINT parcelamentos_categoria_id_foreign 
            FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
        ');

        DB::statement('
            ALTER TABLE parcelamentos
            ADD CONSTRAINT parcelamentos_conta_id_foreign 
            FOREIGN KEY (conta_id) REFERENCES contas(id) ON DELETE CASCADE
        ');

        // Adicionar foreign key na tabela lancamentos
        DB::statement('
            ALTER TABLE lancamentos
            ADD CONSTRAINT lancamentos_parcelamento_id_foreign 
            FOREIGN KEY (parcelamento_id) REFERENCES parcelamentos(id) ON DELETE CASCADE
        ');
    }

    public function down()
    {
        DB::statement('ALTER TABLE lancamentos DROP FOREIGN KEY lancamentos_parcelamento_id_foreign');
        DB::statement('ALTER TABLE parcelamentos DROP FOREIGN KEY parcelamentos_usuario_id_foreign');
        DB::statement('ALTER TABLE parcelamentos DROP FOREIGN KEY parcelamentos_categoria_id_foreign');
        DB::statement('ALTER TABLE parcelamentos DROP FOREIGN KEY parcelamentos_conta_id_foreign');
    }
};
