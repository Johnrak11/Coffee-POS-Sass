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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->foreignId('table_session_id')->nullable();
            $table->string('order_number', 20)->unique(); // "ORD-001"
            $table->decimal('total_amount', 10, 2);
            $table->enum('payment_method', ['cash', 'khqr']);
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('fulfillment_status', ['queue', 'preparing', 'served'])->default('queue');
            $table->timestamps();

            // Indexes
            $table->index('shop_id');
            $table->index('order_number');
            $table->index(['shop_id', 'payment_status']);
            $table->index(['shop_id', 'fulfillment_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
