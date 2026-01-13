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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_session_id')->onDelete('cascade');
            $table->foreignId('product_id')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->string('notes', 255)->nullable(); // "No Sugar", "Less Ice"
            $table->timestamps();

            // Indexes
            $table->index('table_session_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
