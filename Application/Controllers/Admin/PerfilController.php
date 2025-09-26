<?php

namespace Application\Controllers\Admin;

use Application\Controllers\BaseController;
use Application\Lib\Auth;

class PerfilController extends BaseController
{
    public function index(): void
    {
        $this->requireAuth();

        $user = Auth::user();
        if ($user) {
            $user->loadMissing(['cpfDocumento.tipo', 'telefonePrincipal.ddd', 'sexo']);

            $cpfNumero = $user->cpfDocumento?->numero ?? null;
            $user->setAttribute('cpf', $this->formatCpf($cpfNumero));

            $telefoneModel = $user->telefonePrincipal;
            $dddCodigo = $telefoneModel?->ddd?->codigo ?? null;
            $telefoneNumero = $telefoneModel?->numero ?? null;
            $user->setAttribute('telefone', $this->formatTelefone($dddCodigo, $telefoneNumero));

            $sexoNome = $user->sexo?->nm_sexo ?? null;
            $user->setAttribute('sexo', $this->mapSexoToOption($sexoNome));

            $user->setAttribute('data_nascimento', $this->normalizeDate($user->data_nascimento));
        }

        $this->render(
            'admin/perfil/index',
            ['user' => $user],
            'admin/partials/header',
            null
        );
    }

    private function formatCpf(?string $cpf): string
    {
        $digits = preg_replace('/\D+/', '', (string) $cpf);
        if (strlen($digits) !== 11) {
            return '';
        }

        return sprintf(
            '%s.%s.%s-%s',
            substr($digits, 0, 3),
            substr($digits, 3, 3),
            substr($digits, 6, 3),
            substr($digits, 9, 2)
        );
    }

    private function formatTelefone(?string $ddd, ?string $numero): string
    {
        $dddDigits = preg_replace('/\D+/', '', (string) $ddd);
        $numDigits = preg_replace('/\D+/', '', (string) $numero);

        if ($numDigits === '') {
            return '';
        }

        if (strlen($numDigits) > 9) {
            $numDigits = substr($numDigits, -9);
        }
        $masked = $this->maskPhone($numDigits);

        return $dddDigits !== ''
            ? sprintf('(%s) %s', $dddDigits, $masked)
            : $masked;
    }

    private function maskPhone(string $digits): string
    {
        $len = strlen($digits);
        if ($len === 9) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5);
        }
        if ($len === 8) {
            return substr($digits, 0, 4) . '-' . substr($digits, 4);
        }
        if ($len > 9) {
            return substr($digits, 0, 5) . '-' . substr($digits, 5);
        }

        return $digits;
    }

    private function mapSexoToOption(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = $this->normalizeSexoValue($value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'M',
            'F', 'FEMININO' => 'F',
            'O', 'OUTRO' => 'O',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'NAO INFORMADA', 'PREFIRO NAO INFORMAR' => 'N',
            default => '',
        };
    }

    private function normalizeSexoValue(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT', $value);
        if ($converted === false) {
            $map = [
                "\u{00C1}" => 'A',
                "\u{00C0}" => 'A',
                "\u{00C3}" => 'A',
                "\u{00C2}" => 'A',
                "\u{00C4}" => 'A',
                "\u{00E1}" => 'a',
                "\u{00E0}" => 'a',
                "\u{00E3}" => 'a',
                "\u{00E2}" => 'a',
                "\u{00E4}" => 'a',
                "\u{00C9}" => 'E',
                "\u{00C8}" => 'E',
                "\u{00CA}" => 'E',
                "\u{00CB}" => 'E',
                "\u{00E9}" => 'e',
                "\u{00E8}" => 'e',
                "\u{00EA}" => 'e',
                "\u{00EB}" => 'e',
                "\u{00CD}" => 'I',
                "\u{00CC}" => 'I',
                "\u{00CE}" => 'I',
                "\u{00CF}" => 'I',
                "\u{00ED}" => 'i',
                "\u{00EC}" => 'i',
                "\u{00EE}" => 'i',
                "\u{00EF}" => 'i',
                "\u{00D3}" => 'O',
                "\u{00D2}" => 'O',
                "\u{00D4}" => 'O',
                "\u{00D5}" => 'O',
                "\u{00D6}" => 'O',
                "\u{00F3}" => 'o',
                "\u{00F2}" => 'o',
                "\u{00F4}" => 'o',
                "\u{00F5}" => 'o',
                "\u{00F6}" => 'o',
                "\u{00DA}" => 'U',
                "\u{00D9}" => 'U',
                "\u{00DB}" => 'U',
                "\u{00DC}" => 'U',
                "\u{00FA}" => 'u',
                "\u{00F9}" => 'u',
                "\u{00FB}" => 'u',
                "\u{00FC}" => 'u',
                "\u{00C7}" => 'C',
                "\u{00E7}" => 'c',
            ];
            $converted = strtr($value, $map);
        }

        $base = $converted !== false ? $converted : $value;
        $base = str_replace(['-', '_'], ' ', $base);

        return strtoupper(trim($base));
    }

    private function normalizeDate($value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return '';
            }
            if (preg_match('~^\d{4}-\d{2}-\d{2}$~', $value)) {
                return $value;
            }
            $timestamp = strtotime($value);
            if ($timestamp) {
                return date('Y-m-d', $timestamp);
            }
        }

        return '';
    }
}
