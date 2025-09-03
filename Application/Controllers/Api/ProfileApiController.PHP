<?php

namespace Application\Controllers\Api;

use Application\Core\Response;
use Application\Lib\Auth;
use Application\Models\Usuario; // ajuste para o seu model de usuário
use Illuminate\Database\Capsule\Manager as DB;

class ProfileApiController
{
    // POST /api/profile (multipart/form-data)
    public function update(): void
    {
        try {
            $user = Auth::user();
            if (!$user) {
                Response::json(['status'=>'error','message'=>'Não autenticado'], 401);
                return;
            }

            // Valida campos básicos
            $nome     = trim((string)($_POST['nome']     ?? $user->nome ?? ''));
            $email    = trim((string)($_POST['email']    ?? $user->email ?? ''));
            $username = trim((string)($_POST['username'] ?? $user->username ?? ''));
            $tema     = trim((string)($_POST['tema']     ?? $user->tema ?? 'system'));
            $moeda    = trim((string)($_POST['moeda']    ?? $user->moeda ?? 'BRL'));

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Response::json(['status'=>'error','message'=>'E-mail inválido.'], 422);
                return;
            }
            if ($username === '') {
                Response::json(['status'=>'error','message'=>'Username é obrigatório.'], 422);
                return;
            }

            // Troca de senha (opcional)
            $current = (string)($_POST['senha_atual'] ?? '');
            $nova    = (string)($_POST['nova_senha']  ?? '');
            $conf    = (string)($_POST['conf_senha']  ?? '');

            if ($nova !== '' || $conf !== '') {
                if ($nova !== $conf) {
                    Response::json(['status'=>'error','message'=>'Confirmação de senha não confere.'], 422);
                    return;
                }
                if (strlen($nova) < 6) {
                    Response::json(['status'=>'error','message'=>'Nova senha deve ter pelo menos 6 caracteres.'], 422);
                    return;
                }
                // verificação da senha atual (ajuste para a sua forma de hash)
                if (!password_verify($current, $user->password)) {
                    Response::json(['status'=>'error','message'=>'Senha atual incorreta.'], 422);
                    return;
                }
                $user->password = password_hash($nova, PASSWORD_DEFAULT);
            }

            // Upload do avatar (opcional)
            if (!empty($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file  = $_FILES['avatar'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed = ['image/jpeg','image/png','image/webp'];
                if (!in_array($mime, $allowed, true)) {
                    Response::json(['status'=>'error','message'=>'Avatar deve ser JPG, PNG ou WEBP.'], 422);
                    return;
                }
                if ($file['size'] > 2*1024*1024) { // 2MB
                    Response::json(['status'=>'error','message'=>'Avatar até 2MB.'], 422);
                    return;
                }

                $ext = match($mime) {
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    default      => 'webp',
                };
                $dir = __DIR__ . '/../../../public/uploads/avatars';
                if (!is_dir($dir)) @mkdir($dir, 0775, true);

                $filename = 'u' . $user->id . '.' . $ext;
                $destPath = $dir . '/' . $filename;
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    Response::json(['status'=>'error','message'=>'Falha ao salvar avatar.'], 500);
                    return;
                }

                $user->avatar = 'uploads/avatars/' . $filename; // path público
            }

            // Salva dados básicos
            $user->nome     = $nome;
            $user->email    = $email;
            $user->username = $username;
            $user->tema     = $tema;
            $user->moeda    = $moeda;
            $user->save();

            Response::json(['ok'=>true, 'user'=>[
                'id'       => (int)$user->id,
                'nome'     => (string)$user->nome,
                'email'    => (string)$user->email,
                'username' => (string)$user->username,
                'avatar'   => (string)($user->avatar ?? ''),
                'tema'     => (string)($user->tema ?? 'system'),
                'moeda'    => (string)($user->moeda ?? 'BRL'),
            ]]);
        } catch (\Throwable $e) {
            Response::json(['status'=>'error','message'=>$e->getMessage()], 500);
        }
    }
}
