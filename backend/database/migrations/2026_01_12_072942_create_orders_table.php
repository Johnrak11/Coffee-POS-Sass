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

            // On Windows/MariaDB, adding nullable FKs can crash with "Tablespace missing" (Err 194)
            // We use simple integer column here to avoid the ALTER TABLE trigger
            $table->unsignedBigInteger('user_id')->nullable();
            $table->index('user_id'); // Add index manually
            $table->foreignId('table_session_id')->nullable();

            $table->string('order_number', 20)->unique(); // "ORD-001"
            $table->integer('queue_number')->nullable(); // From add_queue_number

            // Amounts
            $table->decimal('total_amount', 10, 2);
            $table->decimal('received_amount', 12, 2)->nullable(); // From add_currency_fields

            // Currency
            $table->string('payment_currency')->default('USD'); // From add_currency_fields
            $table->decimal('exchange_rate_snapshot', 10, 2)->nullable(); // From add_currency_fields

            // Statuses
            $table->enum('payment_method', ['cash', 'khqr']);
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'rejected'])->default('pending'); // From add_rejected_status (updated enum)
            $table->string('confirmation_status')->default('confirmed'); // From add_confirmation_status
            $table->enum('fulfillment_status', ['queue', 'preparing', 'served'])->default('queue');

            // KHQR Fields (From add_khqr_fields)
            $table->string('khqr_md5')->nullable()->unique();
            $table->text('khqr_string')->nullable();
            $table->json('payment_metadata')->nullable();

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
