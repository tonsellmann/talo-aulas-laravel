<?php

namespace App\Livewire\Aluna;

use App\Actions\AceitarOferta;
use App\Actions\SairDaFila;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\EntradaNaFila;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Página aberta pelo link da mensagem "vagou uma vaga". */
#[Layout('layouts::aluna')]
#[Title('Vaga oferecida')]
class Oferta extends Component
{
    use AvisaRegras;

    public EntradaNaFila $entrada;

    public function mount(EntradaNaFila $entrada): void
    {
        abort_unless($entrada->aluna_id === Auth::id(), 404);
        $this->entrada = $entrada->load('aula.modalidade', 'aula.instrutora');
    }

    public function aceitar(AceitarOferta $aceitar): void
    {
        if ($this->tentar(fn () => $aceitar($this->entrada), 'A vaga é sua.')) {
            $this->redirectRoute('aluna.inicio', navigate: true);
        }
        $this->entrada->refresh();
    }

    public function recusar(SairDaFila $sair): void
    {
        $this->tentar(fn () => $sair($this->entrada), 'Tudo bem. A vaga seguiu para a próxima da fila.');
        $this->redirectRoute('aluna.inicio', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.aluna.oferta');
    }
}
