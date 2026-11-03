<?php

namespace App\Livewire\Aluna;

use App\Actions\AceitarOferta;
use App\Actions\SairDaFila;
use App\Enums\StatusCobranca;
use App\Enums\StatusFila;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\Cobranca;
use App\Models\EntradaNaFila;
use App\Models\LoteDeCredito;
use App\Models\Reserva;
use App\Models\User;
use App\Support\Carteira;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::aluna')]
#[Title('Minhas aulas')]
class Inicio extends Component
{
    use AvisaRegras;

    private function aluna(): User
    {
        return Auth::user();
    }

    /** @return Collection<int, Reserva> */
    #[Computed]
    public function proximas(): Collection
    {
        return $this->aluna()->proximasReservas()->limit(6)->get();
    }

    /** @return Collection<int, EntradaNaFila> */
    #[Computed]
    public function ofertas(): Collection
    {
        return $this->aluna()->filas()->where('status', StatusFila::Oferta)
            ->where('oferta_expira_em', '>=', now())
            ->with('aula.modalidade', 'aula.instrutora')->get();
    }

    /** @return Collection<int, EntradaNaFila> */
    #[Computed]
    public function filas(): Collection
    {
        return $this->aluna()->filas()->where('status', StatusFila::Aguardando)
            ->whereHas('aula', fn ($q) => $q->futuras())
            ->with('aula.modalidade')->get();
    }

    /** @return Collection<int, LoteDeCredito> */
    #[Computed]
    public function lotes(): Collection
    {
        return Carteira::de($this->aluna())->lotesAtivos();
    }

    /** Lote do plano no ciclo atual, mesmo zerado, para mostrar quanto já foi usado. */
    #[Computed]
    public function loteDoPlano(): ?LoteDeCredito
    {
        return $this->aluna()->lotes()->where('origem', 'ciclo')
            ->where('valido_de', '<=', now())->where('valido_ate', '>=', now())->first();
    }

    #[Computed]
    public function pix(): ?Cobranca
    {
        return $this->aluna()->cobrancas()->where('status', StatusCobranca::Pendente)->latest('id')->first();
    }

    public function aceitar(int $entrada, AceitarOferta $aceitar): void
    {
        $entrada = $this->aluna()->filas()->findOrFail($entrada);
        $this->tentar(fn () => $aceitar($entrada), 'A vaga é sua. Já está nas suas próximas aulas.');
    }

    public function sairDaFila(int $entrada, SairDaFila $sair): void
    {
        $entrada = $this->aluna()->filas()->with('aula')->findOrFail($entrada);
        $this->tentar(fn () => $sair($entrada), 'Você saiu da fila.');
    }

    #[On('agenda-mudou')]
    public function atualizar(): void
    {
        unset($this->proximas, $this->lotes, $this->loteDoPlano, $this->filas, $this->ofertas);
    }

    public function render(): View
    {
        return view('livewire.aluna.inicio');
    }
}
