<?php

declare(strict_types=1);

namespace Application\Controllers\Api\AI;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\AI\AIResponseDTO;
use Application\Models\Usuario;
use Application\Services\AI\AIQuotaService;
use Application\Services\AI\UserAiWorkflowService;
use DomainException;
use InvalidArgumentException;

/**
 * Controller de IA para usuarios autenticados.
 * Expoe chat, categorizacao, analise financeira e gestao de conversas.
 */
class UserAiController extends ApiController
{
    private ?UserAiWorkflowService $workflowService;

    public function __construct(
        ?UserAiWorkflowService $workflowService = null
    ) {
        parent::__construct();

        $this->workflowService = $workflowService;
    }

    public function chat(): Response
    {
        $userId = $this->authenticatedUserId();
        $payload = $this->getRequestPayload();

        try {
            $result = $this->workflowService()->chat(
                $userId,
                trim((string) ($payload['message'] ?? '')),
                $this->attachment()
            );
        } catch (InvalidArgumentException $e) {
            return $this->validationError($e);
        }

        $response = $result['response'];
        $chatData = [
            'response' => $response->message,
            'intent' => $response->intent?->value,
            'source' => $response->source,
            'cached' => $response->cached,
            'derived_message' => $result['derived_message'],
        ];

        return $this->respondFromAi($response, $chatData, 503);
    }

    public function suggestCategory(): Response
    {
        $userId = $this->authenticatedUserId();
        $payload = $this->getRequestPayload();

        try {
            $response = $this->workflowService()->suggestCategory(
                $userId,
                trim((string) ($payload['description'] ?? ''))
            );
        } catch (InvalidArgumentException $e) {
            return $this->validationError($e);
        }

        $data = $response->data;
        $categoryData = [
            'category' => $data['categoria'] ?? null,
            'subcategory' => $data['subcategoria'] ?? null,
            'category_id' => $data['categoria_id'] ?? null,
            'subcategory_id' => $data['subcategoria_id'] ?? null,
            'confidence' => $data['confidence'] ?? null,
            'source' => $response->source,
        ];

        if ($response->success) {
            return Response::successResponse($categoryData);
        }

        return Response::errorResponse('Não foi possivel sugerir categoria.', 422);
    }

    public function analyze(): Response
    {
        $userId = $this->authenticatedUserId();
        $payload = $this->getRequestPayload();

        $response = $this->workflowService()->analyze(
            $userId,
            trim((string) ($payload['period'] ?? 'ultimo mes'))
        );

        if (!$response->success) {
            return Response::errorResponse($response->message, 503);
        }

        $analyzeData = $response->data;
        $analyzeData['source'] = $response->source;
        $analyzeData['cached'] = $response->cached;

        return Response::successResponse($analyzeData);
    }

    public function extractTransaction(): Response
    {
        $userId = $this->authenticatedUserId();
        $payload = $this->getRequestPayload();

        try {
            $response = $this->workflowService()->extractTransaction(
                $userId,
                trim((string) ($payload['message'] ?? ''))
            );
        } catch (InvalidArgumentException $e) {
            return $this->validationError($e);
        }

        if ($response->success) {
            $extractData = $response->data;
            $extractData['source'] = $response->source;

            return Response::successResponse($extractData, $response->message);
        }

        return Response::errorResponse($response->message, 422);
    }

    public function getQuota(): Response
    {
        $user = $this->authenticatedUser();

        return Response::successResponse(AIQuotaService::getUsage($user));
    }

    public function listConversations(): Response
    {
        $userId = $this->authenticatedUserId();

        return Response::successResponse($this->workflowService()->listConversations($userId));
    }

    public function createConversation(): Response
    {
        $userId = $this->authenticatedUserId();
        $conversation = $this->workflowService()->createConversation($userId);

        return Response::successResponse([
            'id' => $conversation->id,
            'titulo' => $conversation->titulo,
            'created_at' => $conversation->created_at?->toISOString(),
        ], 'Conversa criada.', 201);
    }

    public function getMessages(int $id): Response
    {
        $userId = $this->authenticatedUserId();
        $conversation = $this->workflowService()->findConversation($userId, $id);

        if ($conversation === null) {
            return $this->conversationNotFoundResponse();
        }

        return Response::successResponse($this->workflowService()->getConversationMessages($conversation));
    }

