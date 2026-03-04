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
            $table->integer('search_radius')->nullable()->default(10000)->after('pois_json');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            $table->dropColumn('search_radius');
        });
    }
};
