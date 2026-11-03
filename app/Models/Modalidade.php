<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $nome
 * @property int $capacidade
 * @property string $cor
 */
class Modalidade extends Model
{
    protected $table = 'modalidades';

    protected $guarded = [];
}
