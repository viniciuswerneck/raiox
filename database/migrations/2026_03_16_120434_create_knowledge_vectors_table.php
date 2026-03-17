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
        Schema::create('knowledge_vectors', function (Blueprint $table) {
            $table->id();
            $table->string('source_type', 50)->index(); // 'wiki', 'ibge', 'document'
            $table->string('reference_id', 100)->index(); // Ex: CEP ou Nome da Cidade
            $table->text('content'); // O trecho do texto original (chunk)
            $table->json('embedding'); // O vetor gerado (array de floats)
            $table->json('metadata')->nullable(); // Contexto extra (tags geográficas, etc)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_vectors');
    }
};
