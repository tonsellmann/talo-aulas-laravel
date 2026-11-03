<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventoDeWebhook extends Model
{
    public $timestamps = false;

    protected $table = 'eventos_de_webhook';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'recebido_em' => 'immutable_datetime',
            'processado_em' => 'immutable_datetime',
        ];
    }
}
