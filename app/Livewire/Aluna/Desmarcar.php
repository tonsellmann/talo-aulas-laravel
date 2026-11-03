<?php

namespace App\Livewire\Aluna;

use App\Actions\CancelarReserva;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\Reserva;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Confirmação de desmarcar: antes de a aluna confirmar, mostra o que vai
 * acontecer com a aula (volta, vira reposição ou se perde).
 */
class Desmarcar extends Component
{
    use AvisaRegras;

    public ?int $reservaId = null;

    #[On('pedir-desmarcar')]
    public function abrir(int $reserva): void
    {
        $this->reservaId = $reserva;
    }

    /** @return array{reserva: Reserva, no_prazo: bool, prazo: CarbonImmutable, reposicoes: int}|null */
    #[Computed]
    public function consequencia(): ?array
    {
        $reserva = $this->reservaId ? Auth::user()->reservas()->with('aula.modalidade')->find($this->reservaId) : null;

        return $reserva ? ['reserva' => $reserva] + CancelarReserva::consequencia($reserva) : null;
    }

    public function confirmar(CancelarReserva $cancelar): void
    {
        $reserva = Auth::user()->reservas()->findOrFail($this->reservaId);

        $ok = $this->tentar(fn () => $cancelar($reserva, Auth::user()), fn (Reserva $r) => $r->status->value === 'cancelada'
            ? 'Desmarcado. A aula voltou para o seu saldo.'
            : 'Desmarcado depois do prazo. O aviso chegou no WhatsApp.');

        $this->reservaId = null;

        if ($ok) {
            $this->dispatch('agenda-mudou');
        }
    }

    public function render(): View
    {
        return view('livewire.aluna.desmarcar');
    }
}
