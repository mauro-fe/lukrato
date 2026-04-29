<?php

declare(strict_types=1);

namespace Application\Services\Importacao;

use Application\DTO\Importacao\NormalizedImportRowDTO;
use Application\Models\Categoria;
use Application\Services\AI\Rules\CategoryRuleEngine;

class ImportRowCategorizationService
{
    /**
     * @var array<int, string|null>
     */
    private static array $categoriaNameCache = [];

    /**
     * @param array<int, NormalizedImportRowDTO> $rows
     * @return array<int, NormalizedImportRowDTO>
     */
    public function assignRowKeys(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $index => $row) {
            if (!$row instanceof NormalizedImportRowDTO) {
                continue;
            }

            $normalized[] = NormalizedImportRowDTO::fromArray([
                'date' => $row->date,
                'amount' => $row->amount,
                'type' => $row->type,
                'description' => $row->description,
                'memo' => $row->memo,
                'external_id' => $row->externalId,
                'account_hint' => $row->accountHint,
                'raw' => $row->raw,
                'row_key' => $row->rowKey ?? self::buildRowKeyFromPayload([
                    'date' => $row->date,
                    'amount' => $row->amount,
                    'type' => $row->type,
                    'description' => $row->description,
                    'external_id' => $row->externalId,
                ], (int) $index),
                'categoria_id' => $row->categoriaId,
                'subcategoria_id' => $row->subcategoriaId,
                'categoria_nome' => $row->categoriaNome,
                'subcategoria_nome' => $row->subcategoriaNome,
                'categoria_sugerida_id' => $row->categoriaSugeridaId,
                'subcategoria_sugerida_id' => $row->subcategoriaSugeridaId,
                'categoria_sugerida_nome' => $row->categoriaSugeridaNome,
                'subcategoria_sugerida_nome' => $row->subcategoriaSugeridaNome,
                'categoria_source' => $row->categoriaSource,
                'categoria_confidence' => $row->categoriaConfidence,
                'categoria_editada' => $row->categoriaEditada,
                'categoria_learning_source' => $row->categoriaLearningSource,
            ]);
        }

