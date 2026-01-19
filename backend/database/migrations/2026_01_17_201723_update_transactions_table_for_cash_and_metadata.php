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
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'payment_method')) {
                $table->string('payment_method')->default('khqr'); // cash, khqr
            }
            if (!Schema::hasColumn('transactions', 'amount')) {
                $table->decimal('amount', 12, 2)->nullable();
            }
            if (!Schema::hasColumn('transactions', 'currency')) {
                $table->string('currency', 3)->default('USD');
            }
            if (!Schema::hasColumn('transactions', 'payload')) {
                $table->json('payload')->nullable(); // Store full response/metadata
            }

            // Note: We are keeping khqr_string and md5_hash as required.
            // For Cash payments, we'll store dummy values.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'amount', 'currency', 'payload']);
            // Cannot easily revert nullable check without raw query or assumptions
        });
    }
};
