<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $slug
 * @property string $nome
 * @property int $aulas_por_ciclo
 * @property int $reposicoes_por_ciclo
 * @property list<string>|null $modalidades
 * @property bool $escolhe_modalidade
 * @property int $preco_centavos
 * @property int $ciclo_dias
 */
class Plano extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'modalidades' => 'array',
            'escolhe_modalidade' => 'boolean',
            'ativo' => 'boolean',
        ];
    }
}
