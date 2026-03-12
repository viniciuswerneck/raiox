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
        Schema::table('cities', function (Blueprint $table) {
            if (!Schema::hasColumn('cities', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (!Schema::hasColumn('cities', 'image_url')) {
                $table->string('image_url')->nullable()->after('history_extract');
            }
            if (!Schema::hasColumn('cities', 'stats_cache')) {
                $table->json('stats_cache')->nullable()->after('image_url');
            }
            if (!Schema::hasColumn('cities', 'last_calculated_at')) {
                $table->timestamp('last_calculated_at')->nullable()->after('stats_cache');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['slug', 'image_url', 'stats_cache', 'last_calculated_at']);
        });
    }
};
