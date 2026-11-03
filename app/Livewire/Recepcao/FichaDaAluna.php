<?php

namespace App\Livewire\Recepcao;

use App\Enums\OrigemLote;
use App\Enums\Papel;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\AcessoSensivel;
use App\Models\LoteDeCredito;
use App\Models\MovimentoDeCredito;
use App\Models\NotaDeSaude;
use App\Models\Reserva;
use App\Models\User;
use App\Support\Carteira;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::recepcao')]
#[Title('Ficha da aluna')]
class FichaDaAluna extends Component
{
    use AvisaRegras;

    public User $aluna;

    public bool $verNotas = false;

    public string $novaNota = '';

    public int $cortesias = 1;

    public string $motivoCortesia = '';

    public function mount(User $aluna): void
    {
        abort_unless($aluna->papel === Papel::Aluna, 404);
        $this->aluna = $aluna;
    }

    /** @return Collection<int, LoteDeCredito> */
    #[Computed]
    public function lotes(): Collection
    {
        return Carteira::de($this->aluna)->lotesAtivos();
    }

    /** @return Collection<int, Reserva> */
    #[Computed]
    public function proximas(): Collection
    {
        return $this->aluna->proximasReservas()->get();
    }

    /** @return Collection<int, MovimentoDeCredito> */
    #[Computed]
    public function movimentos(): Collection
    {
        return $this->aluna->movimentos()->orderByDesc('created_at')->orderByDesc('id')->limit(15)->get();
    }

    /** @return Collection<int, NotaDeSaude> */
    #[Computed]
    public function notas(): Collection
    {
        return $this->verNotas ? $this->aluna->notasDeSaude()->with('autor')->latest()->get() : collect();
    }

    /** @return Collection<int, AcessoSensivel> */
    #[Computed]
    public function acessos(): Collection
    {
        return AcessoSensivel::query()->where('aluna_id', $this->aluna->id)->with('usuario')->latest('created_at')->limit(5)->get();
    }

    /** Ler a observação de saúde fica registrado: quem, quando, de qual aluna. */
    public function abrirNotas(): void
    {
        AcessoSensivel::query()->create(['usuario_id' => Auth::id(), 'aluna_id' => $this->aluna->id, 'recurso' => 'notas_de_saude', 'created_at' => now()]);
        $this->verNotas = true;
        unset($this->acessos);
    }

    public function salvarNota(): void
    {
        $this->validate(['novaNota' => 'required|min:5|max:500']);
        NotaDeSaude::query()->create(['aluna_id' => $this->aluna->id, 'texto' => $this->novaNota, 'criado_por' => Auth::id()]);
        $this->novaNota = '';
        unset($this->notas);
        $this->dispatch('aviso', tipo: 'ok', texto: 'Observação salva, cifrada no banco.');
    }

    public function darCortesia(): void
    {
        $this->validate(['cortesias' => 'required|integer|min:1|max:4', 'motivoCortesia' => 'required|min:4|max:120'], ['motivoCortesia.required' => 'Escreva o motivo: ele fica no extrato.']);

        $this->tentar(fn () => Carteira::de($this->aluna)->creditar(
            quantidade: $this->cortesias,
            origem: OrigemLote::Cortesia,
            validoDe: now(),
            validoAte: now()->addDays(30)->endOfDay(),
            modalidades: $this->aluna->matriculaAtual?->modalidadesPermitidas(),
            descricao: 'Cortesia: '.$this->motivoCortesia,
            por: Auth::user(),
            motivo: $this->motivoCortesia,
        ), 'Cortesia lançada. Vale por 30 dias.');

        $this->reset('cortesias', 'motivoCortesia');
        unset($this->lotes, $this->movimentos);
    }

    public function render(): View
    {
        return view('livewire.recepcao.ficha-da-aluna');
    }
}
