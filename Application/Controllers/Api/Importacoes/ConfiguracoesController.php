<?php

declare(strict_types=1);

namespace Application\Controllers\Api\Importacoes;

use Application\Controllers\ApiController;
use Application\Core\Response;
use Application\Repositories\ContaRepository;
use Application\Services\Importacao\ImportProfileConfigService;

class ConfiguracoesController extends ApiController
{
    private readonly ImportProfileConfigService $profileService;
    private readonly ContaRepository $contaRepository;

    public function __construct(
        ?ImportProfileConfigService $profileService = null,
        ?ContaRepository $contaRepository = null,
    ) {
        parent::__construct();
        $this->profileService = $this->resolveOrCreate($profileService, ImportProfileConfigService::class);
        $this->contaRepository = $this->resolveOrCreate($contaRepository, ContaRepository::class);
    }

    public function __invoke(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $contaId = $this->request->queryInt('conta_id', 0);

        if ($contaId <= 0) {
            return Response::validationErrorResponse(['conta_id' => 'Conta obrigatória para carregar configuração.']);
        }

        if (!$this->contaRepository->belongsToUser($contaId, $userId)) {
            return Response::errorResponse('Conta inválida para o usuário autenticado.', 403);
        }

        $profile = $this->profileService->getForUserAndConta($userId, $contaId);

        return Response::successResponse($profile->toArray(), 'Configuração carregada com sucesso.');
    }

    public function save(): Response
    {
        $userId = $this->requireApiUserIdAndReleaseSessionOrFail();
        $payload = $this->getRequestPayload();

        $contaId = (int) ($payload['conta_id'] ?? 0);
        if ($contaId <= 0) {
            return Response::validationErrorResponse(['conta_id' => 'Conta obrigatória para salvar configuração.']);
        }

        if (!$this->contaRepository->belongsToUser($contaId, $userId)) {
            return Response::errorResponse('Conta inválida para o usuário autenticado.', 403);
        }

        $csvHasHeader = $this->normalizeBoolean($payload['csv_has_header'] ?? true);
        $rawColumnMap = is_array($payload['csv_column_map'] ?? null) ? $payload['csv_column_map'] : [];
        $options = [
            'csv_mapping_mode' => $this->normalizeCsvMappingMode($payload['csv_mapping_mode'] ?? 'auto'),
            'csv_start_row' => $this->normalizeCsvStartRow($payload['csv_start_row'] ?? ($csvHasHeader ? 2 : 1), $csvHasHeader),
            'csv_delimiter' => $this->normalizeCsvDelimiter($payload['csv_delimiter'] ?? ';'),
            'csv_has_header' => $csvHasHeader,
            'csv_date_format' => $this->normalizeCsvDateFormat($payload['csv_date_format'] ?? 'd/m/Y'),
            'csv_decimal_separator' => $this->normalizeCsvDecimalSeparator($payload['csv_decimal_separator'] ?? ','),
            'csv_column_map' => $this->normalizeCsvColumnMap([
                'tipo' => $payload['csv_column_tipo'] ?? ($rawColumnMap['tipo'] ?? null),
                'data' => $payload['csv_column_data'] ?? ($rawColumnMap['data'] ?? null),
                'descricao' => $payload['csv_column_descricao'] ?? ($rawColumnMap['descricao'] ?? null),
                'valor' => $payload['csv_column_valor'] ?? ($rawColumnMap['valor'] ?? null),
                'categoria' => $payload['csv_column_categoria'] ?? ($rawColumnMap['categoria'] ?? null),
                'subcategoria' => $payload['csv_column_subcategoria'] ?? ($rawColumnMap['subcategoria'] ?? null),
                'observacao' => $payload['csv_column_observacao'] ?? ($rawColumnMap['observacao'] ?? null),
                'id_externo' => $payload['csv_column_id_externo'] ?? ($rawColumnMap['id_externo'] ?? null),
            ]),
        ];

        $profile = $this->profileService->saveForUserAndConta($userId, $contaId, [
            'source_type' => $payload['source_type'] ?? 'ofx',
            'label' => $payload['label'] ?? 'Perfil base',
            'agencia' => $payload['agencia'] ?? null,
            'numero_conta' => $payload['numero_conta'] ?? null,
            'options' => $options,
        ]);

        return Response::successResponse($profile->toArray(), 'Configuração de importação salva com sucesso.');
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'sim', 'yes', 'on'], true);
    }

    private function normalizeCsvMappingMode(mixed $value): string
    {
        $mode = strtolower(trim((string) $value));

        return in_array($mode, ['auto', 'manual'], true) ? $mode : 'auto';
    }

    private function normalizeCsvStartRow(mixed $value, bool $hasHeader): int
    {
        $row = (int) $value;
        if ($row > 0) {
            return $row;
        }

        return $hasHeader ? 2 : 1;
    }

    private function normalizeCsvDelimiter(mixed $value): string
    {
        $delimiter = trim((string) $value);
        if ($delimiter === '') {
            return ';';
        }

        $lower = strtolower($delimiter);
        if ($lower === 'tab' || $lower === '\\t') {
            return "\t";
        }

        if (mb_strlen($delimiter) === 1) {
            return $delimiter;
        }

        return ';';
    }

    private function normalizeCsvDateFormat(mixed $value): string
    {
        $format = trim((string) $value);

        return $format !== '' ? mb_substr($format, 0, 20) : 'd/m/Y';
    }

    private function normalizeCsvDecimalSeparator(mixed $value): string
    {
        $separator = trim((string) $value);

        return $separator === '.' ? '.' : ',';
    }

    /**
     * @param array<string, mixed> $columnMap
     * @return array<string, string>
     */
    private function normalizeCsvColumnMap(array $columnMap): array
    {
        return [
            'tipo' => $this->normalizeCsvColumnReference($columnMap['tipo'] ?? null),
            'data' => $this->normalizeCsvColumnReference($columnMap['data'] ?? null),
            'descricao' => $this->normalizeCsvColumnReference($columnMap['descricao'] ?? null),
            'valor' => $this->normalizeCsvColumnReference($columnMap['valor'] ?? null),
            'categoria' => $this->normalizeCsvColumnReference($columnMap['categoria'] ?? null),
            'subcategoria' => $this->normalizeCsvColumnReference($columnMap['subcategoria'] ?? null),
            'observacao' => $this->normalizeCsvColumnReference($columnMap['observacao'] ?? null),
            'id_externo' => $this->normalizeCsvColumnReference($columnMap['id_externo'] ?? null),
        ];
    }

    private function normalizeCsvColumnReference(mixed $value): string
    {
        $reference = strtoupper(trim((string) $value));
        if ($reference === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $reference) || preg_match('/^[A-Z]+$/', $reference)) {
            return mb_substr($reference, 0, 8);
        }

        return '';
    }
}
