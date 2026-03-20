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
        Schema::table('llm_logs', function (Blueprint $table) {
            $table->unsignedBigInteger('ai_key_id')->nullable()->after('user_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('llm_logs', function (Blueprint $table) {
            $table->dropColumn('ai_key_id');
        });
    }
};
