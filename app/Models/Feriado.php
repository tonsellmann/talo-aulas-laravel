<?php

namespace App\Models;

use App\Casts\Data;
use Illuminate\Database\Eloquent\Model;

class Feriado extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => Data::class];
    }
}
