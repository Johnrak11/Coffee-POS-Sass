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
        Schema::create('order_item_options', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_item_id');
            // We keep product_variant_id as a reference, but make it nullable just in case.
            // Ideally we also snapshot the name/price.
            $table->unsignedBigInteger('product_variant_id')->nullable();

            // Snapshots for historical accuracy
            $table->string('group_name'); // e.g. "Sugar Level", "Toppings"
            $table->string('option_name'); // e.g. "50%", "Boba"
            $table->decimal('extra_price', 10, 2)->default(0);

            $table->timestamps();

            $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('cascade');
            // We probably don't want cascade on variant delete because we want to keep history?
            // Actually, if variant is deleted, we might want to set this to null but keep the snapshot names.
            // For now, let's just index it.
            $table->index('product_variant_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_options');
    }
};
