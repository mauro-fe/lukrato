<?php

declare(strict_types=1);

namespace Application\Services\AI\NLP;

/**
 * Normaliza expressões numéricas em português brasileiro para valores numéricos.
 *
 * Cobre:
 * - "X mil" -> X*1000 (ex: "2 mil" -> "2000", "5 mil" -> "5000")
 * - "Xk" -> X*1000 (ex: "2k" -> "2000", "1.5k" -> "1500")
 * - Números por extenso: "duzentos" -> "200", "trezentos" -> "300"
 * - Gírias monetárias: "conto", "pila", "paus", "mango", "nota"
 * - "mil" isolado -> "1000"
 *
 * IMPORTANTE: NÃO altera formatos BR tipo "1.560,00" — esses são
 * parseados corretamente pelo parseValue() downstream.
 */
class NumberNormalizer
{
    /**
     * Números cardinais por extenso -> valor numérico.
     * Ordenados do maior para o menor para evitar conflitos de substring.
     */
    private const EXTENSO_MAP = [
        // Milhares compostos
        'dez mil'          => '10000',
        'nove mil'         => '9000',
        'oito mil'         => '8000',
        'sete mil'         => '7000',
        'seis mil'         => '6000',
        'cinco mil'        => '5000',
        'quatro mil'       => '4000',
        'tres mil'         => '3000',
        'três mil'         => '3000',
        'dois mil'         => '2000',
        'duas mil'         => '2000',
        'um mil'           => '1000',

        // Centenas
        'novecentos'       => '900',
        'novecentas'       => '900',
        'oitocentos'       => '800',
        'oitocentas'       => '800',
        'setecentos'       => '700',
        'setecentas'       => '700',
        'seiscentos'       => '600',
        'seiscentas'       => '600',
        'quinhentos'       => '500',
        'quinhentas'       => '500',
        'quatrocentos'     => '400',
        'quatrocentas'     => '400',
        'trezentos'        => '300',
        'trezentas'        => '300',
        'duzentos'         => '200',
        'duzentas'         => '200',
        'cento e cinquenta' => '150',
        'cento e vinte'    => '120',
        'cem'              => '100',

        // Dezenas
        'noventa'          => '90',
        'oitenta'          => '80',
        'setenta'          => '70',
        'sessenta'         => '60',
        'cinquenta'        => '50',
        'cinqüenta'        => '50',
        'quarenta'         => '40',
        'trinta'           => '30',
        'vinte'            => '20',
        'quinze'           => '15',
        'doze'             => '12',
        'dez'              => '10',
    ];

    /**
     * Normaliza expressões numéricas na mensagem.
     *
     * @param string $message Mensagem (pode ser mixed case)
     * @return string Mensagem com valores numéricos normalizados
     */
    public static function normalize(string $message): string
    {
        $text = $message;

        // 1. "X mil" com X numérico: "2 mil" -> "2000", "1.5 mil" -> "1500", "2,5 mil" -> "2500"
        $text = preg_replace_callback(
            '/(\d+(?:[.,]\d+)?)\s*mil\b/iu',
            function ($m) {
                $num = str_replace(',', '.', $m[1]);
                $val = (float) $num * 1000;
                return (string) (int) $val;
            },
            $text
        );

        // 2. "Xk" com X numérico: "2k" -> "2000", "1.5k" -> "1500"
        $text = preg_replace_callback(
            '/(\d+(?:[.,]\d+)?)\s*k\b/iu',
            function ($m) {
                $num = str_replace(',', '.', $m[1]);
                $val = (float) $num * 1000;
                return (string) (int) $val;
            },
            $text
        );

        // 3. "mil" isolado (sem número antes): "mil reais" -> "1000 reais"
        //    Mas NÃO dentro de palavras: "milho", "milagre", "Milton", "similar"
        //    Só match quando "mil" é a palavra inteira seguida de espaço/fim/moeda
        $text = preg_replace(
            '/(?<!\d)\bmil\b(?!\w)/iu',
            '1000',
            $text
        );

        // 4. Números por extenso (processar do maior para menor)
        $lower = mb_strtolower($text);
        foreach (self::EXTENSO_MAP as $extenso => $valor) {
            if (mb_strpos($lower, $extenso) !== false) {
                // Substituir preservando posição no texto original
                $text = preg_replace(
                    '/\b' . preg_quote($extenso, '/') . '\b/iu',
                    $valor,
                    $text
                );
                $lower = mb_strtolower($text);
            }
        }

        // 5. Gírias monetárias: remover sufixo monetário (o número já está limpo)
        //    "50 conto(s)" -> "50", "100 pila(s)" -> "100", "50 paus" -> "50", "50 mango(s)" -> "50"
        $text = preg_replace(
            '/(\d+(?:[.,]\d+)?)\s*(?:contos?|pilas?|paus|mangos?|pratas?)\b/iu',
            '$1',
            $text
        );

        // 6. "uma nota" / "1 nota" = R$100 (gíria brasileira)
        $text = preg_replace('/\b(?:uma|1)\s+nota\b/iu', '100', $text);

        // 7. "meio" como valor = 50 centavos quando após número
        //    "2 e meio" = "2.50" — NÃO implementar aqui (muito ambíguo)

        return $text;
    }

    /**
     * Parseia um valor monetário no formato brasileiro para float.
     *
     * Formatos suportados:
     * - "1.560,00" -> 1560.00 (BR: ponto=milhar, vírgula=decimal)
     * - "1560,50"  -> 1560.50 (BR: vírgula=decimal)
     * - "1560.50"  -> 1560.50 (EN: ponto=decimal)
     * - "1560"     -> 1560.00 (inteiro)
     * - "42,35"    -> 42.35
     * - "1.500"    -> 1500.00 (BR: ponto=milhar, 3 dígitos após)
     *
     * @param string $raw Valor bruto como string
     * @return float Valor parseado
     */
    public static function parseValue(string $raw): float
    {
        $raw = trim($raw);

        // Remove R$ e espaços
        $raw = preg_replace('/^R?\$\s*/', '', $raw);
        $raw = trim($raw);

        if ($raw === '') {
            return 0.0;
        }

        // Formato BR completo: "1.560,00" (tem ponto E vírgula)
        if (str_contains($raw, '.') && str_contains($raw, ',')) {
            $raw = str_replace('.', '', $raw);   // Remove pontos (milhares)
            $raw = str_replace(',', '.', $raw);  // Vírgula -> ponto decimal
            return (float) $raw;
        }

        // Só vírgula: "42,35" ou "1560,50" -> vírgula é decimal
        if (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
            return (float) $raw;
        }

        // Só ponto: verificar se é milhar ou decimal
        if (str_contains($raw, '.')) {
            $parts = explode('.', $raw);
            $lastPart = end($parts);

            // Se última parte tem exatamente 3 dígitos, ponto é milhar: "1.500" -> 1500
            if (strlen($lastPart) === 3) {
                $raw = str_replace('.', '', $raw);
                return (float) $raw;
            }

            // Caso contrário, ponto é decimal: "42.35" -> 42.35
            return (float) $raw;
        }

        // Só dígitos: inteiro
        return (float) $raw;
    }
}
