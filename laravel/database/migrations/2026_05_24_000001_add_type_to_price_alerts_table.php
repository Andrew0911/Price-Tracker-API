<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('price_alerts', function (Blueprint $table) {
            $table->index('user_id', 'price_alerts_user_id_fk_index');
            $table->dropUnique(['user_id', 'product_id']);
            $table->string('type')->default('target_price')->after('product_id');
            $table->decimal('target_price', 10, 2)->nullable()->change();
            $table->unique(['user_id', 'product_id', 'type']);
        });
    }

    public function down(): void
    {
        DB::table('price_alerts')->where('type', 'price_drop')->delete();

        Schema::table('price_alerts', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'product_id', 'type']);
            $table->decimal('target_price', 10, 2)->nullable(false)->change();
            $table->dropColumn('type');
            $table->unique(['user_id', 'product_id']);
            $table->dropIndex('price_alerts_user_id_fk_index');
        });
    }
};
