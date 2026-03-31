<?php

declare(strict_types=1);

namespace Application\Controllers\Api\User;

use Application\Controllers\ApiController;
use Application\Core\Exceptions\ValidationException;
use Application\Core\Response;
use Application\Models\Telefone;
use Application\Models\Usuario;
use Application\Services\Communication\MailService;
use Application\Services\Infrastructure\CacheService;

class SupportController extends ApiController
{
    private MailService $mailService;

    public function __construct(?MailService $mailService = null, ?CacheService $cache = null)
    {
        parent::__construct(cache: $cache);
        $this->mailService = $mailService ?? new MailService();
    }

    public function send(): Response
    {
        $userId = $this->requireApiUserIdOrFail();

        $data = $this->getRequestPayload();
        $message = trim((string) ($data['message'] ?? ''));
        $retorno = $data['retorno'] ?? null;

        if (!in_array($retorno, ['email', 'whatsapp'], true)) {
            $retorno = null;
        }

        $messageValidation = 'Mensagem é obrigatória e deve ter pelo menos 10 caracteres.';

        if ($message === '' || mb_strlen($message) < 10) {
            return Response::jsonResponse([
                'success' => false,
                'message' => $messageValidation,
                'errors' => ['message' => $messageValidation],
            ]);
        }

        $usuario = Usuario::find($userId);
        if (!$usuario) {
            return Response::jsonResponse([
                'success' => false,
                'message' => 'Usuário não encontrado.',
            ]);
        }

        $nome = trim((string) ($usuario->nome ?? 'Usuário Lukrato'));
        $email = trim((string) ($usuario->email ?? ''));

        $telefoneModel = Telefone::with('ddd')
            ->where('id_usuario', $usuario->id ?? $usuario->id_usuario ?? null)
            ->first();

        $foneFormatado = null;

        if ($telefoneModel) {
            $ddd = $telefoneModel->ddd->codigo ?? null;
            $num = trim((string) ($telefoneModel->numero ?? ''));

            if ($num !== '') {
                $foneFormatado = $ddd
                    ? sprintf('(%s) %s', $ddd, $num)
                    : $num;
            }
        }

        try {
            $this->cache?->checkRateLimit("support:{$userId}", 3, 3600);
        } catch (ValidationException $e) {
            return Response::jsonResponse([
                'success' => false,
                'message' => 'Você já enviou várias mensagens recentemente. Aguarde um pouco antes de enviar outra.',
            ]);
        }

        try {
            $this->mailService->sendSupportMessage(
                $email,
                $nome,
                $message,
                $foneFormatado,
                $retorno
            );

            return Response::jsonResponse([
                'success' => true,
                'message' => 'Mensagem enviada com sucesso. Em breve entraremos em contato.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->domainErrorResponse(
                $e,
                'Não foi possível validar a mensagem de suporte.',
                422
            );
        } catch (\Throwable $e) {
            return $this->failAndLogResponse(
                $e,
                'Não foi possível enviar sua mensagem de suporte. Tente novamente mais tarde.'
            );
        }
    }
}
