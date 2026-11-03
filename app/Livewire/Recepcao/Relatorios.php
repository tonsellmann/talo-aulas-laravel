<?php

namespace App\Livewire\Recepcao;

use App\Enums\StatusCobranca;
use App\Enums\StatusFila;
use App\Enums\StatusReserva;
use App\Models\Aula;
use App\Models\Cobranca;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::recepcao')]
#[Title('Relatórios')]
class Relatorios extends Component
{
    public const DIAS = 21;

    /**
     * Ocupação média por dia da semana e horário, nas aulas que já aconteceram.
     *
     * @return array{horarios: list<string>, celulas: array<string, array{media: float, aulas: int}>}
     */
    #[Computed]
    public function ocupacao(): array
    {
        $aulas = Aula::query()->whereNull('cancelada_em')
            ->whereBetween('inicio', [now()->subDays(self::DIAS)->startOfDay(), now()])
            ->get(['inicio', 'ocupadas', 'capacidade']);

        $celulas = $aulas->groupBy(fn (Aula $a) => $a->inicio->dayOfWeekIso.' '.$a->inicio->format('H:i'))
            ->map(fn ($grupo) => ['media' => $grupo->avg(fn (Aula $a) => $a->ocupadas / max(1, $a->capacidade)), 'aulas' => $grupo->count()]);

        return [
            'horarios' => $aulas->map(fn (Aula $a) => $a->inicio->format('H:i'))->unique()->sort()->values()->all(),
            'celulas' => $celulas->all(),
        ];
    }

    /**
     * Horários que mais acumulam fila: candidatos a uma turma nova.
     *
     * @return Collection<int, array{rotulo: string, total: int, aulas: int}>
     */
    #[Computed]
    public function filas(): Collection
    {
        return EntradaNaFila::query()
            ->whereIn('status', [StatusFila::Aguardando, StatusFila::Oferta, StatusFila::Expirada, StatusFila::Encerrada, StatusFila::Confirmada])
            ->whereHas('aula', fn ($q) => $q->where('inicio', '>=', now()->subDays(self::DIAS)))
            ->with('aula.modalidade')->get()
            ->groupBy(fn ($e) => $e->aula->modalidade->nome.'|'.$e->aula->inicio->translatedFormat('l').'|'.$e->aula->inicio->format('H:i'))
            ->map(fn ($g, $chave) => ['rotulo' => str_replace('|', ', ', $chave), 'total' => $g->count(), 'aulas' => $g->pluck('aula_id')->unique()->count()])
            ->sortByDesc('total')->take(5)->values();
    }

    /** @return list<array{nome: string, cor: string, taxa: float, total: int}> */
    #[Computed]
    public function faltas(): array
    {
        return Reserva::query()
            ->whereIn('status', [StatusReserva::Presente, StatusReserva::Falta])
            ->whereHas('aula', fn ($q) => $q->where('inicio', '>=', now()->subDays(self::DIAS)))
            ->with('aula.modalidade')->get()
            ->groupBy(fn ($r) => $r->aula->modalidade->nome)
            ->map(fn ($g, $nome) => ['nome' => $nome, 'cor' => $g->first()->aula->modalidade->cor, 'total' => $g->count(), 'taxa' => $g->where('status', StatusReserva::Falta)->count() / max(1, $g->count())])
            ->sortByDesc('taxa')->values()->all();
    }

    /** @return array{recebido: int, a_receber: int, divergente: int} */
    #[Computed]
    public function dinheiro(): array
    {
        return [
            'recebido' => (int) Cobranca::query()->where('status', StatusCobranca::Paga)->where('paga_em', '>=', now()->subDays(30))->sum('valor_pago_centavos'),
            'a_receber' => (int) Cobranca::query()->where('status', StatusCobranca::Pendente)->sum('valor_centavos'),
            'divergente' => (int) Cobranca::query()->where('status', StatusCobranca::Divergente)->selectRaw('coalesce(sum(valor_centavos - valor_pago_centavos), 0) as falta')->value('falta'),
        ];
    }

    public function render(): View
    {
        return view('livewire.recepcao.relatorios');
    }
}
