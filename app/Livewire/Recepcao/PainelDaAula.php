<?php

namespace App\Livewire\Recepcao;

use App\Actions\CancelarAulaPeloEstudio;
use App\Actions\CancelarReserva;
use App\Actions\RegistrarPresenca;
use App\Actions\ReservarAula;
use App\Enums\OrigemReserva;
use App\Enums\Papel;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\Aula;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/** Painel lateral de uma aula: lista, presença, encaixe, fila e cancelamento.
 *
 * @property-read Aula|null $aula
 * @property-read Collection<int, Reserva> $lista
 * @property-read Collection<int, EntradaNaFila> $fila
 * @property-read Collection<int, User> $sugestoes
 */
class PainelDaAula extends Component
{
    use AvisaRegras;

    public ?int $aulaId = null;

    public string $busca = '';

    public string $motivo = '';

    public bool $cancelandoAula = false;

    public ?int $desmarcando = null;

    public bool $abonar = false;

    #[On('abrir-aula')]
    public function abrir(int $aula): void
    {
        $this->reset('busca', 'motivo', 'cancelandoAula', 'desmarcando', 'abonar');
        $this->aulaId = $aula;
    }

    public function fechar(): void
    {
        $this->aulaId = null;
    }

    #[Computed]
    public function aula(): ?Aula
    {
        return $this->aulaId ? Aula::query()->with('modalidade', 'instrutora')->find($this->aulaId) : null;
    }

    /** @return Collection<int, Reserva> */
    #[Computed]
    public function lista(): Collection
    {
        $aula = $this->aula;

        return $aula?->reservas()->with('aluna', 'lote')->orderBy('created_at')->get()->each->setRelation('aula', $aula) ?? collect();
    }

    /** @return Collection<int, EntradaNaFila> */
    #[Computed]
    public function fila(): Collection
    {
        return $this->aula?->fila()->with('aluna')->get() ?? collect();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function sugestoes(): Collection
    {
        if (mb_strlen($this->busca) < 2) {
            return collect();
        }

        $jaNaAula = $this->lista->filter(fn (Reserva $r) => $r->status->ocupaVaga())->pluck('aluna_id');

        return User::query()->where('papel', Papel::Aluna)
            ->where('name', 'like', '%'.$this->busca.'%')
            ->whereNotIn('id', $jaNaAula)
            ->orderBy('name')->limit(6)->get();
    }

    public function encaixar(int $aluna, ReservarAula $reservar): void
    {
        $pessoa = User::query()->findOrFail($aluna);
        $this->tentar(fn () => $reservar($pessoa, $this->aula, OrigemReserva::Recepcao, Auth::user()), $pessoa->primeiroNome().' está na aula. Ela recebeu a confirmação no WhatsApp.');
        $this->busca = '';
        $this->atualizar();
    }

    public function presenca(int $reserva, bool $presente, RegistrarPresenca $registrar): void
    {
        $reserva = $this->aula->reservas()->findOrFail($reserva);
        $this->tentar(fn () => $registrar($reserva, $presente), $presente ? 'Presença marcada.' : 'Presença desfeita.');
        $this->atualizar();
    }

    public function desmarcar(CancelarReserva $cancelar): void
    {
        $reserva = $this->aula->reservas()->with('aluna')->findOrFail($this->desmarcando);
        $this->tentar(fn () => $cancelar($reserva, Auth::user(), $this->abonar), 'Reserva de '.$reserva->aluna->primeiroNome().' desmarcada. A vaga foi para a fila.');
        $this->reset('desmarcando', 'abonar');
        $this->atualizar();
    }

    public function cancelarAula(CancelarAulaPeloEstudio $cancelar): void
    {
        $this->validate(['motivo' => 'required|min:4|max:120'], ['motivo.required' => 'Escreva o motivo: ele vai na mensagem para as alunas.']);
        $this->tentar(fn () => $cancelar($this->aula, $this->motivo, Auth::user()), 'Aula cancelada. As alunas receberam a aula de volta e o aviso no WhatsApp.');
        $this->reset('cancelandoAula', 'motivo');
        $this->atualizar();
    }

    private function atualizar(): void
    {
        unset($this->aula, $this->lista, $this->fila, $this->sugestoes);
        $this->dispatch('aula-mudou');
    }

    public function render(): View
    {
        return view('livewire.recepcao.painel-da-aula');
    }
}
