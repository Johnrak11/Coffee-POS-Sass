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
        Schema::create('shop_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->string('table_number', 10); // "T-01", "VIP-2"
            $table->string('qr_token', 64)->unique(); // Secret string in QR URL
            $table->enum('status', ['available', 'occupied'])->default('available');
            $table->timestamps();

            // Indexes
            $table->index('shop_id');
            $table->index('qr_token');
            $table->index(['shop_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_tables');
    }
};
