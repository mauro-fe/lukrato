<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario;
use Illuminate\Database\Capsule\Manager as DB;

class ProfileApiController
{
    // POST /api/profile (multipart/form-data)
    public function update(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::json(['status' => 'error', 'message' => 'Não autenticado'], 401);
                return;
            }

            // ===== Helpers =====
            $onlyDigits = fn(string $s) => preg_replace('/\D+/', '', $s);

            $cpfIsValid = function (?string $cpf) use ($onlyDigits): bool {
                $cpf = $onlyDigits((string)$cpf);
                if (strlen($cpf) !== 11) return false;
                if (preg_match('/^(\d)\1{10}$/', $cpf)) return false; // todos iguais

                for ($t = 9; $t < 11; $t++) {
                    $d = 0;
                    for ($c = 0; $c < $t; $c++) {
                        $d += (int)$cpf[$c] * (($t + 1) - $c);
                    }
                    $d = ((10 * $d) % 11) % 10;
                    if ((int)$cpf[$t] !== $d) return false;
                }
                return true;
            };

            $normalizeDate = function (?string $d): ?string {
                if (!$d) return null;
                $ts = strtotime($d);
                return $ts ? date('Y-m-d', $ts) : null;
            };

            $sanitizePhone = function (?string $tel) use ($onlyDigits): ?string {
                $tel = $onlyDigits((string)$tel);
                if ($tel === '') return null;
                if (!in_array(strlen($tel), [10, 11], true)) return null; // 10 fixo, 11 celular
                return $tel;
            };

            // ===== Entrada =====
            $nome     = trim((string)($_POST['nome']  ?? $user->nome ?? ''));
            $email    = trim((string)($_POST['email'] ?? $user->email ?? ''));
            // username é opcional
            $username = trim((string)($_POST['username'] ?? ($user->username ?? '')));

            // Novos campos
            $rawCpf            = (string)($_POST['cpf']             ?? ($user->cpf ?? ''));
            $rawDataNascimento = (string)($_POST['data_nascimento'] ?? ($user->data_nascimento ?? ''));
            $rawTelefone       = (string)($_POST['telefone']        ?? ($user->telefone ?? ''));
            $sexo              = (string)($_POST['sexo']            ?? ($user->sexo ?? ''));

            // ===== Validações =====
            if ($nome === '') {
                Response::json(['status' => 'error', 'message' => 'Nome é obrigatório.'], 422);
                return;
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::json(['status' => 'error', 'message' => 'E-mail inválido.'], 422);
                return;
            }

            if ($username !== '' && strlen($username) < 3) {
                Response::json(['status' => 'error', 'message' => 'Username deve ter ao menos 3 caracteres.'], 422);
                return;
            }

            $cpf = $onlyDigits($rawCpf);
            if ($cpf !== '' && !$cpfIsValid($cpf)) {
                Response::json(['status' => 'error', 'message' => 'CPF inválido.'], 422);
                return;
            }

            $dataNascimento = $normalizeDate($rawDataNascimento);
            if ($rawDataNascimento !== '' && !$dataNascimento) {
                Response::json(['status' => 'error', 'message' => 'Data de nascimento inválida.'], 422);
                return;
            }
            if ($dataNascimento && strtotime($dataNascimento) > strtotime('today')) {
                Response::json(['status' => 'error', 'message' => 'Data de nascimento não pode ser futura.'], 422);
                return;
            }

            $telefone = $sanitizePhone($rawTelefone);
            if ($rawTelefone !== '' && !$telefone) {
                Response::json(['status' => 'error', 'message' => 'Telefone inválido. Informe DDD + número.'], 422);
                return;
            }

            $sexo = strtoupper(trim($sexo));
            $allowedSexo = ['', 'M', 'F', 'O', 'N'];
            if (!in_array($sexo, $allowedSexo, true)) {
                Response::json(['status' => 'error', 'message' => 'Valor de sexo inválido.'], 422);
                return;
            }

            // Troca de senha (opcional)
            $current = (string)($_POST['senha_atual'] ?? '');
            $nova    = (string)($_POST['nova_senha']  ?? '');
            $conf    = (string)($_POST['conf_senha']  ?? '');

            if ($nova !== '' || $conf !== '') {
                if ($nova !== $conf) {
                    Response::json(['status' => 'error', 'message' => 'Confirmação de senha não confere.'], 422);
                    return;
                }
                if (strlen($nova) < 6) {
                    Response::json(['status' => 'error', 'message' => 'Nova senha deve ter pelo menos 6 caracteres.'], 422);
                    return;
                }
                if (!password_verify($current, $user->password)) {
                    Response::json(['status' => 'error', 'message' => 'Senha atual incorreta.'], 422);
                    return;
                }
            }

            // Unicidade
            if (Usuario::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                Response::json(['status' => 'error', 'message' => 'Este e-mail já está em uso.'], 422);
                return;
            }
            if ($username !== '' && Usuario::where('username', $username)->where('id', '!=', $user->id)->exists()) {
                Response::json(['status' => 'error', 'message' => 'Este username já está em uso.'], 422);
                return;
            }
            if ($cpf !== '' && Usuario::where('cpf', $cpf)->where('id', '!=', $user->id)->exists()) {
                Response::json(['status' => 'error', 'message' => 'Este CPF já está em uso.'], 422);
                return;
            }

            // ===== Persistência (transação) =====
            DB::connection()->beginTransaction();

            // Campos básicos
            $user->nome  = $nome;
            $user->email = $email;
            if ($username !== '') {
                $user->username = $username;
            }

            // Novos campos (armazenar somente dígitos onde faz sentido)
            if ($cpf !== '' || isset($_POST['cpf'])) {
                $user->cpf = $cpf !== '' ? $cpf : null;
            }
            if ($rawTelefone !== '' || isset($_POST['telefone'])) {
                $user->telefone = $telefone ?? null;
            }
            if ($rawDataNascimento !== '' || isset($_POST['data_nascimento'])) {
                $user->data_nascimento = $dataNascimento ?? null;
            }
            if (isset($_POST['sexo'])) {
                $user->sexo = $sexo !== '' ? $sexo : null;
            }

            // Senha (se trocada)
            if ($nova !== '' && $nova === $conf) {
                $user->password = password_hash($nova, PASSWORD_DEFAULT);
            }

            $user->save();
            DB::connection()->commit();

            Response::json([
                'ok'   => true,
                'user' => [
                    'id'              => (int)$user->id,
                    'nome'            => (string)$user->nome,
                    'email'           => (string)$user->email,
                    'username'        => (string)($user->username ?? ''),
                    'cpf'             => (string)($user->cpf ?? ''),
                    'telefone'        => (string)($user->telefone ?? ''),
                    'data_nascimento' => (string)($user->data_nascimento ?? ''),
                    'sexo'            => (string)($user->sexo ?? ''),
                ]
            ]);
        } catch (\Throwable $e) {
            try { DB::connection()->rollBack(); } catch (\Throwable $_) {}
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }
}
