<?php

namespace App\Livewire\Demonstracao;

use App\Actions\RodarRotinas;
use App\Enums\Papel;
use App\Models\Mensagem;
use App\Models\User;
use App\Support\Demonstracao;
use App\Support\Relogio;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Barra da demonstração: trocar de pessoa, avançar o relógio e ver o que
 * teria chegado no WhatsApp. Só aparece com TALO_DEMONSTRACAO ligado.
 */
class Barra extends Component
{
    public string $voltarPara = '/';

    public bool $mensagensAbertas = false;

    public function mount(): void
    {
        abort_unless(config('talo.demonstracao'), 404);
        $this->voltarPara = request()->fullUrl();
    }

    /** @return Collection<int, User> */
    #[Computed]
    public function pessoas(): Collection
    {
        return User::query()->orderByRaw("case papel when 'aluna' then 2 else 1 end")->orderBy('name')->get();
    }

    /** @return Builder<Mensagem> */
    private function minhasMensagens(): Builder
    {
        $usuario = Auth::user();

        return Mensagem::query()
            ->when($usuario?->papel === Papel::Aluna, fn ($q) => $q->where('aluna_id', $usuario->id))
            ->when($usuario?->papel === Papel::Instrutora, fn ($q) => $q->whereRaw('1 = 0'));
    }

    #[Computed]
    public function naoLidas(): int
    {
        return Auth::check() ? $this->minhasMensagens()->whereNull('lida_em')->count() : 0;
    }

    /** @return Collection<int, Mensagem> */
    #[Computed]
    public function mensagens(): Collection
    {
        return $this->minhasMensagens()->with('aluna')->latest('created_at')->latest('id')->limit(60)->get();
    }

    public function entrarComo(int $id): void
    {
        $pessoa = User::query()->findOrFail($id);
        Auth::login($pessoa);
        session()->regenerate();

        $this->redirectRoute($pessoa->papel->inicio(), navigate: true);
    }

    public function avancar(int $minutos): void
    {
        Relogio::avancar($minutos * 60);
        $feito = app(RodarRotinas::class)();

        $partes = array_filter([
            $feito['lembretes'] ? "{$feito['lembretes']} lembrete(s)" : null,
            $feito['ofertas_vencidas'] ? "{$feito['ofertas_vencidas']} oferta(s) vencida(s)" : null,
            $feito['aulas_fechadas'] ? "{$feito['aulas_fechadas']} aula(s) fechada(s)" : null,
            $feito['lotes_vencidos'] ? "{$feito['lotes_vencidos']} lote(s) de aulas vencido(s)" : null,
            $feito['pix_gerados'] ? "{$feito['pix_gerados']} Pix de renovação" : null,
        ]);

        session()->flash('aviso', [
            'tipo' => 'ok',
            'texto' => 'Relógio em '.now()->translatedFormat('l, H:i').'. '.($partes ? 'As rotinas rodaram: '.implode(', ', $partes).'.' : 'As rotinas rodaram e não havia nada vencido.'),
        ]);

        $this->redirect($this->voltarPara, navigate: true);
    }

    public function recomecar(): void
    {
        Demonstracao::montar();
        Auth::logout();
        session()->invalidate();

        $this->redirectRoute('entrar');
    }

    public function abrirMensagens(): void
    {
        $this->mensagensAbertas = true;
        $this->minhasMensagens()->whereNull('lida_em')->update(['lida_em' => now()]);
        unset($this->naoLidas);
    }

    public function render(): View
    {
        return view('livewire.demonstracao.barra');
    }
}
