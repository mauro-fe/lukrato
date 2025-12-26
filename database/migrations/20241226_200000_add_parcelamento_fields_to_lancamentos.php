<?php

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Schema\Blueprint;

return new class {
    public function up()
    {
        DB::schema()->table('lancamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('parcelamento_id')->nullable()->after('id');
            $table->integer('numero_parcela')->nullable()->after('parcelamento_id');
        });
    }

    public function down()
    {
        DB::schema()->table('lancamentos', function (Blueprint $table) {
            $table->dropForeign(['parcelamento_id']);
            $table->dropColumn(['parcelamento_id', 'numero_parcela']);
        });
    }
};
