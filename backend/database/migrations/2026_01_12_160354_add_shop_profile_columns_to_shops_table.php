<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            if (!Schema::hasColumn('shops', 'logo_url')) {
                $table->text('logo_url')->nullable();
            }
            if (!Schema::hasColumn('shops', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('shops', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (!Schema::hasColumn('shops', 'receipt_footer')) {
                $table->text('receipt_footer')->nullable();
            }
            if (!Schema::hasColumn('shops', 'currency_symbol')) {
                $table->string('currency_symbol', 10)->nullable()->default('$');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['logo_url', 'address', 'phone', 'receipt_footer', 'currency_symbol']);
        });
    }
};
