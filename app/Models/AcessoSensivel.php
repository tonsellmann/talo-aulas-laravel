<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcessoSensivel extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'acessos_sensiveis';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** @return BelongsTo<User, $this> */
    public function aluna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aluna_id');
    }
}
