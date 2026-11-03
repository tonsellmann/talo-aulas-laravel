<?php

namespace App\Livewire\Recepcao;

use App\Enums\StatusCobranca;
use App\Enums\StatusFila;
use App\Models\Aula;
use App\Models\Cobranca;
use App\Models\EntradaNaFila;
use App\Models\LoteDeCredito;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::recepcao')]
#[Title('Hoje')]
class Hoje extends Component
{
    /** @return Collection<int, Aula> */
    #[Computed]
    public function aulas(): Collection
    {
        return Aula::query()->noDia(now())->with('modalidade', 'instrutora')->withCount('fila')->orderBy('inicio')->get();
    }

    /** @return array{divergentes: Collection<int, Cobranca>, pix_pendentes: int, ofertas: Collection<int, EntradaNaFila>, vencendo: Collection<int, LoteDeCredito>} */
    #[Computed]
    public function pendencias(): array
    {
        return [
            'divergentes' => Cobranca::query()->where('status', StatusCobranca::Divergente)->with('aluna')->get(),
            'pix_pendentes' => Cobranca::query()->where('status', StatusCobranca::Pendente)->count(),
            'ofertas' => EntradaNaFila::query()->where('status', StatusFila::Oferta)->where('oferta_expira_em', '>=', now())
                ->with('aluna', 'aula.modalidade')->orderBy('oferta_expira_em')->get(),
            'vencendo' => LoteDeCredito::query()->where('saldo', '>', 0)->whereNull('expirado_em')
                ->whereBetween('valido_ate', [now(), now()->addDays(3)->endOfDay()])
                ->with('aluna')->orderBy('valido_ate')->get(),
        ];
    }

    #[On('aula-mudou')]
    public function atualizar(): void
    {
        unset($this->aulas, $this->pendencias);
    }

    public function render(): View
    {
        return view('livewire.recepcao.hoje');
    }
}
