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
        Schema::create('location_reports', function (Blueprint $table) {
            $table->id();
            $table->string('cep')->unique()->index();
            $table->string('logradouro')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade');
            $table->string('uf', 2);
            $table->string('codigo_ibge');
            $table->unsignedBigInteger('populacao')->nullable();
            $table->decimal('idhm', 4, 3)->nullable();
            $table->json('raw_ibge_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('location_reports');
    }
};
