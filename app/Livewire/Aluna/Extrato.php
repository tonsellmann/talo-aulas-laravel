<?php

namespace App\Livewire\Aluna;

use App\Models\LoteDeCredito;
use App\Models\MovimentoDeCredito;
use App\Support\Carteira;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::aluna')]
#[Title('Extrato')]
class Extrato extends Component
{
    public int $limite = 40;

    /** @return Collection<int, MovimentoDeCredito> */
    #[Computed]
    public function movimentos(): Collection
    {
        return Auth::user()->movimentos()->with('lote')
            ->orderByDesc('created_at')->orderByDesc('id')
            ->limit($this->limite)->get();
    }

    /** @return Collection<int, LoteDeCredito> */
    #[Computed]
    public function lotes(): Collection
    {
        return Carteira::de(Auth::user())->lotesAtivos();
    }

    public function mais(): void
    {
        $this->limite += 40;
    }

    public function render(): View
    {
        return view('livewire.aluna.extrato');
    }
}
