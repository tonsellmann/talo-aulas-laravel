<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modalidades', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('nome', 60);
            $table->unsignedSmallInteger('capacidade');
            $table->string('cor', 9);
            $table->string('descricao')->nullable();
            $table->timestamps();
        });

        // Turma é o horário fixo da semana; cada data dela vira uma aula.
        Schema::create('turmas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modalidade_id')->constrained('modalidades');
            $table->foreignId('instrutora_id')->constrained('users');
            $table->unsignedTinyInteger('dia_semana'); // 1 = segunda ... 6 = sábado
            $table->string('hora', 5); // 06:30
            $table->unsignedSmallInteger('duracao_minutos')->default(50);
            $table->string('nivel', 20);
            $table->unsignedSmallInteger('capacidade');
            $table->boolean('ativa')->default(true);
            $table->timestamps();

            $table->unique(['dia_semana', 'hora', 'modalidade_id']);
        });

        Schema::create('feriados', function (Blueprint $table) {
            $table->id();
            $table->date('data')->unique();
            $table->string('nome', 80);
        });

        Schema::create('aulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turma_id')->nullable()->constrained('turmas')->nullOnDelete();
            $table->foreignId('modalidade_id')->constrained('modalidades');
            $table->foreignId('instrutora_id')->constrained('users');
            $table->dateTime('inicio');
            $table->dateTime('fim');
            $table->string('nivel', 20);
            $table->unsignedSmallInteger('capacidade');
            // Contador mantido junto com as reservas, dentro da mesma transação.
            // É nele que a reserva trava a linha para não vender a mesma vaga duas vezes.
            $table->unsignedSmallInteger('ocupadas')->default(0);
            $table->dateTime('cancelada_em')->nullable();
            $table->string('motivo_cancelamento')->nullable();
            $table->dateTime('fechada_em')->nullable();
            $table->timestamps();

            $table->unique(['turma_id', 'inicio']);
            $table->index('inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aulas');
        Schema::dropIfExists('feriados');
        Schema::dropIfExists('turmas');
        Schema::dropIfExists('modalidades');
    }
};
