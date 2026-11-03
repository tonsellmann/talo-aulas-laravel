<?php

namespace App\Exceptions;

use App\Models\Aula;
use DomainException;

/**
 * Uma regra do estúdio impediu a ação. A mensagem vai direto para a tela,
 * então é escrita para a aluna, não para o programador.
 */
class RegraDeNegocio extends DomainException
{
    public function __construct(string $mensagem, public readonly string $codigo = 'regra')
    {
        parent::__construct($mensagem);
    }

    public static function aulaLotada(): self
    {
        return new self('Essa aula lotou. Você pode entrar na fila de espera.', 'lotada');
    }

    public static function semCredito(Aula $aula): self
    {
        return new self(
            'Você não tem aula disponível que valha para '.$aula->modalidade->nome.' em '.$aula->inicio->format('d/m').'. Renove o plano ou fale com a recepção.',
            'sem_credito',
        );
    }

    public static function jaReservada(): self
    {
        return new self('Você já está nessa aula.', 'ja_reservada');
    }

    public static function aulaCancelada(): self
    {
        return new self('Essa aula foi cancelada pelo estúdio.', 'cancelada');
    }

    public static function aulaJaComecou(): self
    {
        return new self('Essa aula já começou.', 'comecou');
    }

    public static function agendaFechada(Aula $aula): self
    {
        return new self(
            'A agenda dessa aula abre em '.$aula->inicio->subDays(config('talo.reserva.abre_dias_antes'))->translatedFormat('d/m \à\s H:i').'.',
            'agenda_fechada',
        );
    }

    public static function emCimaDaHora(): self
    {
        return new self('Falta menos de meia hora para a aula. Para entrar agora, fale com a recepção.', 'em_cima_da_hora');
    }
}
