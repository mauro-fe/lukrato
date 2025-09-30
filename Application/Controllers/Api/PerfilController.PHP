<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;
use Application\Models\Usuario;
use Application\Models\Sexo;
use Application\Models\TipoDocumento;
use Application\Models\Documento;
use Application\Models\Telefone;
use Application\Models\Ddd;

class PerfilController
{
    public function show(): void
    {
        try {
            $sessionUser = Auth::user();
            if (!$sessionUser) {
                Response::error('Nao autenticado', 401);
                return;
            }

            $user = Usuario::find((int) $sessionUser->id);
            if (!$user) {
                Response::error('Usuario nao encontrado', 404);
                return;
            }

            Response::success([
                'user' => $this->buildUserPayload($user),
            ], 'Perfil carregado');
        } catch (\Throwable $e) {
            Response::error('Erro interno', 500, ['exception' => $e->getMessage()]);
        }
    }

    public function update(): void
    {
        try {
            $sessionUser = Auth::user();
            if (!$sessionUser) {
                Response::error('Nao autenticado', 401);
                return;
            }

            $user = Usuario::findOrFail((int) $sessionUser->id);

            $nome = trim((string) ($_POST['nome'] ?? $user->nome ?? ''));
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? $user->email ?? '')));
            $username = trim((string) ($_POST['username'] ?? $user->username ?? ''));
            $rawCpf = (string) ($_POST['cpf'] ?? '');
            $rawTel = (string) ($_POST['telefone'] ?? '');
            $rawSexo = (string) ($_POST['sexo'] ?? '');
            $rawNasc = (string) ($_POST['data_nascimento'] ?? '');

            if ($nome === '') {
                Response::validationError(['nome' => 'Nome e obrigatorio.']);
                return;
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::validationError(['email' => 'E-mail invalido.']);
                return;
            }

            if ($username !== '' && mb_strlen($username) < 3) {
                Response::validationError(['username' => 'Minimo de 3 caracteres.']);
                return;
            }

            $cpf = $this->digits($rawCpf);
            if ($cpf !== '' && !$this->isValidCpf($cpf)) {
                Response::validationError(['cpf' => 'CPF invalido.']);
                return;
            }

            $nasc = null;
            if (trim($rawNasc) !== '') {
                $nasc = $this->parseDate($rawNasc);
                if (!$nasc) {
                    Response::validationError(['data_nascimento' => 'Data invalida.']);
                    return;
                }

                if (strtotime($nasc) > strtotime('today')) {
                    Response::validationError(['data_nascimento' => 'Data no futuro nao permitida.']);
                    return;
                }
            }

            [$dddCode, $foneLocal] = $this->splitPhone($rawTel);
            if ($rawTel !== '' && ($dddCode === null || $foneLocal === null)) {
                Response::validationError(['telefone' => 'Telefone invalido. Use DDD + numero.']);
                return;
            }

            $sexoLabel = $this->mapSexoLabel($rawSexo);

            if (
                Usuario::whereRaw('LOWER(email) = ?', [$email])
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                Response::validationError(['email' => 'Este e-mail ja esta em uso.']);
                return;
            }

            if (
                $username !== '' &&
                Usuario::whereRaw('LOWER(username) = ?', [mb_strtolower($username)])
                    ->where('id', '!=', $user->id)
                    ->exists()
            ) {
                Response::validationError(['username' => 'Este username ja esta em uso.']);
                return;
            }

            if ($cpf !== '') {
                $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
                $cpfEmUso = Documento::where('numero', $cpf)
                    ->where('id_tipo', $tipoCpf->id_tipo)
                    ->where('id_usuario', '!=', $user->id)
                    ->exists();

                if ($cpfEmUso) {
                    Response::validationError(['cpf' => 'Este CPF ja esta em uso.']);
                    return;
                }
            }

            DB::connection()->transaction(function () use (
                $user,
                $nome,
                $email,
                $username,
                $nasc,
                $sexoLabel,
                $cpf,
                $dddCode,
                $foneLocal,
                $rawTel
            ) {
                $user->nome = $nome;
                $user->email = $email;

                if ($username !== '') {
                    $user->username = $username;
                }

                $user->data_nascimento = $nasc;

                if ($sexoLabel !== null) {
                    $sexo = Sexo::firstOrCreate(['nm_sexo' => $sexoLabel]);
                    $user->id_sexo = $sexo->id_sexo;
                } else {
                    $user->id_sexo = null;
                }

                $user->save();

                $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
                if ($cpf !== '') {
                    Documento::updateOrCreate(
                        ['id_usuario' => $user->id, 'id_tipo' => $tipoCpf->id_tipo],
                        ['numero' => $cpf]
                    );
                } else {
                    Documento::where('id_usuario', $user->id)
                        ->where('id_tipo', $tipoCpf->id_tipo)
                        ->delete();
                }

                $tel = Telefone::where('id_usuario', $user->id)->orderBy('id_telefone')->first();

                if ($rawTel === '') {
                    if ($tel) {
                        $tel->delete();
                    }
                } else {
                    $ddd = Ddd::firstOrCreate(['codigo' => $dddCode]);
                    if ($tel) {
                        $tel->numero = $foneLocal;
                        $tel->id_ddd = $ddd->id_ddd;
                        $tel->tipo = $tel->tipo ?? 'celular';
                        $tel->save();
                    } else {
                        Telefone::create([
                            'id_usuario' => $user->id,
                            'numero'     => $foneLocal,
                            'id_ddd'     => $ddd->id_ddd,
                            'tipo'       => 'celular',
                        ]);
                    }
                }
            });

            $user->refresh();

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user'    => $this->buildUserPayload($user),
            ]);
        } catch (\Throwable $e) {
            Response::error('Erro interno', 500, ['exception' => $e->getMessage()]);
        }
    }

    private function digits(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value);
        return $digits !== null ? $digits : '';
    }

    private function isValidCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$t] !== $d) {
                return false;
            }
        }

        return true;
    }

    private function parseDate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $value)) {
            [$dd, $mm, $yy] = explode('/', $value);
            if (checkdate((int) $mm, (int) $dd, (int) $yy)) {
                return sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
            }
            return null;
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d', $ts) : null;
    }

    private function splitPhone(?string $value): array
    {
        $digits = $this->digits((string) $value);
        if ($digits === '' || !in_array(strlen($digits), [10, 11], true)) {
            return [null, null];
        }

        $ddd = substr($digits, 0, 2);
        $local = substr($digits, 2);

        return [$ddd, $local];
    }

    private function mapSexoLabel(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = $this->normalizeSexoValue($value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'Masculino',
            'F', 'FEMININO'  => 'Feminino',
            'O', 'OUTRO'     => 'Outro',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'NAO INFORMADA', 'PREFIRO NAO INFORMAR' => 'Nao informado',
            default          => null,
        };
    }

    private function mapSexoToOption(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $normalized = $this->normalizeSexoValue($value);

        return match ($normalized) {
            'M', 'MASCULINO' => 'M',
            'F', 'FEMININO'  => 'F',
            'O', 'OUTRO'     => 'O',
            'N', 'NAO INFORMADO', 'NAO-INFORMADO', 'NAO INFORMADA', 'PREFIRO NAO INFORMAR' => 'N',
            default          => '',
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

    private function formatCpf(?string $cpf): string
    {
        $digits = $this->digits((string) $cpf);
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
        $dddDigits = $this->digits((string) $ddd);
        $numDigits = $this->digits((string) $numero);

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

    private function buildUserPayload(Usuario $user): array
    {
        $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
        $cpfAtual = Documento::where('id_usuario', $user->id)
            ->where('id_tipo', $tipoCpf->id_tipo)
            ->value('numero');

        $tel = Telefone::where('id_usuario', $user->id)->orderBy('id_telefone')->first();
        $dddCodigo = null;
        $telefoneNumero = null;
        if ($tel) {
            $telefoneNumero = $tel->numero;
            if ($tel->id_ddd) {
                $ddd = Ddd::find($tel->id_ddd);
                $dddCodigo = $ddd ? $ddd->codigo : null;
            }
        }

        $sexoNome = null;
        if ($user->id_sexo) {
            $sexo = Sexo::find($user->id_sexo);
            $sexoNome = $sexo ? $sexo->nm_sexo : null;
        }

        return [
            'id'              => (int) $user->id,
            'nome'            => (string) ($user->nome ?? ''),
            'email'           => (string) ($user->email ?? ''),
            'username'        => (string) ($user->username ?? ''),
            'avatar'          => (string) ($user->avatar ?? ''),
            'data_nascimento' => $this->normalizeDate($user->data_nascimento),
            'sexo'            => $this->mapSexoToOption($sexoNome),
            'cpf'             => $this->formatCpf($cpfAtual),
            'telefone'        => $this->formatTelefone($dddCodigo, $telefoneNumero),
        ];
    }
}
