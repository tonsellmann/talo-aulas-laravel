<?php

namespace App\Livewire\Aluna;

use App\Actions\EntrarNaFila;
use App\Actions\ReservarAula;
use App\Actions\SairDaFila;
use App\Enums\ModoFila;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Models\Modalidade;
use App\Models\Reserva;
use App\Models\User;
use App\Support\Carteira;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read list<CarbonImmutable> $dias
 * @property-read Collection<int, Modalidade> $modalidades
 * @property-read Collection<int, Aula> $aulas
 * @property-read array{reservas: Collection<int, Reserva>, filas: Collection<int, EntradaNaFila>} $minhas
 */
#[Layout('layouts::aluna')]
#[Title('Agenda')]
class Agenda extends Component
{
    use AvisaRegras;

    #[Url(as: 'dia')]
    public string $dia = '';

    #[Url(as: 'modalidade')]
    public string $modalidade = '';

    /** Aula lotada em que a aluna está escolhendo como entrar na fila. */
    public ?int $filaPara = null;

    public string $modo = 'avisar';

    public function mount(): void
    {
        $this->dia = $this->dia ?: now()->toDateString();

        if ($this->modalidade === '') {
            $permitidas = Auth::user()->matriculaAtual?->modalidadesPermitidas();
            $this->modalidade = $permitidas && count($permitidas) === 1 ? $permitidas[0] : '';
        }
    }

    private function aluna(): User
    {
        return Auth::user();
    }

    /** @return list<CarbonImmutable> */
    #[Computed]
    public function dias(): array
    {
        // O estúdio não abre aos domingos.
        return array_values(array_filter(
            array_map(fn ($i) => now()->startOfDay()->addDays($i), range(0, 8)),
            fn (CarbonImmutable $d) => ! $d->isSunday(),
        ));
    }

    /** @return Collection<int, Modalidade> */
    #[Computed]
    public function modalidades(): Collection
    {
        return Modalidade::query()->orderBy('id')->get();
    }

    /** @return Collection<int, Aula> */
    #[Computed]
    public function aulas(): Collection
    {
        $dia = CarbonImmutable::parse($this->dia);

        return Aula::query()
            ->noDia($dia)
            ->when($this->modalidade, fn ($q) => $q->whereHas('modalidade', fn ($m) => $m->where('slug', $this->modalidade)))
            ->with('modalidade', 'instrutora')
            ->orderBy('inicio')
            ->get();
    }

    /**
     * Reservas e filas da aluna nas aulas mostradas, para cada cartão saber o estado.
     *
     * @return array{reservas: Collection<int, Reserva>, filas: Collection<int, EntradaNaFila>}
     */
    #[Computed]
    public function minhas(): array
    {
        $ids = $this->aulas->pluck('id');

        return [
            'reservas' => $this->aluna()->reservas()->whereIn('aula_id', $ids)->get()->keyBy('aula_id'),
            'filas' => $this->aluna()->filas()->whereIn('aula_id', $ids)->whereIn('status', ['aguardando', 'oferta'])->get()->keyBy('aula_id'),
        ];
    }

    public function pagaCom(Aula $aula): ?string
    {
        return Carteira::de($this->aluna())->loteParaAula($aula)?->origem->rotulo();
    }

    public function reservar(int $aula, ReservarAula $reservar): void
    {
        $aula = Aula::query()->with('modalidade')->findOrFail($aula);

        $this->tentar(fn () => $reservar($this->aluna(), $aula), function (Reserva $r) {
            $prazo = $r->prazoDeCancelamento();

            return 'Reservado: '.$r->aula->modalidade->nome.', '.$r->aula->quando().'. Dá para desmarcar até '.$prazo->translatedFormat('l, d/m \\à\\s H:i').'.';
        });

        $this->atualizar();
    }

    public function escolherFila(int $aula): void
    {
        $this->filaPara = $aula;
        $this->modo = 'avisar';
    }

    public function entrarNaFila(EntrarNaFila $entrar): void
    {
        $aula = Aula::query()->with('modalidade')->findOrFail($this->filaPara);
        $this->tentar(fn () => $entrar($this->aluna(), $aula, ModoFila::from($this->modo)), fn ($e) => 'Você entrou na fila: é a '.$e->posicao().'ª.');
        $this->filaPara = null;
        $this->atualizar();
    }

    public function sairDaFila(int $entrada, SairDaFila $sair): void
    {
        $entrada = $this->aluna()->filas()->with('aula')->findOrFail($entrada);
        $this->tentar(fn () => $sair($entrada), 'Você saiu da fila.');
        $this->atualizar();
    }

    #[On('agenda-mudou')]
    public function atualizar(): void
    {
        unset($this->aulas, $this->minhas);
    }

    public function render(): View
    {
        return view('livewire.aluna.agenda');
    }
}
