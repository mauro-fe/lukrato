<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Enums\LogCategory;
use Application\Models\CartaoCredito;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportExecutionService;
use Application\Services\Importacao\ImportQueueService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportSecurityPolicy;
use Application\Services\Importacao\ImportUploadSecurityService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;

class ConfirmController extends ApiController
{
    private readonly ImportExecutionService $executionService;
    private readonly ImportQueueService $queueService;
    private readonly ImportProfileConfigService $profileService;
    private readonly ContaRepository $contaRepository;
    private readonly PlanLimitService $planLimitService;
    private readonly ImportUploadSecurityService $uploadSecurityService;

    public function __construct(
        ?ImportExecutionService $executionService = null,
        ?ImportQueueService $queueService = null,
        ?ImportProfileConfigService $profileService = null,
        ?ContaRepository $contaRepository = null,
        ?PlanLimitService $planLimitService = null,
        ?ImportUploadSecurityService $uploadSecurityService = null,
    ) {
        parent::__construct();
        $this->executionService = $this->resolveOrCreate($executionService, ImportExecutionService::class);
        $this->queueService = $this->resolveOrCreate($queueService, ImportQueueService::class);
        $this->profileService = $this->resolveOrCreate($profileService, ImportProfileConfigService::class);
        $this->contaRepository = $this->resolveOrCreate($contaRepository, ContaRepository::class);
        $this->planLimitService = $this->resolveOrCreate($planLimitService, PlanLimitService::class);
        $this->uploadSecurityService = $this->resolveOrCreate($uploadSecurityService, ImportUploadSecurityService::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $payload = $this->getRequestPayload();

        $sourceType = strtolower(trim((string) ($payload['source_type'] ?? 'ofx')));
        if (!in_array($sourceType, ['ofx', 'csv'], true)) {
            return Response::validationErrorResponse(['source_type' => 'Formato de importação inválido.']);
        }

        $importTarget = $this->normalizeImportTarget((string) ($payload['import_target'] ?? 'conta'));

        $importQuota = $this->planLimitService->canUseImportacao($userId, $sourceType, $importTarget);
        if (!(bool) ($importQuota['allowed'] ?? true)) {
            return Response::errorResponse(
                (string) ($importQuota['message'] ?? 'Limite de importações atingido para o plano atual.'),
                403,
                [
                    'limit_reached' => true,
                    'upgrade_url' => (string) ($importQuota['upgrade_url'] ?? '/assinatura'),
                    'bucket' => $importQuota['bucket'] ?? null,
                    'source_type' => $sourceType,
                    'import_target' => $importTarget,
                    'limit_info' => [
                        'limit' => $importQuota['limit'] ?? null,
                        'used' => $importQuota['used'] ?? null,
                        'remaining' => $importQuota['remaining'] ?? null,
                    ],
                ]
            );
        }

        $contaId = 0;
        $cartaoId = null;

        if ($importTarget === 'cartao') {
            $cartaoId = (int) ($payload['cartao_id'] ?? 0);
            if ($cartaoId <= 0) {
                return Response::validationErrorResponse(['cartao_id' => 'Cartão obrigatório para confirmar importação de fatura.']);
            }

            $cartao = CartaoCredito::query()
                ->where('id', $cartaoId)
                ->where('user_id', $userId)
                ->first();

            if (!$cartao) {
                return Response::errorResponse('Cartão inválido para o usuário autenticado.', 403);
            }

            if ((bool) ($cartao->arquivado ?? false)) {
                return Response::validationErrorResponse(['cartao_id' => 'Cartão arquivado não pode receber importação.']);
            }

            $contaId = (int) ($cartao->conta_id ?? 0);
            if ($contaId <= 0) {
                return Response::validationErrorResponse([
                    'cartao_id' => 'Vincule uma conta ao cartão antes de importar a fatura.',
                ]);
            }
        } else {
            $contaId = (int) ($payload['conta_id'] ?? 0);
            if ($contaId <= 0) {
                return Response::validationErrorResponse(['conta_id' => 'Conta obrigatória para confirmar importação.']);
            }

            if (!$this->contaRepository->belongsToUser($contaId, $userId)) {
                return Response::errorResponse('Conta inválida para o usuário autenticado.', 403);
            }
        }

        try {
            [$tmpName, $filename] = $this->extractUploadedFile($sourceType);
            $profile = $this->resolveProfile($userId, $contaId, $sourceType);
            $contents = file_get_contents($tmpName);
            if ($contents === false) {
                throw new \InvalidArgumentException('Não foi possível ler o arquivo enviado.');
            }

            $rowOverrides = $this->parseRowOverrides($payload);

            $preparation = $this->executionService->prepareExecution(
                $sourceType,
                $contents,
                $profile,
                $filename,
                $importTarget,
                $cartaoId,
                $userId,
                $rowOverrides
            );
            $preview = is_array($preparation->data['preview'] ?? null)
                ? $preparation->data['preview']
                : [];

            if (!$this->previewAllowsConfirmation($preview)) {
                return Response::validationErrorResponse([
                    'file' => $this->resolvePreviewBlockingMessage($preview),
                ]);
            }

            if ($this->shouldQueueImport($payload)) {
                $job = $this->queueService->enqueueFromUpload(
                    $userId,
                    $sourceType,
                    $profile,
                    $tmpName,
                    $filename,
                    $importTarget,
                    $cartaoId,
                    $rowOverrides
                );

                return Response::successResponse([
                    'status' => 'queued',
                    'job' => $job,
                    'next_step' => 'Acompanhe o processamento pela API de status do job ou pelo histórico de importações.',
                ], 'Importação enfileirada com sucesso.', 202);
            }

            $result = $this->executionService->confirmExecution(
                $userId,
                $sourceType,
                $contents,
                $profile,
                $filename,
                $importTarget,
                $cartaoId,
                $rowOverrides
            );
        } catch (\InvalidArgumentException $e) {
            return Response::validationErrorResponse(['file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'import_confirm',
                'user_id' => $userId,
                'source_type' => $sourceType,
                'import_target' => $importTarget,
                'conta_id' => $contaId,
                'cartao_id' => $cartaoId,
                'async' => $this->shouldQueueImport($payload),
            ], $userId);

            return Response::errorResponse(
                ImportSecurityPolicy::clientProcessingErrorMessage(),
                422,
                ['request_id' => LogService::currentRequestId()],
                'import_processing_error'
            );
        }

        if (!$result->success) {
            return Response::errorResponse($result->message, $result->httpCode);
        }

        return Response::successResponse($result->data, $result->message, 200);
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractUploadedFile(string $sourceType): array
    {
        $file = $this->request->file('file');

        if (!is_array($file)) {
            throw new \InvalidArgumentException('Arquivo obrigatório para confirmar importação.');
        }

        $validatedUpload = $this->uploadSecurityService->extractValidatedUpload($sourceType, $file);

        return [
            (string) ($validatedUpload['tmp_name'] ?? ''),
            (string) ($validatedUpload['filename'] ?? ''),
        ];
    }

    private function resolveProfile(int $userId, int $contaId, string $sourceType): ImportProfileConfigDTO
    {
        $profile = $this->profileService->getForUserAndConta($userId, $contaId, $sourceType);
        $array = $profile->toArray();
        $array['source_type'] = $sourceType;

        return ImportProfileConfigDTO::fromArray($array);
    }

    private function normalizeImportTarget(string $importTarget): string
    {
        $normalized = strtolower(trim($importTarget));

        return in_array($normalized, ['conta', 'cartao'], true) ? $normalized : 'conta';
    }

    /**
     * @param array<string, mixed> $preview
     */
    private function previewAllowsConfirmation(array $preview): bool
    {
        return (bool) ($preview['can_confirm'] ?? false);
    }

    /**
     * @param array<string, mixed> $preview
     */
    private function resolvePreviewBlockingMessage(array $preview): string
    {
        $errors = is_array($preview['errors'] ?? null) ? $preview['errors'] : [];

        foreach ($errors as $error) {
            $message = trim((string) $error);
            if ($message !== '') {
                return $message;
            }
        }

        return 'Preview bloqueado. Ajuste o arquivo ou a configuração antes de confirmar.';
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function shouldQueueImport(array $payload): bool
    {
        if (!array_key_exists('async', $payload)) {
            return ImportSecurityPolicy::shouldQueueConfirmByDefault();
        }

        $raw = strtolower(trim((string) ($payload['async'] ?? '0')));

        return in_array($raw, ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function parseRowOverrides(array $payload): array
    {
        $raw = $payload['row_overrides'] ?? null;
        if (is_array($raw)) {
            return $raw;
        }

        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }
}
