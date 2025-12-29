<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Models\Categoria;
use Application\Core\Response;
use Application\Repositories\CategoriaRepository;
use Application\Enums\CategoriaTipo;
use Application\DTOs\Requests\CreateCategoriaDTO;
use Application\DTOs\Requests\UpdateCategoriaDTO;
use Application\Validators\CategoriaValidator;
use Application\Services\GamificationService;
use GUMP;
use Exception;
use ValueError;

class CategoriaController extends BaseController
{
    private CategoriaRepository $categoriaRepo;
    private const CACHE_FILE = __DIR__ . '/../../../storage/cache/categoria_requests.json';
    private const CACHE_TTL = 5; // segundos

    public function __construct()
    {
        parent::__construct();
        $this->categoriaRepo = new CategoriaRepository();
    }

    /**
     * Verificar se é uma requisição duplicada
     */
    private function isDuplicateRequest(string $key): bool
    {
        $cacheFile = self::CACHE_FILE;
        $cacheDir = dirname($cacheFile);

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }

        // Ler cache existente
        $cache = [];
        if (file_exists($cacheFile)) {
            $content = @file_get_contents($cacheFile);
            if ($content) {
                $cache = json_decode($content, true) ?: [];
            }
        }

        $now = microtime(true);

        // Limpar entradas antigas
        $cache = array_filter($cache, function ($timestamp) use ($now) {
            return ($now - $timestamp) < self::CACHE_TTL;
        });

        // Verificar se já existe
        if (isset($cache[$key])) {
            return true;
        }

        // Marcar como processando
        $cache[$key] = $now;
        @file_put_contents($cacheFile, json_encode($cache), LOCK_EX);

