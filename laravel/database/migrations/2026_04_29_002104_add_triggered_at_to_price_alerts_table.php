<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('price_alerts', function (Blueprint $table) {
            $table->timestamp('triggered_at')->nullable()->after('notified_at');
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('price_alerts', function (Blueprint $table) {
            $table->dropColumn('triggered_at');
            $table->boolean('is_active')->default(true)->after('target_price');
        });
    }
};
