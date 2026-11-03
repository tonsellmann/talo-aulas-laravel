<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $aluna_id
 * @property string $texto
 */
class NotaDeSaude extends Model
{
    protected $table = 'notas_de_saude';

    protected $guarded = [];

    protected function casts(): array
    {
        // Cifrado com a APP_KEY: quem lê o banco direto vê só texto embaralhado.
        return ['texto' => 'encrypted'];
    }

    /** @return BelongsTo<User, $this> */
    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'criado_por');
    }
}
