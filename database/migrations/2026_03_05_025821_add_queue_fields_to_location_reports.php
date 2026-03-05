<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('location_reports', function (Blueprint $blueprint) {
            $blueprint->string('status')->default('completed')->after('search_radius');
            $blueprint->text('error_message')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['status', 'error_message']);
        });
    }
};
