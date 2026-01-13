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
            $table->string('primary_color')->nullable()->default('#F97316');
            $table->string('bakong_account_id')->nullable();
            $table->string('merchant_name')->nullable();
            $table->string('merchant_city')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'bakong_account_id', 'merchant_name', 'merchant_city']);
        });
    }
};
