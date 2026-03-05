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
            $table->integer('infra_score')->default(0)->after('walkability_score');
            $table->integer('mobility_score')->default(0)->after('infra_score');
            $table->integer('leisure_score')->default(0)->after('mobility_score');
            $table->integer('general_score')->default(0)->after('leisure_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            $table->dropColumn(['infra_score', 'mobility_score', 'leisure_score', 'general_score']);
        });
    }
};
