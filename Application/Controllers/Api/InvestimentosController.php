<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Services\InvestimentoService; // Importa o Service
use Application\Services\LogService;
use Application\Core\Exceptions\ValidationException; // Importa a Exceção
use Throwable;

class InvestimentosController extends BaseController
{
    private InvestimentoService $service;

    public function __construct()
    {
        parent::__construct();
        // Instancia o serviço
        $this->service = new InvestimentoService();
    }

    /** Resolve o ID do usuário logado de forma robusta. */
    private function resolveUserId(): ?int
    {
        $id = $this->userId ?? ($_SESSION['user']['id'] ?? null) ?? ($_SESSION['auth']['id'] ?? null);
        return $id !== null ? (int)$id : null;
    }

    /** Extrai o ID da rota ou de arrays genéricos. */
    private function extractId(mixed $routeParam): int
    {
        if (is_array($routeParam) && isset($routeParam['id'])) return (int)$routeParam['id'];
        if (is_numeric($routeParam)) return (int)$routeParam;
        return 0;
    }

    // --- Endpoints "Magros" ---

    public function index(): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            // 1. Coleta filtros
            $filters = [
                'q'            => $this->request->get('q', ''),
                'categoria_id' => $this->request->get('categoria_id', 0),
                'conta_id'     => $this->request->get('conta_id', 0),
                'ticker'       => $this->request->get('ticker', ''),
                'order'        => $this->request->get('order', 'nome'),
                'dir'          => $this->request->get('dir', 'asc'),
            ];

            // 2. Chama o Serviço
            $items = $this->service->getInvestimentos($uid, $filters);

            // 3. Responde
            Response::success(['data' => $items]);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao listar investimentos');
        }
    }

    public function show(int $id): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $data = $this->service->getInvestimentoById($id, $uid);
            Response::success($data);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao buscar investimento');
        }
    }

    public function store(): void
    {
        try {
            $this->requireAuth();
            $userId = $this->resolveUserId();
            if ($userId === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $data = $this->request->all();

            $inv = $this->service->criarInvestimento($userId, $data);

            Response::success(['message' => 'Investimento criado com sucesso', 'id' => (int)$inv->id], 201);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao criar investimento');
        }
    }

    public function update(mixed $routeParam = null): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $id = $this->extractId($routeParam);
            if ($id <= 0) {
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            $payload = $this->request->all();

            $this->service->atualizarInvestimento($id, $uid, $payload);

            Response::success(['message' => 'Investimento atualizado com sucesso']);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao atualizar investimento');
        }
    }

    public function destroy(mixed $routeParam = null): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $id = $this->extractId($routeParam);
            if ($id <= 0) {
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            $this->service->excluirInvestimento($id, $uid);

            Response::success(['message' => 'Investimento excluído com sucesso']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao excluir investimento');
        }
    }

    public function atualizarPreco(mixed $routeParam = null): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $id = $this->extractId($routeParam);
            if ($id <= 0) {
                Response::validationError(['id' => 'ID inválido']);
                return;
            }

            $preco = $this->request->post('preco_atual');

            $this->service->atualizarPreco($id, $uid, $preco);

            Response::success(['message' => 'Preço atualizado com sucesso']);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao atualizar preço');
        }
    }

    public function categorias(): void
    {
        try {
            $this->requireAuth();
            $cats = $this->service->getCategorias();
            Response::success($cats);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao listar categorias');
        }
    }

    public function transacoes(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $tx = $this->service->getTransacoes($investimentoId, $uid);
            Response::success($tx);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao listar transações');
        }
    }

    public function criarTransacao(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $data = $this->request->all();
            $tx = $this->service->criarTransacao($investimentoId, $uid, $data);

            Response::success(['message' => 'Transação registrada com sucesso', 'id' => (int)$tx->id], 201);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            // Captura erro customizado (ex: Quantidade indisponível)
            $msg = stripos($e->getMessage(), 'Quantidade indispon') !== false
                ? 'Quantidade indisponível para venda.'
                : 'Falha ao registrar transação.';
            $this->failAndLog($e, $msg, 422);
        }
    }

    public function proventos(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $items = $this->service->getProventos($investimentoId, $uid);
            Response::success($items);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao listar proventos');
        }
    }

    public function criarProvento(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $data = $this->request->all();
            $p = $this->service->criarProvento($investimentoId, $uid, $data);

            Response::success(['message' => 'Provento registrado com sucesso', 'id' => (int)$p->id], 201);
        } catch (ValidationException $e) {
            Response::validationError($e->getErrors());
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Response::error('Investimento não encontrado', 404);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao registrar provento');
        }
    }

    public function stats(): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            $stats = $this->service->getStats($uid);

            Response::success($stats);
        } catch (Throwable $e) {
            $this->failAndLog($e, 'Falha ao calcular estatísticas');
        }
    }
}
