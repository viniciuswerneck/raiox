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
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('ibge_code')->nullable()->unique();
            $table->string('uf', 2);
            $table->string('name');
            $table->integer('population')->nullable();
            $table->decimal('average_income', 10, 2)->nullable();
            $table->decimal('sanitation_rate', 5, 2)->nullable();
            $table->longText('history_extract')->nullable();
            $table->json('wiki_json')->nullable();
            $table->json('raw_ibge_data')->nullable();
            $table->timestamps();

            $table->unique(['uf', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
