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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->onDelete('cascade');
            $table->text('khqr_string'); // The raw QR generated
            $table->string('md5_hash', 32); // LOWERCASE Hash for API Checking
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('order_id');
            $table->index('md5_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
