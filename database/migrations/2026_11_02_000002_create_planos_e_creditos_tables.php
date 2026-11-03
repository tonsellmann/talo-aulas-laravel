<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planos', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 40)->unique();
            $table->string('nome', 60);
            $table->unsignedSmallInteger('aulas_por_ciclo');
            $table->unsignedTinyInteger('reposicoes_por_ciclo')->default(0);
            // Modalidades em que as aulas do plano valem. Nulo = todas.
            $table->json('modalidades')->nullable();
            // No 2x por semana a aluna escolhe uma das modalidades na matrícula.
            $table->boolean('escolhe_modalidade')->default(false);
            $table->unsignedInteger('preco_centavos');
            $table->unsignedSmallInteger('ciclo_dias')->default(30);
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });

        Schema::create('matriculas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluna_id')->constrained('users');
            $table->foreignId('plano_id')->constrained('planos');
            $table->foreignId('modalidade_id')->nullable()->constrained('modalidades');
            $table->string('status', 20)->default('ativa'); // ativa, pendente, encerrada
            $table->boolean('renovacao_automatica')->default(true);
            $table->date('inicio');
            $table->timestamps();

            $table->index(['aluna_id', 'status']);
        });

        // Cada mês pago é um ciclo, com as aulas e as reposições daquele período.
        Schema::create('ciclos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained('matriculas')->cascadeOnDelete();
            $table->date('inicio');
            $table->date('fim');
            $table->unsignedTinyInteger('reposicoes_disponiveis')->default(0);
            $table->string('status', 20)->default('pago'); // pago, aguardando
            $table->timestamps();

            $table->unique(['matricula_id', 'inicio']);
        });

        // As aulas compradas ficam em lotes com validade. A reserva gasta primeiro
        // o lote que vence antes, como num estoque.
        Schema::create('lotes_de_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluna_id')->constrained('users');
            $table->foreignId('ciclo_id')->nullable()->constrained('ciclos')->nullOnDelete();
            $table->string('origem', 20); // ciclo, avulsa, reposicao, cortesia
            $table->unsignedSmallInteger('quantidade');
            $table->unsignedSmallInteger('saldo');
            $table->json('modalidades')->nullable();
            $table->dateTime('valido_de');
            $table->dateTime('valido_ate');
            $table->dateTime('expirado_em')->nullable();
            $table->dateTime('aviso_vencimento_em')->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('criado_por')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['aluna_id', 'valido_ate']);
        });

        // Extrato: cada entrada e saída de aula, com o motivo. O saldo do lote
        // é sempre a soma destes movimentos; nada muda sem deixar rastro.
        Schema::create('movimentos_de_credito', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluna_id')->constrained('users');
            $table->foreignId('lote_id')->nullable()->constrained('lotes_de_credito');
            $table->unsignedBigInteger('reserva_id')->nullable()->index();
            $table->string('tipo', 20);
            $table->smallInteger('quantidade');
            $table->string('descricao');
            $table->foreignId('criado_por')->nullable()->constrained('users');
            $table->dateTime('created_at');

            $table->index(['aluna_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentos_de_credito');
        Schema::dropIfExists('lotes_de_credito');
        Schema::dropIfExists('ciclos');
        Schema::dropIfExists('matriculas');
        Schema::dropIfExists('planos');
    }
};