        return $normalized;
    }

    /**
     * @param array<int, NormalizedImportRowDTO> $rows
     * @return array<int, NormalizedImportRowDTO>
     */
    public function enrichRows(array $rows, ?int $userId = null): array
    {
        $enriched = [];

        foreach ($rows as $index => $row) {
            if (!$row instanceof NormalizedImportRowDTO) {
                continue;
            }

            $match = CategoryRuleEngine::match($row->description, $userId, $row->memo ?? null);
            $categoriaId = self::normalizePositiveInt($match['categoria_id'] ?? null);
            $subcategoriaId = self::normalizePositiveInt($match['subcategoria_id'] ?? null);
            $categoriaNome = self::normalizeText($match['categoria'] ?? null);
            $subcategoriaNome = self::normalizeText($match['subcategoria'] ?? null);
            $source = self::normalizeText($match['confidence'] ?? null);

            $enriched[] = NormalizedImportRowDTO::fromArray([
                'date' => $row->date,
                'amount' => $row->amount,
                'type' => $row->type,
                'description' => $row->description,
                'memo' => $row->memo,
                'external_id' => $row->externalId,
                'account_hint' => $row->accountHint,
                'raw' => $row->raw,
                'row_key' => self::buildRowKeyFromPayload([
                    'date' => $row->date,
                    'amount' => $row->amount,
                    'type' => $row->type,
                    'description' => $row->description,
                    'external_id' => $row->externalId,
                ], (int) $index),
                'categoria_id' => $categoriaId,
                'subcategoria_id' => $subcategoriaId,
                'categoria_nome' => $categoriaNome,
                'subcategoria_nome' => $subcategoriaNome,
                'categoria_sugerida_id' => $categoriaId,
                'subcategoria_sugerida_id' => $subcategoriaId,
                'categoria_sugerida_nome' => $categoriaNome,
                'subcategoria_sugerida_nome' => $subcategoriaNome,
                'categoria_source' => $source,
                'categoria_confidence' => $source,
                'categoria_editada' => false,
                'categoria_learning_source' => null,
            ]);
        }

        return $enriched;
    }

    /**
     * @param array<int, NormalizedImportRowDTO> $rows
     * @return array<int, NormalizedImportRowDTO>
     */
    public function resolveNamedCategories(array $rows, ?int $userId = null, string $importTarget = 'conta'): array
    {
        if ($userId === null || $userId <= 0) {
            return $rows;
        }

        $normalizedImportTarget = strtolower(trim($importTarget));
        $resolvedRows = [];

        foreach ($rows as $row) {
            if (!$row instanceof NormalizedImportRowDTO) {
                continue;
            }

            $payload = $row->toArray();
            $raw = is_array($payload['raw'] ?? null) ? $payload['raw'] : [];
            $categoriaNomeRaw = self::normalizeText($raw['categoria'] ?? null);
            $subcategoriaNomeRaw = self::normalizeText($raw['subcategoria'] ?? null);

            if ($row->categoriaId !== null || $categoriaNomeRaw === null) {
                $resolvedRows[] = $row;
                continue;
            }

            $categoriaTipo = $normalizedImportTarget === 'cartao' ? 'despesa' : $row->type;
            $categoria = $this->findRootCategoryByName($categoriaNomeRaw, $userId, $categoriaTipo);
            if (!$categoria) {
                $resolvedRows[] = $row;
                continue;
            }

            $subcategoria = $subcategoriaNomeRaw !== null
                ? $this->findSubcategoryByName($subcategoriaNomeRaw, (int) $categoria->id, $userId)
                : null;

            $payload['categoria_id'] = (int) $categoria->id;
            $payload['categoria_nome'] = self::normalizeText($categoria->nome);
            $payload['subcategoria_id'] = $subcategoria ? (int) $subcategoria->id : null;
            $payload['subcategoria_nome'] = $subcategoria ? self::normalizeText($subcategoria->nome) : null;
            $payload['categoria_sugerida_id'] = $payload['categoria_sugerida_id'] ?? (int) $categoria->id;
            $payload['subcategoria_sugerida_id'] = $payload['subcategoria_sugerida_id'] ?? ($subcategoria ? (int) $subcategoria->id : null);
            $payload['categoria_sugerida_nome'] = $payload['categoria_sugerida_nome'] ?? self::normalizeText($categoria->nome);
            $payload['subcategoria_sugerida_nome'] = $payload['subcategoria_sugerida_nome'] ?? ($subcategoria ? self::normalizeText($subcategoria->nome) : null);
            $payload['categoria_source'] = $payload['categoria_source'] ?? 'csv';
            $payload['categoria_confidence'] = $payload['categoria_confidence'] ?? 'csv';
            $payload['categoria_editada'] = false;

            $resolvedRows[] = NormalizedImportRowDTO::fromArray($payload);
        }

        return $resolvedRows;
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function buildRowKeyFromPayload(array $row, int $index): string
    {
        $payload = implode('|', [
            $index,
            trim((string) ($row['date'] ?? '')),
            mb_strtolower(trim((string) ($row['description'] ?? ''))),
            number_format(abs((float) ($row['amount'] ?? 0)), 2, '.', ''),
            mb_strtolower(trim((string) ($row['type'] ?? ''))),
            trim((string) ($row['external_id'] ?? $row['externalId'] ?? '')),
        ]);

        return hash('sha256', $payload);
    }

    /**
     * @return array{categoria_nome:?string,subcategoria_nome:?string}
     */
    public static function resolveCategoryNames(?int $categoriaId, ?int $subcategoriaId): array
    {
        return [
            'categoria_nome' => self::resolveCategoryName($categoriaId),
            'subcategoria_nome' => self::resolveCategoryName($subcategoriaId),
        ];
    }

    private static function resolveCategoryName(?int $categoriaId): ?string
    {
        if ($categoriaId === null || $categoriaId <= 0) {
            return null;
        }

        if (array_key_exists($categoriaId, self::$categoriaNameCache)) {
            return self::$categoriaNameCache[$categoriaId];
        }

        try {
            $categoria = Categoria::find($categoriaId);
            self::$categoriaNameCache[$categoriaId] = $categoria
                ? self::normalizeText($categoria->nome)
                : null;
        } catch (\Throwable) {
            self::$categoriaNameCache[$categoriaId] = null;
        }

        return self::$categoriaNameCache[$categoriaId];
    }

    private function findRootCategoryByName(string $nome, int $userId, string $tipo): ?Categoria
    {
        $query = Categoria::query()
            ->whereNull('parent_id')
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            });

        if (in_array($tipo, ['receita', 'despesa'], true)) {
            $query->whereIn('tipo', [$tipo, 'ambas']);
        }

        return $query
            ->orderByDesc('user_id')
            ->first();
    }

    private function findSubcategoryByName(string $nome, int $categoriaId, int $userId): ?Categoria
    {
        return Categoria::query()
            ->where('parent_id', $categoriaId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->where(function ($q) use ($userId) {
                $q->whereNull('user_id')
                    ->orWhere('user_id', $userId);
            })
            ->orderByDesc('user_id')
            ->first();
    }

    private static function normalizePositiveInt(mixed $value): ?int
    {
        if (!is_scalar($value) || $value === '') {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private static function normalizeText(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }
}
