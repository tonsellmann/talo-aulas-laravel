<?php

namespace App\Livewire\Instrutora;

use App\Actions\RegistrarPresenca;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\AcessoSensivel;
use App\Models\Aula;
use App\Models\NotaDeSaude;
use App\Models\Reserva;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Collection<int, Aula> $aulas
 * @property-read Collection<int, Aula> $amanha
 * @property-read Aula|null $aula
 * @property-read Collection<int, Reserva> $lista
 * @property-read list<int> $comNotas
 */
#[Layout('layouts::instrutora')]
#[Title('Minhas aulas')]
class Aulas extends Component
{
    use AvisaRegras;

    public ?int $aulaId = null;

    /** @var list<int> alunas cujas observações a instrutora abriu nesta tela */
    public array $notasAbertas = [];

    public function mount(): void
    {
        // Abre direto na aula que está acontecendo ou na próxima do dia.
        $this->aulaId = $this->aulas->first(fn (Aula $a) => now()->lessThan($a->fim))->id ?? $this->aulas->last()?->id;
    }

    /** @return Collection<int, Aula> */
    #[Computed]
    public function aulas(): Collection
    {
        return Aula::query()->where('instrutora_id', Auth::id())->noDia(now())->whereNull('cancelada_em')
            ->with('modalidade')->orderBy('inicio')->get();
    }

    /** @return Collection<int, Aula> */
    #[Computed]
    public function amanha(): Collection
    {
        return Aula::query()->where('instrutora_id', Auth::id())->noDia(now()->addDay())->whereNull('cancelada_em')
            ->with('modalidade')->orderBy('inicio')->get();
    }

    #[Computed]
    public function aula(): ?Aula
    {
        return $this->aulas->firstWhere('id', $this->aulaId);
    }

    /** @return Collection<int, Reserva> */
    #[Computed]
    public function lista(): Collection
    {
        return $this->aula?->reservasAtivas()->with('aluna')->get()->sortBy('aluna.name')->values() ?? collect();
    }

    /**
     * Quais alunas da lista têm observação de saúde (sem ler o conteúdo).
     *
     * @return list<int>
     */
    #[Computed]
    public function comNotas(): array
    {
        return NotaDeSaude::query()->whereIn('aluna_id', $this->lista->pluck('aluna_id'))->distinct()->pluck('aluna_id')->all();
    }

    /** @return Collection<int, NotaDeSaude> */
    public function notasDe(int $aluna): Collection
    {
        return NotaDeSaude::query()->where('aluna_id', $aluna)->latest()->get();
    }

    public function escolher(int $aula): void
    {
        $this->aulaId = $aula;
        $this->notasAbertas = [];
        unset($this->aula, $this->lista, $this->comNotas);
    }

    /** A instrutora só lê observação de aluna que está na aula dela; a leitura fica registrada. */
    public function abrirNota(int $aluna): void
    {
        abort_unless($this->lista->contains('aluna_id', $aluna), 403);
        AcessoSensivel::query()->create(['usuario_id' => Auth::id(), 'aluna_id' => $aluna, 'recurso' => 'notas_de_saude', 'created_at' => now()]);
        $this->notasAbertas[] = $aluna;
    }

    public function presenca(int $reserva, bool $presente, RegistrarPresenca $registrar): void
    {
        $reserva = $this->aula->reservas()->findOrFail($reserva);
        $this->tentar(fn () => $registrar($reserva, $presente), $presente ? 'Presença marcada.' : 'Presença desfeita.');
        unset($this->lista);
    }

    public function render(): View
    {
        return view('livewire.instrutora.aulas');
    }
}
