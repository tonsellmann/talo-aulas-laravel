<?php

namespace App\Livewire\Recepcao;

use App\Models\Mensagem;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::recepcao')]
#[Title('Mensagens')]
class Mensagens extends Component
{
    #[Url(as: 'tipo')]
    public string $modelo = '';

    public const MODELOS = [
        'reserva_confirmada' => 'Reserva confirmada',
        'cancelamento' => 'Desmarcou',
        'vaga_oferecida' => 'Vaga oferecida',
        'reservada_pela_fila' => 'Reservada pela fila',
        'oferta_expirou' => 'Oferta vencida',
        'vaga_sem_credito' => 'Vaga sem saldo',
        'lembrete_aula' => 'Lembrete de aula',
        'aulas_vencendo' => 'Aulas vencendo',
        'cobranca_gerada' => 'Pix de renovação',
        'pagamento_confirmado' => 'Pix recebido',
        'aula_cancelada' => 'Aula cancelada',
        'falta_registrada' => 'Falta',
    ];

    /** @return Collection<int, Mensagem> */
    #[Computed]
    public function mensagens(): Collection
    {
        return Mensagem::query()->with('aluna')
            ->when($this->modelo, fn ($q) => $q->where('modelo', $this->modelo))
            ->latest('created_at')->latest('id')->limit(100)->get();
    }

    public function render(): View
    {
        return view('livewire.recepcao.mensagens');
    }
}
