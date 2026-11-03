<?php

use App\Actions\AceitarOferta;
use App\Actions\CancelarReserva;
use App\Actions\EntrarNaFila;
use App\Actions\ExpirarOfertas;
use App\Actions\ReservarAula;
use App\Enums\ModoFila;
use App\Enums\StatusFila;
use App\Enums\StatusReserva;
use App\Exceptions\RegraDeNegocio;
use App\Models\EntradaNaFila;
use App\Models\Reserva;
use App\Notifications\VagaOferecida;
use App\Notifications\VagaSemCredito;
use Illuminate\Support\Facades\Notification;

function lotar($aula): Reserva
{
    return app(ReservarAula::class)(alunaCom(), $aula, avisar: false);
}

it('não entra na fila de aula que ainda tem vaga', function () {
    expect(fn () => app(EntrarNaFila::class)(alunaCom(), aulaEm(48), ModoFila::Avisar))
        ->toThrow(RegraDeNegocio::class, 'Ainda tem vaga');
});

it('com folga, quem pediu reserva automática ganha a vaga sozinha', function () {
    $aula = aulaEm(48, capacidade: 1);
    $ocupante = lotar($aula);
    $daFila = alunaCom();
    app(EntrarNaFila::class)($daFila, $aula, ModoFila::Automatico);

    app(CancelarReserva::class)($ocupante);

    expect(Reserva::query()->where('aluna_id', $daFila->id)->sole()->status)->toBe(StatusReserva::Confirmada)
        ->and(EntradaNaFila::query()->where('aluna_id', $daFila->id)->sole()->status)->toBe(StatusFila::Confirmada);
});

it('quem pediu aviso recebe uma oferta de 30 minutos; vencida, a vaga passa para a próxima', function () {
    $aula = aulaEm(48, capacidade: 1);
    $ocupante = lotar($aula);
    [$primeira, $segunda] = [alunaCom(), alunaCom()];
    app(EntrarNaFila::class)($primeira, $aula, ModoFila::Avisar);
    $this->travel(1)->minutes();
    app(EntrarNaFila::class)($segunda, $aula, ModoFila::Avisar);

    app(CancelarReserva::class)($ocupante);

    $oferta = EntradaNaFila::query()->where('aluna_id', $primeira->id)->sole();
    expect($oferta->status)->toBe(StatusFila::Oferta)
        ->and((int) round(now()->diffInMinutes($oferta->oferta_expira_em)))->toBe(30)
        ->and(EntradaNaFila::query()->where('aluna_id', $segunda->id)->sole()->status)->toBe(StatusFila::Aguardando);
    Notification::assertSentTo($primeira, VagaOferecida::class);

    $this->travel(31)->minutes();
    app(ExpirarOfertas::class)();

    expect($oferta->fresh()->status)->toBe(StatusFila::Expirada)
        ->and(EntradaNaFila::query()->where('aluna_id', $segunda->id)->sole()->status)->toBe(StatusFila::Oferta);
});

it('perto da aula, a vaga vai para toda a fila e fica com quem confirmar primeiro', function () {
    $aula = aulaEm(1, capacidade: 1);
    $ocupante = lotar($aula);
    [$rapida, $lenta] = [alunaCom(), alunaCom()];
    app(EntrarNaFila::class)($rapida, $aula, ModoFila::Avisar);
    app(EntrarNaFila::class)($lenta, $aula, ModoFila::Avisar);

    app(CancelarReserva::class)($ocupante, abonar: true);

    // Como a tela da oferta: a entrada chega com a aluna e a aula carregadas.
    $ofertas = EntradaNaFila::query()->where('aula_id', $aula->id)->with('aluna', 'aula.modalidade')->get();
    expect($ofertas->pluck('status')->unique()->all())->toBe([StatusFila::Oferta]);

    app(AceitarOferta::class)($ofertas->firstWhere('aluna_id', $rapida->id));
    expect(fn () => app(AceitarOferta::class)($ofertas->firstWhere('aluna_id', $lenta->id)->fresh(['aluna', 'aula.modalidade'])))
        ->toThrow(RegraDeNegocio::class, 'Outra pessoa da fila confirmou antes');
    expect($ofertas->firstWhere('aluna_id', $lenta->id)->fresh()->status)->toBe(StatusFila::Aguardando);
});

it('reserva automática de quem ficou sem aula vira aviso, não erro', function () {
    $aula = aulaEm(48, capacidade: 1);
    $ocupante = lotar($aula);
    $semAula = alunaCom(0);
    app(EntrarNaFila::class)($semAula, $aula, ModoFila::Automatico);

    app(CancelarReserva::class)($ocupante);

    expect(EntradaNaFila::query()->where('aluna_id', $semAula->id)->sole()->status)->toBe(StatusFila::SemCredito);
    Notification::assertSentTo($semAula, VagaSemCredito::class);
});
