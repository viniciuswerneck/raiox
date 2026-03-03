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
        Schema::table('location_reports', function (Blueprint $table) {
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();
            $table->json('pois_json')->nullable();
            $table->json('climate_json')->nullable();
            $table->json('wiki_json')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'pois_json', 'climate_json', 'wiki_json']);
        });
    }
};
