<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cobrancas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluna_id')->constrained('users');
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas');
            $table->date('ciclo_inicio')->nullable();
            $table->string('descricao');
            $table->unsignedInteger('valor_centavos');
            $table->string('status', 20)->default('pendente'); // pendente, paga, expirada, divergente
            $table->string('gateway', 20);
            $table->string('gateway_id', 80)->nullable();
            $table->text('pix_copia_e_cola')->nullable();
            $table->dateTime('vence_em');
            $table->dateTime('paga_em')->nullable();
            $table->unsignedInteger('valor_pago_centavos')->nullable();
            $table->timestamps();

            $table->unique(['gateway', 'gateway_id']);
            $table->unique(['matricula_id', 'ciclo_inicio']);
        });

        // O gateway reenvia o mesmo aviso quando não recebe resposta a tempo.
        // A chave única garante que um pagamento confirmado duas vezes vire um ciclo só.
        Schema::create('eventos_de_webhook', function (Blueprint $table) {
            $table->id();
            $table->string('gateway', 20);
            $table->string('evento_id', 120);
            $table->string('tipo', 60);
            $table->json('payload');
            $table->dateTime('recebido_em');
            $table->dateTime('processado_em')->nullable();
            $table->string('resultado')->nullable();

            $table->unique(['gateway', 'evento_id']);
        });

        // Caixa de saída das mensagens de WhatsApp: o que foi (ou seria) enviado.
        Schema::create('mensagens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluna_id')->nullable()->constrained('users');
            $table->string('canal', 20)->default('whatsapp');
            $table->string('destino', 40)->nullable();
            $table->string('modelo', 40);
            $table->text('texto');
            $table->string('link')->nullable();
            $table->string('rotulo_link', 40)->nullable();
            $table->string('status', 20)->default('pendente'); // pendente, enviada, falhou
            $table->dateTime('enviada_em')->nullable();
            $table->dateTime('created_at');
            $table->dateTime('lida_em')->nullable();
        });

        // Dado de saúde é dado sensível pela LGPD: fica cifrado e cada leitura é registrada.
        Schema::create('notas_de_saude', function (Blueprint $table) {
            $table->id();
            $table->foreignId('aluna_id')->constrained('users');
            $table->text('texto');
            $table->foreignId('criado_por')->constrained('users');
            $table->timestamps();
        });

        Schema::create('acessos_sensiveis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users');
            $table->foreignId('aluna_id')->constrained('users');
            $table->string('recurso', 40);
            $table->dateTime('created_at');
        });

        Schema::create('ajustes', function (Blueprint $table) {
            $table->string('chave', 60)->primary();
            $table->text('valor')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ajustes');
        Schema::dropIfExists('acessos_sensiveis');
        Schema::dropIfExists('notas_de_saude');
        Schema::dropIfExists('mensagens');
        Schema::dropIfExists('eventos_de_webhook');
        Schema::dropIfExists('cobrancas');
    }
};
