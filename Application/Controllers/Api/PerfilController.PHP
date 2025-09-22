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
    public function update(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::error('Não autenticado', 401);
                return;
            }

            $digits = fn(string $s) => preg_replace('/\D+/', '', $s);

            $cpfIsValid = function (?string $cpf) use ($digits): bool {
                $cpf = $digits((string)$cpf);
                if (strlen($cpf) !== 11) return false;
                if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;
                for ($t = 9; $t < 11; $t++) {
                    $d = 0;
                    for ($c = 0; $c < $t; $c++) $d += (int)$cpf[$c] * (($t + 1) - $c);
                    $d = ((10 * $d) % 11) % 10;
                    if ((int)$cpf[$t] !== $d) return false;
                }
                return true;
            };

            $parseDate = function (?string $d): ?string {
                if (!$d) return null;
                $d = trim($d);
                if (preg_match('~^\d{2}/\d{2}/\d{4}$~', $d)) {
                    [$dd, $mm, $yy] = explode('/', $d);
                    if (checkdate((int)$mm, (int)$dd, (int)$yy)) return sprintf('%04d-%02d-%02d', $yy, $mm, $dd);
                    return null;
                }
                $ts = strtotime($d);
                return $ts ? date('Y-m-d', $ts) : null;
            };

            $mapSexo = function (?string $s): ?string {
                $s = strtoupper(trim((string)$s));
                if ($s === '' || $s === 'SELECIONE') return null;
                return match ($s) {
                    'M', 'MASCULINO'        => 'Masculino',
                    'F', 'FEMININO'         => 'Feminino',
                    'O', 'OUTRO'            => 'Outro',
                    'N', 'NAO INFORMADO', 'NÃO INFORMADO', 'NAOINFORMADO' => 'Não informado',
                    default                 => null
                };
            };

            $splitPhone = function (?string $raw) use ($digits): array {
                $num = $digits((string)$raw);
                if ($num === '' || !in_array(strlen($num), [10, 11], true)) {
                    return [null, null]; 
                }
                $ddd = substr($num, 0, 2);
                $local = substr($num, 2);
                return [$ddd, $local];
            };

            $nome     = trim((string)($_POST['nome']            ?? $user->nome ?? ''));
            $email    = trim((string)($_POST['email']           ?? $user->email ?? ''));
            $username = trim((string)($_POST['username']        ?? $user->username ?? ''));
            $rawCpf   = (string)($_POST['cpf']                  ?? '');
            $rawTel   = (string)($_POST['telefone']             ?? '');
            $rawSexo  = (string)($_POST['sexo']                 ?? '');
            $rawNasc  = (string)($_POST['data_nascimento']      ?? '');

            if ($nome === '') {
                Response::validationError(['nome' => 'Nome é obrigatório.']);
                return;
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::validationError(['email' => 'E-mail inválido.']);
                return;
            }
            if ($username !== '' && strlen($username) < 3) {
                Response::validationError(['username' => 'Min. 3 caracteres.']);
                return;
            }

            $cpf = $digits($rawCpf);
            if ($cpf !== '' && !$cpfIsValid($cpf)) {
                Response::validationError(['cpf' => 'CPF inválido.']);
                return;
            }

            $nasc = null;
            if ($rawNasc !== '') {
                $nasc = $parseDate($rawNasc);
                if (!$nasc) {
                    Response::validationError(['data_nascimento' => 'Data inválida.']);
                    return;
                }
                if (strtotime($nasc) > strtotime('today')) {
                    Response::validationError(['data_nascimento' => 'Data no futuro não permitida.']);
                    return;
                }
            }

            [$dddCode, $foneLocal] = $splitPhone($rawTel);
            if ($rawTel !== '' && ($dddCode === null || $foneLocal === null)) {
                Response::validationError(['telefone' => 'Telefone inválido. Use DDD + número.']);
                return;
            }

            $sexoLabel = $mapSexo($rawSexo); 

            if (Usuario::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                Response::validationError(['email' => 'Este e-mail já está em uso.']);
                return;
            }
            if (
                $username !== '' &&
                Usuario::where('username', $username)->where('id', '!=', $user->id)->exists()
            ) {
                Response::validationError(['username' => 'Este username já está em uso.']);
                return;
            }
            if ($cpf !== '') {
                $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
                $cpfEmUso = Documento::where('numero', $cpf)
                    ->where('id_tipo', $tipoCpf->id_tipo)
                    ->where('id_usuario', '!=', $user->id)
                    ->exists();
                if ($cpfEmUso) {
                    Response::validationError(['cpf' => 'Este CPF já está em uso.']);
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
                $foneLocal
            ) {
                $user->nome = $nome;
                $user->email = $email;
                if ($username !== '') $user->username = $username;
                $user->data_nascimento = $nasc;

                if ($sexoLabel !== null) {
                    $sexo = Sexo::firstOrCreate(['nm_sexo' => $sexoLabel]);
                    $user->id_sexo = $sexo->id_sexo;
                } else {
                    $user->id_sexo = null;
                }
                if (property_exists($user, 'cpf')) $user->cpf = null;
                if (property_exists($user, 'telefone')) $user->telefone = null;
                if (property_exists($user, 'sexo')) $user->sexo = null;

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

                if ($dddCode !== null && $foneLocal !== null) {
                    $ddd = Ddd::firstOrCreate(['codigo' => $dddCode]);
                    $tel = Telefone::where('id_usuario', $user->id)->orderBy('id_telefone')->first();
                    if ($tel) {
                        $tel->numero = $foneLocal;
                        $tel->id_ddd = $ddd->id_ddd;
                        $tel->tipo   = $tel->tipo ?? 'celular';
                        $tel->save();
                    } else {
                        Telefone::create([
                            'id_usuario' => $user->id,
                            'numero'     => $foneLocal,
                            'id_ddd'     => $ddd->id_ddd,
                            'tipo'       => 'celular',
                        ]);
                    }
                } else {
                   
                }
            });

            $tipoCpf = TipoDocumento::firstOrCreate(['ds_tipo' => 'CPF']);
            $cpfAtual = Documento::where('id_usuario', $user->id)
                ->where('id_tipo', $tipoCpf->id_tipo)
                ->value('numero');

            $tel = Telefone::where('id_usuario', $user->id)->orderBy('id_telefone')->first();
            $telefoneFmt = null;
            if ($tel) {
                $ddd = $tel->id_ddd ? Ddd::find($tel->id_ddd)?->codigo : null;
                $telefoneFmt = $ddd ? sprintf('(%s) %s', $ddd, $tel->numero) : $tel->numero;
            }

            $sexoTxt = $user->id_sexo ? (Sexo::find($user->id_sexo)?->nm_sexo ?? null) : null;

            Response::success([
                'message' => 'Perfil atualizado com sucesso',
                'user' => [
                    'id'              => (int)$user->id,
                    'nome'            => (string)$user->nome,
                    'email'           => (string)$user->email,
                    'username'        => (string)($user->username ?? ''),
                    'data_nascimento' => (string)($user->data_nascimento ?? ''),
                    'sexo'            => (string)($sexoTxt ?? ''),
                    'cpf'             => (string)($cpfAtual ?? ''),
                    'telefone'        => (string)($telefoneFmt ?? ''),
                ]
            ]);
        } catch (\Throwable $e) {
            Response::error($e->getMessage(), 500);
        }
    }
}
