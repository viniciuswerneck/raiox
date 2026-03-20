<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cep_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->string('cep', 8);
            $table->string('status', 20);
            $table->string('logradouro')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('codigo_ibge', 7)->nullable();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->text('error_message')->nullable();
            $table->string('source', 20)->default('viacep');
            $table->string('state_target', 2)->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->timestamps();
            
            $table->index(['cep']);
            $table->index(['status']);
            $table->index(['state_target']);
            $table->index(['created_at']);
        });

        Schema::create('cep_scan_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('state', 2)->nullable();
            $table->string('status', 20)->default('running');
            $table->integer('limit_planned')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('success')->default(0);
            $table->integer('failed')->default(0);
            $table->integer('skipped')->default(0);
            $table->integer('delay_ms')->default(2000);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cep_scan_logs');
        Schema::dropIfExists('cep_scan_sessions');
    }
};
