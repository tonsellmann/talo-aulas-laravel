<?php

namespace App\Livewire\Concerns;

use App\Exceptions\RegraDeNegocio;

trait AvisaRegras
{
    /**
     * Executa uma ação e mostra o resultado no aviso do canto da tela. Regra de
     * negócio quebrada vira mensagem para a pessoa, não erro de sistema.
     */
    protected function tentar(callable $acao, string|callable $sucesso): bool
    {
        try {
            $resultado = $acao();
        } catch (RegraDeNegocio $e) {
            $this->dispatch('aviso', tipo: 'erro', texto: $e->getMessage());

            return false;
        }

        $this->dispatch('aviso', tipo: 'ok', texto: is_callable($sucesso) ? $sucesso($resultado) : $sucesso);

        return true;
    }
}
