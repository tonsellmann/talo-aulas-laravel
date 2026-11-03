<?php

namespace App\Livewire\Recepcao;

use App\Enums\Papel;
use App\Enums\StatusCobranca;
use App\Enums\StatusReserva;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::recepcao')]
#[Title('Alunas')]
class Alunas extends Component
{
    #[Url(as: 'busca')]
    public string $busca = '';

    /** @return Collection<int, User> */
    #[Computed]
    public function alunas(): Collection
    {
        return User::query()->where('papel', Papel::Aluna)
            ->when($this->busca, fn ($q) => $q->where('name', 'like', '%'.$this->busca.'%'))
            ->with([
                'matriculaAtual.plano', 'matriculaAtual.modalidade',
                'lotes' => fn ($q) => $q->where('saldo', '>', 0)->whereNull('expirado_em')->where('valido_ate', '>=', now()),
                'cobrancas' => fn ($q) => $q->whereIn('status', [StatusCobranca::Pendente, StatusCobranca::Divergente]),
            ])
            ->withMin(['reservas as proxima_aula' => fn ($q) => $q->where('reservas.status', StatusReserva::Confirmada)
                ->join('aulas', 'aulas.id', '=', 'reservas.aula_id')->where('aulas.inicio', '>', now())], 'aulas.inicio')
            ->orderBy('name')
            ->get();
    }

    public function render(): View
    {
        return view('livewire.recepcao.alunas');
    }
}
