<?php

namespace App\Livewire\Recepcao;

use App\Models\Aula;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read CarbonImmutable $segunda
 * @property-read Collection<int, Aula> $aulas
 * @property-read list<string> $horarios
 */
#[Layout('layouts::recepcao')]
#[Title('Semana')]
class Semana extends Component
{
    #[Url(as: 'semana')]
    public string $inicio = '';

    public function mount(): void
    {
        $this->inicio = $this->inicio ?: now()->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
    }

    #[Computed]
    public function segunda(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->inicio)->startOfWeek(CarbonImmutable::MONDAY);
    }

    /** @return Collection<int, Aula> */
    #[Computed]
    public function aulas(): Collection
    {
        return Aula::query()
            ->whereBetween('inicio', [$this->segunda, $this->segunda->addDays(5)->endOfDay()])
            ->with('modalidade', 'instrutora')->withCount('fila')
            ->orderBy('inicio')->get();
    }

    /** @return list<string> */
    #[Computed]
    public function horarios(): array
    {
        return $this->aulas->map(fn (Aula $a) => $a->inicio->format('H:i'))->unique()->sort()->values()->all();
    }

    public function mudarSemana(int $semanas): void
    {
        $this->inicio = $this->segunda->addWeeks($semanas)->toDateString();
        unset($this->segunda, $this->aulas, $this->horarios);
    }

    public function estaSemana(): void
    {
        $this->inicio = now()->startOfWeek(CarbonImmutable::MONDAY)->toDateString();
        unset($this->segunda, $this->aulas, $this->horarios);
    }

    #[On('aula-mudou')]
    public function atualizar(): void
    {
        unset($this->aulas);
    }

    public function render(): View
    {
        return view('livewire.recepcao.semana');
    }
}
