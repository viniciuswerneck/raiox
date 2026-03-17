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
        Schema::create('llm_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('provider', 50)->index();
            $table->string('model', 100)->index();
            $table->integer('prompt_tokens')->nullable();
            $table->integer('completion_tokens')->nullable();
            $table->integer('total_tokens')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('status', 20)->default('success')->index(); // success, fail, timeout
            $table->text('error_message')->nullable();
            $table->string('agent_name', 50)->nullable()->index(); // Ex: WikiAgent
            $table->string('agent_version', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('llm_logs');
    }
};
