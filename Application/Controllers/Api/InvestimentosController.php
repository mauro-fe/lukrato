<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\InvestimentoService;
use Application\Core\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InvestimentosController extends BaseController
{
    private InvestimentoService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new InvestimentoService();
    }

    public function index(): void
    {
        $userId = $this->requireAuthenticatedUser();
        $filters = $this->extractFilters();
        $items = $this->service->getInvestimentos($userId, $filters);
        
        Response::success(['data' => $items]);
    }

    public function show(int $id): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $data = $this->service->getInvestimentoById($id, $userId);
            
            Response::success($data);
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function store(): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $data = $this->request->all();
            $investimento = $this->service->criarInvestimento($userId, $data);

            Response::success([
                'message' => 'Investimento criado com sucesso',
                'id' => (int)$investimento->id
            ], 201);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        }
    }

    public function update(mixed $routeParam = null): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $id = $this->extractIdFromRoute($routeParam);
            $data = $this->request->all();
            
            $this->service->atualizarInvestimento($id, $userId, $data);
            
            Response::success(['message' => 'Investimento atualizado com sucesso']);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function destroy(mixed $routeParam = null): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $id = $this->extractIdFromRoute($routeParam);
            
            $this->service->excluirInvestimento($id, $userId);
            
            Response::success(['message' => 'Investimento excluído com sucesso']);
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function atualizarPreco(mixed $routeParam = null): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $id = $this->extractIdFromRoute($routeParam);
            $preco = $this->request->post('preco_atual');
            
            $this->service->atualizarPreco($id, $userId, $preco);
            
            Response::success(['message' => 'Preço atualizado com sucesso']);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function stats(): void
    {
        $userId = $this->requireAuthenticatedUser();
        $stats = $this->service->getStats($userId);
        
        Response::success($stats);
    }

    public function categorias(): void
    {
        $this->requireAuth();
        $categorias = $this->service->getCategorias();
        
        Response::success($categorias);
    }

    public function transacoes(int $investimentoId): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $transacoes = $this->service->getTransacoes($investimentoId, $userId);
            
            Response::success($transacoes);
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function criarTransacao(int $investimentoId): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $data = $this->request->all();
            $transacao = $this->service->criarTransacao($investimentoId, $userId, $data);

            Response::success([
                'message' => 'Transação registrada com sucesso',
                'id' => (int)$transacao->id
            ], 201);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function proventos(int $investimentoId): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $proventos = $this->service->getProventos($investimentoId, $userId);
            
            Response::success($proventos);
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    public function criarProvento(int $investimentoId): void
    {
        try {
            $userId = $this->requireAuthenticatedUser();
            $data = $this->request->all();
            $provento = $this->service->criarProvento($investimentoId, $userId, $data);

            Response::success([
                'message' => 'Provento registrado com sucesso',
                'id' => (int)$provento->id
            ], 201);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        }
    }

    private function requireAuthenticatedUser(): int
    {
        $this->requireAuth();

        $userId = $this->resolveUserId();

        if ($userId === null) {
            Response::error('Sessão expirada', 401);
            exit;
        }

        return $userId;
    }

    private function resolveUserId(): ?int
    {
        $sources = [
            $this->userId ?? null,
            $_SESSION['user']['id'] ?? null,
            $_SESSION['auth']['id'] ?? null,
        ];

        foreach ($sources as $id) {
            if ($id !== null) {
                return (int)$id;
            }
        }

        return null;
    }

    private function extractIdFromRoute(mixed $routeParam): int
    {
        if (is_array($routeParam) && isset($routeParam['id'])) {
            $id = (int)$routeParam['id'];
        } elseif (is_numeric($routeParam)) {
            $id = (int)$routeParam;
        } else {
            $id = 0;
        }

        if ($id <= 0) {
            Response::validationError(['id' => 'ID inválido']);
            exit;
        }

        return $id;
    }

    private function extractFilters(): array
    {
        return [
            'q' => $this->request->get('q', ''),
            'categoria_id' => $this->request->get('categoria_id', 0),
            'conta_id' => $this->request->get('conta_id', 0),
            'ticker' => $this->request->get('ticker', ''),
            'order' => $this->request->get('order', 'nome'),
            'dir' => $this->request->get('dir', 'asc'),
        ];
    }
}