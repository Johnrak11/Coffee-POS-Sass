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
            $table->decimal('exchange_rate', 10, 2)->default(4100)->after('currency_symbol');
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'payment_currency')) {
                $table->string('payment_currency')->default('USD')->after('payment_method'); // USD or KHR
                $table->decimal('exchange_rate_snapshot', 10, 2)->nullable()->after('payment_currency');
                $table->decimal('received_amount', 12, 2)->nullable()->after('total_amount'); // Amount in payment_currency
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn('exchange_rate');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_currency', 'exchange_rate_snapshot', 'received_amount']);
        });
    }
};
