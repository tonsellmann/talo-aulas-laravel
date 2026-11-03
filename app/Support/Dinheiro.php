<?php

namespace App\Support;

final class Dinheiro
{
    public static function formatar(int $centavos): string
    {
        return 'R$ '.number_format($centavos / 100, 2, ',', '.');
    }
}