        return false;
    }

    /**
     * Remover requisição do cache
     */
    private function removeCacheEntry(string $key): void
    {
        $cacheFile = self::CACHE_FILE;

        if (!file_exists($cacheFile)) {
            return;
        }

        $content = @file_get_contents($cacheFile);
        if (!$content) {
            return;
        }

        $cache = json_decode($content, true) ?: [];
        unset($cache[$key]);
        @file_put_contents($cacheFile, json_encode($cache), LOCK_EX);
    }

    public function index(): void
    {
        $this->requireAuthApi();

        error_log("📂 [CATEGORIAS] Requisição recebida - User ID: {$this->userId}");

        $tipo = $this->request?->get('tipo');
        error_log("📂 [CATEGORIAS] Tipo filtro: " . var_export($tipo, true));

        if ($tipo) {
            try {
                $tipoEnum = CategoriaTipo::from(strtolower($tipo));
                $categorias = $this->categoriaRepo->findByType($this->userId, $tipoEnum);
                error_log("📂 [CATEGORIAS] Filtradas por tipo: " . count($categorias) . " categorias");
            } catch (ValueError) {
                $categorias = $this->categoriaRepo->findByUser($this->userId);
                error_log("📂 [CATEGORIAS] Tipo inválido, retornando todas: " . count($categorias));
            }
        } else {
            $categorias = $this->categoriaRepo->findByUser($this->userId);
            error_log("📂 [CATEGORIAS] Todas as categorias: " . count($categorias));
        }

        error_log("📂 [CATEGORIAS] Retornando " . count($categorias) . " categorias");
        Response::success($categorias);
    }


    public function store(): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        error_log("📦 [CATEGORIA CREATE] Payload recebido: " . json_encode($payload));

        // Validar dados
        $errors = CategoriaValidator::validateCreate($payload);
        if (!empty($errors)) {
            error_log("❌ [CATEGORIA CREATE] Erro de validação: " . json_encode($errors));
            Response::validationError($errors);
            return;
        }

        // Sanitizar dados
        $nome = trim((string)($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));

        error_log("📝 [CATEGORIA CREATE] Tentando criar: Nome='{$nome}', Tipo='{$tipo}', UserID={$this->userId}");

        // Verificar se é uma requisição duplicada recente (dentro de 5 segundos)
        // Usa arquivo compartilhado entre processos do Apache
        $requestKey = "{$this->userId}:{$nome}:{$tipo}";

        if ($this->isDuplicateRequest($requestKey)) {
            error_log("⚠️ [CATEGORIA CREATE] Requisição duplicada ignorada - aguardando primeira requisição");

            // Aguardar um pouco para garantir que a primeira requisição terminou
            usleep(200000); // 200ms

            // Buscar a categoria recém criada para retornar
            $categoria = Categoria::forUser($this->userId)
                ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
                ->where('tipo', $tipo)
                ->orderBy('id', 'desc')
                ->first();

            if ($categoria) {
                error_log("✅ [CATEGORIA CREATE] Categoria duplicada encontrada no banco, retornando ID: {$categoria->id}");
                Response::success([
                    'categoria' => $categoria,
                    'gamification' => [],
                ], 'Categoria criada com sucesso', 201);
            } else {
                Response::success([], 'Processando requisição...', 200);
            }
            return;
        }

        error_log("🔒 [CATEGORIA CREATE] Requisição marcada no cache compartilhado");

        // Verificar duplicata no banco
        if ($this->categoriaRepo->hasDuplicate($this->userId, $nome, $tipo)) {
            // Limpar do cache pois não foi criada
            $this->removeCacheEntry($requestKey);
            error_log("❌ [CATEGORIA CREATE] Duplicata encontrada para: '{$nome}' ({$tipo})");
            Response::error('Categoria já existe com este nome e tipo.', 409);
            return;
        }

        error_log("✅ [CATEGORIA CREATE] Nenhuma duplicata, prosseguindo com criação");

        // Criar DTO e categoria
        $dto = CreateCategoriaDTO::fromRequest($this->userId, ['nome' => $nome, 'tipo' => $tipo]);
        $categoria = $this->categoriaRepo->create($dto->toArray());

        error_log("✅ [CATEGORIA CREATE] Categoria criada com ID: {$categoria->id}");

        // 🎮 GAMIFICAÇÃO: Adicionar pontos por criar categoria
        $gamificationResult = [];
        try {
            $gamificationService = new GamificationService();
            $pointsResult = $gamificationService->addPoints(
                $this->userId,
                \Application\Enums\GamificationAction::CREATE_CATEGORIA,
                $categoria->id,
                'categoria'
            );
            $gamificationResult = ['points' => $pointsResult];
        } catch (\Exception $e) {
            error_log("🎮 [GAMIFICATION] Erro ao processar gamificação: " . $e->getMessage());
        }

        Response::success([
            'categoria' => $categoria->fresh(),
            'gamification' => $gamificationResult,
        ], 'Categoria criada com sucesso', 201);
    }


    public function update(mixed $routeParam = null): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int)$routeParam : (int)($payload['id'] ?? 0);

        if ($id <= 0) {
            Response::validationError(['id' => 'ID inválido.']);
            return;
        }

        $categoria = Categoria::forUser($this->userId)->find($id);
        if (!$categoria) {
            Response::error('Categoria não encontrada.', 404);
            return;
        }

        // Validar dados
        $errors = CategoriaValidator::validateUpdate($payload);
        if (!empty($errors)) {
            Response::validationError($errors);
            return;
        }

        // Sanitizar dados
        $nome = trim((string)($payload['nome'] ?? ''));
        $tipo = strtolower(trim((string)($payload['tipo'] ?? '')));

        // Verificar duplicata
        $dup = Categoria::forUser($this->userId)
            ->whereRaw('LOWER(nome) = ?', [mb_strtolower($nome)])
            ->where('tipo', $tipo)
            ->where('id', '!=', $categoria->id)
            ->first();

        if ($dup) {
            Response::error('Categoria já existe.', 409);
            return;
        }

        // Criar DTO e atualizar
        $dto = UpdateCategoriaDTO::fromRequest(['nome' => $nome, 'tipo' => $tipo]);
        $this->categoriaRepo->update($categoria->id, $dto->toArray());

        Response::success($categoria->fresh());
    }


    public function delete(mixed $routeParam = null): void
    {
        $this->requireAuthApi();
        $payload = $this->getRequestPayload();

        $id = is_numeric($routeParam) ? (int)$routeParam : (int)($payload['id'] ?? 0);

        if ($id <= 0) {
            Response::validationError(['id' => 'ID inválido']);
            return;
        }

        $categoria = Categoria::forUser($this->userId)->find($id);
        if (!$categoria) {
            Response::error('Categoria não encontrada.', 404);
            return;
        }

        $categoria->delete();
        Response::success(['deleted' => true]);
    }
}
