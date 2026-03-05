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
            $table->string('codigo_ibge')->nullable()->change();
            $table->string('logradouro')->nullable()->change();
            $table->string('bairro')->nullable()->change();
            $table->integer('populacao')->nullable()->change();
            $table->decimal('idhm', 8, 3)->nullable()->change();
            $table->json('raw_ibge_data')->nullable()->change();
            $table->decimal('lat', 10, 8)->nullable()->change();
            $table->decimal('lng', 11, 8)->nullable()->change();
            $table->json('pois_json')->nullable()->change();
            $table->json('climate_json')->nullable()->change();
            $table->json('wiki_json')->nullable()->change();
            $table->integer('air_quality_index')->nullable()->change();
            $table->string('walkability_score')->nullable()->change();
            $table->decimal('average_income', 15, 2)->nullable()->change();
            $table->decimal('sanitation_rate', 5, 2)->nullable()->change();
            $table->text('history_extract')->nullable()->change();
            $table->string('safety_level')->nullable()->change();
            $table->text('safety_description')->nullable()->change();
            $table->json('real_estate_json')->nullable()->change();
            $table->integer('search_radius')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('location_reports', function (Blueprint $table) {
            // Reverter para non-nullable se necessário, mas geralmente não é recomendado em produção
            // se os dados já forem nulos. Mantemos como nullable no down por segurança.
        });
    }
};
