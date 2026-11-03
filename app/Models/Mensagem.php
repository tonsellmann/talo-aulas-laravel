<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $modelo
 * @property string $texto
 * @property string|null $link
 * @property string|null $rotulo_link
 * @property string $status
 * @property CarbonImmutable $created_at
 * @property CarbonImmutable|null $lida_em
 * @property User|null $aluna
 */
class Mensagem extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'mensagens';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'created_at' => 'immutable_datetime',
            'enviada_em' => 'immutable_datetime',
            'lida_em' => 'immutable_datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function aluna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluna_id');
    }
}
