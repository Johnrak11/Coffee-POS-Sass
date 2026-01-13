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
        Schema::create('table_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_table_id')->constrained('shop_tables')->onDelete('cascade');
            $table->string('session_token', 100)->unique(); // Browser cookie ID
            $table->string('guest_name', 50)->nullable(); // Optional: "Mr. John"
            $table->enum('status', ['active', 'ordering', 'completed'])->default('active');
            $table->timestamps();

            // Indexes
            $table->index('shop_table_id');
            $table->index('session_token');
            $table->index(['shop_table_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_sessions');
    }
};
