<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas');
            $table->foreignId('aluna_id')->constrained('users');
            $table->foreignId('lote_id')->nullable()->constrained('lotes_de_credito');
            // confirmada, presente, falta, cancelada, cancelada_tarde, cancelada_pelo_estudio
            $table->string('status', 30);
            $table->string('origem', 20); // aluna, recepcao, fila
            $table->dateTime('lembrete_enviado_em')->nullable();
            $table->dateTime('cancelada_em')->nullable();
            $table->dateTime('presenca_em')->nullable();
            $table->timestamps();

            // Uma linha por aluna em cada aula. Reservar de novo depois de cancelar
            // reaproveita a linha, e o banco barra duplicidade sem índice parcial.
            $table->unique(['aula_id', 'aluna_id']);
        });

        Schema::create('fila_de_espera', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aula_id')->constrained('aulas');
            $table->foreignId('aluna_id')->constrained('users');
            $table->string('modo', 20); // automatico, avisar
            // aguardando, oferta, confirmada, expirada, saiu, sem_credito, encerrada
            $table->string('status', 20);
            $table->dateTime('oferta_enviada_em')->nullable();
            $table->dateTime('oferta_expira_em')->nullable();
            $table->dateTime('resolvida_em')->nullable();
            $table->timestamps();

            $table->unique(['aula_id', 'aluna_id']);
            $table->index(['aula_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fila_de_espera');
        Schema::dropIfExists('reservas');
    }
};
