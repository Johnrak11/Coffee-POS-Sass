<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            // Drop Geolocation columns
            if (Schema::hasColumn('shops', 'latitude')) $table->dropColumn('latitude');
            if (Schema::hasColumn('shops', 'longitude')) $table->dropColumn('longitude');
            if (Schema::hasColumn('shops', 'radius')) $table->dropColumn('radius');
            if (Schema::hasColumn('shops', 'location_check_enabled')) $table->dropColumn('location_check_enabled');

            // Add IP Security columns
            $table->json('trusted_ips')->nullable();
            $table->boolean('ip_check_enabled')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shops', function (Blueprint $table) {
            $table->dropColumn(['trusted_ips', 'ip_check_enabled']);

            // Restore Geolocation (optional, keeping basic schema)
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('radius')->default(100)->nullable();
            $table->boolean('location_check_enabled')->default(false);
        });
    }
};
