<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\DTO\Importacao\ImportProfileConfigDTO;
use Application\Enums\LogCategory;
use Application\Models\CartaoCredito;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportPreviewService;
use Application\Services\Importacao\ImportProfileConfigService;
use Application\Services\Importacao\ImportSecurityPolicy;
use Application\Services\Importacao\ImportUploadSecurityService;
use Application\Services\Infrastructure\LogService;
use Application\Services\Plan\PlanLimitService;

class PreviewController extends ApiController
{
    private readonly ImportPreviewService $previewService;
    private readonly ImportProfileConfigService $profileService;
    private readonly ContaRepository $contaRepository;
    private readonly PlanLimitService $planLimitService;
    private readonly ImportUploadSecurityService $uploadSecurityService;

    public function __construct(
        ?ImportPreviewService $previewService = null,
        ?ImportProfileConfigService $profileService = null,
        ?ContaRepository $contaRepository = null,
        ?PlanLimitService $planLimitService = null,
        ?ImportUploadSecurityService $uploadSecurityService = null,
    ) {
        parent::__construct();
        $this->previewService = $this->resolveOrCreate($previewService, ImportPreviewService::class);
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
                return Response::validationErrorResponse(['cartao_id' => 'Cartão obrigatório para preview de fatura.']);
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
                return Response::validationErrorResponse(['conta_id' => 'Conta obrigatória para gerar preview.']);
            }

            if (!$this->contaRepository->belongsToUser($contaId, $userId)) {
                return Response::errorResponse('Conta inválida para o usuário autenticado.', 403);
            }
        }

        try {
            [$contents, $filename] = $this->extractUploadedContents($sourceType);
            $profile = $this->resolveProfile($userId, $contaId, $sourceType);
            $categorizePreview = $this->shouldCategorizePreview($payload);
            $preview = $this->previewService->preview(
                $sourceType,
                $contents,
                $profile,
                $filename,
                $importTarget,
                $cartaoId,
                $userId,
                $categorizePreview
            );
        } catch (\InvalidArgumentException $e) {
            return Response::validationErrorResponse(['file' => $e->getMessage()]);
        } catch (\Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'import_preview',
                'user_id' => $userId,
                'source_type' => $sourceType,
                'import_target' => $importTarget,
                'conta_id' => $contaId,
                'cartao_id' => $cartaoId,
            ], $userId);

            return Response::errorResponse(
                ImportSecurityPolicy::clientProcessingErrorMessage(),
                422,
                ['request_id' => LogService::currentRequestId()],
                'import_processing_error'
            );
        }

        return Response::successResponse($preview, 'Preview preparado com sucesso.');
    }

    /**
     * @return array{0:string,1:string}
     */
    private function extractUploadedContents(string $sourceType): array
    {
        $file = $this->request->file('file');

        if (!is_array($file)) {
            throw new \InvalidArgumentException('Arquivo obrigatório para gerar preview.');
        }

        $validatedUpload = $this->uploadSecurityService->extractValidatedUpload($sourceType, $file, true);
        $contents = $validatedUpload['contents'] ?? null;
        if (!is_string($contents)) {
            throw new \InvalidArgumentException('Não foi possível ler o arquivo enviado.');
        }

        return [$contents, (string) $validatedUpload['filename']];
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
     * @param array<string, mixed> $payload
     */
    private function shouldCategorizePreview(array $payload): bool
    {
        $raw = strtolower(trim((string) ($payload['categorize_preview'] ?? '0')));

        return in_array($raw, ['1', 'true', 'yes', 'on'], true);
    }
}
