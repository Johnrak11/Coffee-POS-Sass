<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * CRITICAL SCALABILITY FIX:
     * - Prevent duplicate order numbers (race condition fix)
     * - Prevent duplicate KHQR MD5 processing (payment safety)
     * - Optimize queue number lookups
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // CRITICAL: Prevent duplicate order numbers
            // This enforces uniqueness at database level
            $table->unique(['shop_id', 'order_number'], 'idx_unique_shop_order_number');

            // CRITICAL: Prevent duplicate KHQR MD5 payment processing
            // Allows NULL for cash payments (multiple cash orders with NULL md5 are OK)
            $table->unique('khqr_md5', 'idx_unique_khqr_md5');

            // PERFORMANCE: Optimize queue number generation lookups
            // Used in: Order model booted() event and OrderService
            $table->index(['shop_id', 'created_at', 'queue_number'], 'idx_shop_date_queue');

            // PERFORMANCE: Optimize order number generation lookups
            // Already has shop_id index, but order_number helps with LIKE queries
            $table->index('order_number', 'idx_order_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique('idx_unique_shop_order_number');
            $table->dropUnique('idx_unique_khqr_md5');
            $table->dropIndex('idx_shop_date_queue');
            $table->dropIndex('idx_order_number');
        });
    }
};
