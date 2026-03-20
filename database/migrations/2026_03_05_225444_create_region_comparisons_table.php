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
        Schema::create('region_comparisons', function (Blueprint $table) {
            $table->id();
            $table->string('cep_a')->index();
            $table->string('cep_b')->index();

            // Scores Diferenciais
            $table->integer('score_diff')->default(0);
            $table->integer('infra_diff')->default(0);
            $table->integer('mobilidade_diff')->default(0);
            $table->integer('lazer_diff')->default(0);

            // Dados Consolidados de Comparação
            $table->json('comparison_data')->nullable(); // Guardar deltas detalhados (ex: +3 hospitais)
            $table->text('analysis_text')->nullable();   // Análise gerada pela IA

            $table->index(['cep_a', 'cep_b']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('region_comparisons');
    }
};
