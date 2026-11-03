<?php

namespace App\Support\Pix;

/**
 * Monta o "Pix copia e cola" (BR Code), no padrão EMV do Banco Central:
 * cada campo é ID + tamanho + valor, e o código termina com um CRC16.
 */
final class BrCode
{
    public static function gerar(string $chave, int $centavos, string $nome, string $cidade, string $txid): string
    {
        $conta = self::campo('00', 'br.gov.bcb.pix').self::campo('01', $chave);

        $codigo = self::campo('00', '01')
            .self::campo('26', $conta)
            .self::campo('52', '0000')
            .self::campo('53', '986')
            .self::campo('54', number_format($centavos / 100, 2, '.', ''))
            .self::campo('58', 'BR')
            .self::campo('59', self::texto($nome, 25))
            .self::campo('60', self::texto($cidade, 15))
            .self::campo('62', self::campo('05', self::txid($txid)))
            .'6304';

        return $codigo.self::crc16($codigo);
    }

    /** CRC-16/CCITT-FALSE: polinômio 0x1021, valor inicial 0xFFFF. */
    public static function crc16(string $dados): string
    {
        $crc = 0xFFFF;

        foreach (str_split($dados) as $caractere) {
            $crc ^= ord($caractere) << 8;

            for ($bit = 0; $bit < 8; $bit++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }

    /** Confere o CRC de um código recebido. */
    public static function valido(string $codigo): bool
    {
        if (strlen($codigo) < 8 || substr($codigo, -8, 4) !== '6304') {
            return false;
        }

        return self::crc16(substr($codigo, 0, -4)) === substr($codigo, -4);
    }

    private static function campo(string $id, string $valor): string
    {
        return $id.str_pad((string) strlen($valor), 2, '0', STR_PAD_LEFT).$valor;
    }

    /** O BR Code só aceita ASCII nos campos de nome e cidade. */
    private static function texto(string $valor, int $limite): string
    {
        $semAcento = strtr($valor, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
            'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c',
            'Á' => 'A', 'À' => 'A', 'Â' => 'A', 'Ã' => 'A', 'É' => 'E', 'Ê' => 'E', 'Í' => 'I',
            'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ú' => 'U', 'Ç' => 'C',
        ]);

        return substr(preg_replace('/[^A-Za-z0-9 ]/', '', $semAcento) ?? '', 0, $limite);
    }

    private static function txid(string $txid): string
    {
        return substr(preg_replace('/[^A-Za-z0-9]/', '', $txid) ?? '', 0, 25) ?: '***';
    }
}
