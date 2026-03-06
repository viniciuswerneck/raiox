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
            $table->tinyInteger('data_version')->default(1)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            $table->dropColumn('data_version');
        });
    }
};
