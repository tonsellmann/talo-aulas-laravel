<?php

namespace App\Livewire\Recepcao;

use App\Actions\ConfirmarPagamento;
use App\Enums\StatusCobranca;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\Cobranca;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts::recepcao')]
#[Title('Cobranças')]
class Cobrancas extends Component
{
    use AvisaRegras;

    #[Url(as: 'situacao')]
    public string $situacao = 'pendente';

    /** @return array<string, array{n: int, total: int}> */
    #[Computed]
    public function contagens(): array
    {
        return Cobranca::query()->toBase()->selectRaw('status, count(*) as n, sum(valor_centavos) as total')->groupBy('status')->get()
            ->mapWithKeys(fn (object $linha) => [$linha->status => ['n' => (int) $linha->n, 'total' => (int) $linha->total]])->all();
    }

    /** @return Collection<int, Cobranca> */
    #[Computed]
    public function cobrancas(): Collection
    {
        return Cobranca::query()->where('status', $this->situacao)->with('aluna')
            ->orderByDesc($this->situacao === 'paga' ? 'paga_em' : 'vence_em')->limit(80)->get();
    }

    public function aceitarDiferenca(int $id, ConfirmarPagamento $confirmar): void
    {
        $c = Cobranca::query()->where('status', StatusCobranca::Divergente)->findOrFail($id);
        $this->tentar(fn () => $confirmar($c, (int) $c->valor_pago_centavos, $c->paga_em ?? now(), aceitarDiferenca: true), 'Pagamento aceito. As aulas do ciclo entraram no saldo dela.');
        unset($this->cobrancas, $this->contagens);
    }

    public function render(): View
    {
        return view('livewire.recepcao.cobrancas');
    }
}
