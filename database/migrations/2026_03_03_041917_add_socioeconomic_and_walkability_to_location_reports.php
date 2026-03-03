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
            $table->integer('air_quality_index')->nullable();
            $table->string('walkability_score')->nullable();
            $table->decimal('average_income', 10, 2)->nullable();
            $table->decimal('sanitation_rate', 5, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            $table->dropColumn(['air_quality_index', 'walkability_score', 'average_income', 'sanitation_rate']);
        });
    }
};