    public function sendMessage(int $id): Response
    {
        $userId = $this->authenticatedUserId();
        $conversation = $this->workflowService()->findConversation($userId, $id);

        if ($conversation === null) {
            return $this->conversationNotFoundResponse();
        }

        $payload = $this->getRequestPayload();

        try {
            $result = $this->workflowService()->sendConversationMessage(
                $conversation,
                $userId,
                trim((string) ($payload['message'] ?? '')),
                $this->attachment()
            );
        } catch (InvalidArgumentException $e) {
            return $this->validationError($e);
        }

        $response = $result['response'];
        $sendMessageData = [
            'user_message' => $result['user_message'],
            'assistant_message' => $result['assistant_message'],
            'source' => $response->source,
            'cached' => $response->cached,
            'derived_message' => $result['derived_message'],
            'ai_data' => $response->data,
        ];

        return $this->respondFromAi($response, $sendMessageData, 503);
    }

    public function confirmAction(int $id): Response
    {
        $userId = $this->authenticatedUserId();
        $pending = $this->workflowService()->findPendingAction($userId, $id);

        if ($pending === null) {
            return $this->pendingActionNotFoundResponse();
        }

        try {
            $response = $this->workflowService()->confirmPendingAction(
                $pending,
                $userId,
                $this->getRequestPayload()
            );
        } catch (DomainException $e) {
            return $this->domainError($e, 422);
        }

        if ($response->success) {
            return Response::successResponse([
                'message' => $response->message,
                'ai_data' => $response->data,
            ]);
        }

        return Response::errorResponse($response->message, 422);
    }

    public function rejectAction(int $id): Response
    {
        $userId = $this->authenticatedUserId();
        $pending = $this->workflowService()->findPendingAction($userId, $id);

        if ($pending === null) {
            return $this->pendingActionNotFoundResponse();
        }

        $this->workflowService()->rejectPendingAction($pending);

        return Response::successResponse(['message' => 'Ação cancelada com sucesso.']);
    }

    public function deleteConversation(int $id): Response
    {
        $userId = $this->authenticatedUserId();
        $conversation = $this->workflowService()->findConversation($userId, $id);

        if ($conversation === null) {
            return $this->conversationNotFoundResponse();
        }

        $this->workflowService()->deleteConversation($conversation);

        return Response::successResponse(null, 'Conversa excluida.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function attachment(): ?array
    {
        if (!$this->request->hasFile('attachment')) {
            return null;
        }

        return $this->request->file('attachment');
    }

    private function respondFromAi(AIResponseDTO $response, array $payload, int $failureStatus): Response
    {
        if ($response->success) {
            return Response::successResponse($payload);
        }

        return Response::errorResponse($response->message, $failureStatus);
    }

    private function validationError(InvalidArgumentException $e): Response
    {
        return $this->domainErrorResponse($e, 'Dados inválidos para a operação.', 422);
    }

    private function domainError(DomainException $e, int $defaultStatus): Response
    {
        $status = $e->getCode() > 0 ? (int) $e->getCode() : $defaultStatus;

        return $this->domainErrorResponse(
            $e,
            $status === 404 ? 'Recurso não encontrado.' : 'Não foi possível concluir a operação.',
            $status,
            [],
            $status === 404 ? 'RESOURCE_NOT_FOUND' : null
        );
    }

    private function authenticatedUserId(): int
    {
        return $this->requireApiUserIdAndReleaseSessionOrFail();
    }

    private function authenticatedUser(): Usuario
    {
        return $this->requireApiUserAndReleaseSessionOrFail();
    }

    private function conversationNotFoundResponse(): Response
    {
        return Response::errorResponse('Conversa não encontrada.', 404);
    }

    private function pendingActionNotFoundResponse(): Response
    {
        return Response::errorResponse('Ação não encontrada ou já processada.', 404);
    }

    private function workflowService(): UserAiWorkflowService
    {
        return $this->workflowService ??= $this->resolveOrCreate($this->workflowService, UserAiWorkflowService::class);
    }
}
