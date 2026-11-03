<?php

namespace App\Livewire\Aluna;

use App\Actions\ProcessarWebhookPix;
use App\Enums\StatusCobranca;
use App\Livewire\Concerns\AvisaRegras;
use App\Models\Ciclo;
use App\Models\Cobranca;
use App\Models\Matricula;
use App\Support\Pix\GatewayDeDemonstracao;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * @property-read Matricula|null $matricula
 * @property-read Ciclo|null $ciclo
 * @property-read Cobranca|null $pix
 * @property-read Collection<int, Cobranca> $historico
 */
#[Layout('layouts::aluna')]
#[Title('Plano')]
class Plano extends Component
{
    use AvisaRegras;

    #[Computed]
    public function matricula(): ?Matricula
    {
        return Auth::user()->matriculaAtual()->with('plano', 'modalidade')->first();
    }

    #[Computed]
    public function ciclo(): ?Ciclo
    {
        return $this->matricula?->cicloEm() ?? $this->matricula?->ultimoCiclo;
    }

    #[Computed]
    public function pix(): ?Cobranca
    {
        return Auth::user()->cobrancas()->where('status', StatusCobranca::Pendente)->latest('id')->first();
    }

    /** @return Collection<int, Cobranca> */
    #[Computed]
    public function historico(): Collection
    {
        return Auth::user()->cobrancas()->where('status', '!=', StatusCobranca::Pendente)->latest('ciclo_inicio')->limit(6)->get();
    }

    /**
     * Na demonstração não existe banco de verdade: este botão monta o aviso
     * de pagamento que o gateway mandaria, assina com HMAC e entrega ao mesmo
     * código que recebe o webhook em produção.
     */
    public function simularPagamento(ProcessarWebhookPix $processar): void
    {
        abort_unless(config('talo.demonstracao'), 404);
        $cobranca = $this->pix ?? abort(404);

        $corpo = json_encode(GatewayDeDemonstracao::avisoDePagamento($cobranca));
        $aviso = Request::create('/webhooks/pix', 'POST', server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_TALO_ASSINATURA' => GatewayDeDemonstracao::assinar($corpo),
        ], content: $corpo);

        [$status, $resultado] = $processar($aviso);

        $this->dispatch('aviso', tipo: $status === 200 && $resultado === 'ciclo_criado' ? 'ok' : 'erro', texto: match ($resultado) {
            'ciclo_criado' => 'Pix recebido. As aulas do próximo ciclo já estão no seu saldo.',
            default => 'O aviso do pagamento voltou com: '.$resultado,
        });

        unset($this->pix, $this->historico, $this->ciclo, $this->matricula);
    }

    public function render(): View
    {
        return view('livewire.aluna.plano');
    }
}
