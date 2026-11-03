<?php

namespace App\Actions;

use App\Models\LoteDeCredito;
use App\Support\Carteira;

class ExpirarCreditos
{
    public function __invoke(): int
    {
        $lotes = LoteDeCredito::query()
            ->whereNull('expirado_em')
            ->where('valido_ate', '<', now())
            ->with('aluna')
            ->get();

        $lotes->each(fn (LoteDeCredito $lote) => Carteira::de($lote->aluna)->expirar($lote));

        return $lotes->count();
    }
}
