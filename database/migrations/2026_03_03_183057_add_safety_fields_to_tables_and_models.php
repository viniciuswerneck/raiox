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
            $table->string('safety_level')->nullable();
            $table->string('safety_description')->nullable();
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->string('safety_level')->nullable();
            $table->string('safety_description')->nullable();
        });

        Schema::table('neighborhoods', function (Blueprint $table) {
            $table->string('safety_level')->nullable();
            $table->string('safety_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            $table->dropColumn(['safety_level', 'safety_description']);
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['safety_level', 'safety_description']);
        });

        Schema::table('neighborhoods', function (Blueprint $table) {
            $table->dropColumn(['safety_level', 'safety_description']);
        });
    }
};
