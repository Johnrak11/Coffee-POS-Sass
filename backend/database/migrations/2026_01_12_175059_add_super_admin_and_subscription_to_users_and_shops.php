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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false);
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->string('subscription_plan')->default('basic'); // basic, pro
            // subscription_status likely already exists or doesnt need modification this way
            // $table->string('subscription_status')->default('active')->change(); 
            // Just add if checking existing column is hard in migration without Doctrine DBAL which might be missing
            // Safest: Add expires_at
            $table->timestamp('subscription_expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_super_admin');
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_expires_at']);
        });
    }
};
