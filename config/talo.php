<?php

/*
| Regras do estúdio. Ficam aqui, e não espalhadas pelo código, porque são
| combinadas com a dona do estúdio e mudam com o tempo.
*/

return [

    'estudio' => [
        'nome' => 'Talo Pilates',
        'cidade' => 'Ilhéus',
        'whatsapp' => '5573999990000',
        'chave_pix' => env('TALO_CHAVE_PIX', 'financeiro@talopilates.com.br'),
    ],

    'reserva' => [
        // A agenda de cada aula abre uma semana antes.
        'abre_dias_antes' => 7,
        // A aluna reserva sozinha até meia hora antes; depois disso, só a recepção.
        'fecha_minutos_antes' => 30,
    ],

    'cancelamento' => [
        // Cancelando até 6 horas antes, a aula volta para a aluna.
        'prazo_horas' => 6,
    ],

    'fila' => [
        // Tempo que a aluna tem para aceitar uma vaga oferecida.
        'oferta_minutos' => 30,
        // Perto da aula não dá tempo de oferecer uma a uma:
        // a vaga vai para todas da fila e fica com quem confirmar primeiro.
        'corrida_final_minutos' => 90,
        // A reserva automática só acontece com folga; perto da aula, pergunta antes.
        'automatico_ate_minutos_antes' => 120,
        // Nenhuma oferta passa deste limite antes do início.
        'oferta_ate_minutos_antes' => 15,
    ],

    'aulas' => [
        // Quantas semanas à frente a agenda é gerada a partir das turmas.
        'semanas_geradas' => 4,
        // Depois do fim da aula, quem não teve presença marcada vira falta.
        'tolerancia_fechamento_minutos' => 15,
    ],

    'lembretes' => [
        'aula_horas_antes' => 3,
        'creditos_dias_antes' => 3,
    ],

    'reposicao' => [
        'validade_dias' => 30,
    ],

    'renovacao' => [
        'dias_antes' => 5,
        'pix_validade_horas' => 72,
    ],

    'pix' => [
        // asaas em produção; demonstracao gera Pix de mentira e assina o webhook localmente.
        'gateway' => env('TALO_PIX_GATEWAY', 'demonstracao'),
        'asaas' => [
            'url' => env('ASAAS_URL', 'https://api.asaas.com/v3'),
            'chave' => env('ASAAS_CHAVE'),
            'token_webhook' => env('ASAAS_TOKEN_WEBHOOK'),
        ],
        'demonstracao' => [
            'segredo' => env('TALO_SEGREDO_WEBHOOK', 'segredo-de-demonstracao'),
        ],
    ],

    'whatsapp' => [
        // Sem token, as mensagens ficam só na caixa de saída (é o que a demonstração mostra).
        'token' => env('WHATSAPP_TOKEN'),
        'numero_id' => env('WHATSAPP_NUMERO_ID'),
    ],

    // Liga a barra da demonstração: trocar de pessoa e avançar o relógio.
    'demonstracao' => (bool) env('TALO_DEMONSTRACAO', false),

];
