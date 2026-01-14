<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('option_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Size", "Sugar Level"
            $table->timestamps();
        });

        Schema::create('option_set_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_set_id')->constrained()->cascadeOnDelete();
            $table->string('label'); // e.g., "Large", "50%"
            $table->decimal('price_modifier', 10, 2)->default(0); // e.g., 0.50
            $table->integer('position')->default(0); // for sorting
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('option_set_elements');
        Schema::dropIfExists('option_sets');
    }
};
