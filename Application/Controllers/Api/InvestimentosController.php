<?php

namespace Application\Controllers\Api;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Investimento;
use Application\Models\TransacaoInvestimento;
use Application\Models\Provento;
use Application\Models\CategoriaInvestimento;
use Application\Services\LogService;
use Exception;
use GUMP;

class InvestimentosController extends BaseController
{
    /** Resolve o ID do usuário logado de forma robusta (propriedade + fallbacks de sessão). */
    private function resolveUserId(): ?int
    {
        $id = $this->userId
            ?? ($_SESSION['user']['id'] ?? null)
            ?? ($_SESSION['auth']['id'] ?? null)
            ?? ($_SESSION['usuario_id'] ?? null);

        return $id !== null ? (int)$id : null;
    }

    /** Normaliza números com vírgula para ponto antes das validações/conversões. */
    private function normalizeNumerics(array &$data, array $keys): void
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $data) && $data[$k] !== null && $data[$k] !== '') {
                $data[$k] = str_replace(',', '.', (string)$data[$k]);
            }
        }
    }

    public function index(): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $q           = trim((string)$this->request->get('q', ''));
            $categoriaId = (int)$this->request->get('categoria_id', 0);
            $contaId     = (int)$this->request->get('conta_id', 0);
            $ticker      = trim((string)$this->request->get('ticker', ''));
            $order       = in_array($this->request->get('order'), ['nome', 'ticker', 'quantidade', 'preco_medio', 'preco_atual', 'atualizado_em'])
                ? $this->request->get('order') : 'nome';
            $dir         = strtolower($this->request->get('dir')) === 'desc' ? 'desc' : 'asc';

            $query = Investimento::where('user_id', $uid);

            if ($q !== '') {
                $query->where(function ($w) use ($q) {
                    $w->where('nome', 'like', "%{$q}%")
                        ->orWhere('ticker', 'like', "%{$q}%");
                });
            }
            if ($categoriaId > 0) $query->where('categoria_id', $categoriaId);
            if ($contaId > 0)     $query->where('conta_id', $contaId);
            if ($ticker !== '')   $query->where('ticker', $ticker);

            $items = $query->orderBy($order, $dir)->get()->map(function ($i) {
                $valorInvestido = (float)$i->quantidade * (float)$i->preco_medio;
                $valorAtual     = (float)$i->quantidade * (float)($i->preco_atual ?? 0);
                $lucro          = $valorAtual - $valorInvestido;
                $rentabilidade  = $valorInvestido > 0 ? round(($lucro / $valorInvestido) * 100, 2) : 0.0;
                return [
                    'id'              => (int)$i->id,
                    'categoria_id'    => (int)$i->categoria_id,
                    'conta_id'        => $i->conta_id ? (int)$i->conta_id : null,
                    'nome'            => (string)$i->nome,
                    'ticker'          => $i->ticker,
                    'quantidade'      => (float)$i->quantidade,
                    'preco_medio'     => (float)$i->preco_medio,
                    'preco_atual'     => $i->preco_atual !== null ? (float)$i->preco_atual : null,
                    'valor_investido' => round($valorInvestido, 2),
                    'valor_atual'     => round($valorAtual, 2),
                    'lucro'           => round($lucro, 2),
                    'rentabilidade'   => $rentabilidade,
                    'atualizado_em'   => (string)$i->atualizado_em,
                ];
            });

            Response::success(['data' => $items]);
        } catch (Exception $e) {
            LogService::error('Falha ao listar investimentos', [
                'user_id'   => $this->resolveUserId(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao listar investimentos', 500);
        }
    }

    public function show(int $id): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $i = Investimento::where('user_id', $uid)->find($id);
            if (!$i) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $valorInvestido = (float)$i->quantidade * (float)$i->preco_medio;
            $valorAtual     = (float)$i->quantidade * (float)($i->preco_atual ?? 0);
            $lucro          = $valorAtual - $valorInvestido;
            $rentabilidade  = $valorInvestido > 0 ? round(($lucro / $valorInvestido) * 100, 2) : 0.0;

            Response::success([
                'id'              => (int)$i->id,
                'categoria_id'    => (int)$i->categoria_id,
                'conta_id'        => $i->conta_id ? (int)$i->conta_id : null,
                'nome'            => (string)$i->nome,
                'ticker'          => $i->ticker,
                'quantidade'      => (float)$i->quantidade,
                'preco_medio'     => (float)$i->preco_medio,
                'preco_atual'     => $i->preco_atual !== null ? (float)$i->preco_atual : null,
                'valor_investido' => round($valorInvestido, 2),
                'valor_atual'     => round($valorAtual, 2),
                'lucro'           => round($lucro, 2),
                'rentabilidade'   => $rentabilidade,
                'observacoes'     => $i->observacoes,
                'atualizado_em'   => (string)$i->atualizado_em,
            ]);
        } catch (Exception $e) {
            LogService::error('Falha ao buscar investimento', [
                'user_id'   => $this->resolveUserId(),
                'id'        => $id,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao buscar investimento', 500);
        }
    }

    public function store(): void
    {
        error_log("[DEBUG] Entrou no store() de investimentos");

        try {
            $this->requireAuth();
            $userId = $this->resolveUserId();
            error_log("[DEBUG] userId resolvido: " . var_export($userId, true));

            if ($userId === null) {
                $_SESSION['message'] = 'Sessao expirada. Faça login novamente.';
                $_SESSION['message_type'] = 'danger';
                Response::redirectTo(BASE_URL . 'investimentos');
                return;
            }

            $gump = new GUMP();
            $data = [
                'categoria_id' => $this->request->post('categoria_id'),
                'conta_id'     => $this->request->post('conta_id'),
                'nome'         => $this->request->post('nome'),
                'ticker'       => $this->request->post('ticker'),
                'quantidade'   => $this->request->post('quantidade'),
                'preco_medio'  => $this->request->post('preco_medio'),
                'preco_atual'  => $this->request->post('preco_atual'),
                'data_compra'  => $this->request->post('data_compra'),
                'observacoes'  => $this->request->post('observacoes'),
            ];
            // aceita números com vírgula
            $this->normalizeNumerics($data, ['quantidade', 'preco_medio', 'preco_atual']);

            $gump->validation_rules([
                'categoria_id' => 'required|integer|min_numeric,1',
                'conta_id'     => 'integer',
                'nome'         => 'required|min_len,2|max_len,200',
                'ticker'       => 'max_len,20',
                'quantidade'   => 'required|numeric|min_numeric,0.0001',
                'preco_medio'  => 'required|numeric|min_numeric,0',
                'preco_atual'  => 'numeric',
                'data_compra'  => 'date',
            ]);
            $gump->filter_rules([
                'nome'        => 'trim|sanitize_string',
                'ticker'      => 'trim|sanitize_string',
                'quantidade'  => 'trim',
                'preco_medio' => 'trim',
                'preco_atual' => 'trim',
                'observacoes' => 'trim',
            ]);
            $data = $gump->run($data);
            // Detecta submissão de formulário HTML para decidir entre redirect e JSON
            $ct = strtolower($this->request->contentType() ?? '');
            $isFormSubmit = (strpos($ct, 'application/x-www-form-urlencoded') !== false)
                || (strpos($ct, 'multipart/form-data') !== false);
            if ($data === false) {
                if ($isFormSubmit && !$this->request->isAjax()) {
                    $_SESSION['message'] = 'Falha de validacao ao criar investimento.';
                    $_SESSION['message_type'] = 'danger';
                    Response::redirectTo(BASE_URL . 'investimentos');
                    return;
                }
                Response::validationError($gump->get_errors_array());
                return;
            }

            $inv = new Investimento();
            $inv->user_id      = $userId; // ESSENCIAL
            $inv->categoria_id = (int)$data['categoria_id'];
            $inv->conta_id     = !empty($data['conta_id']) ? (int)$data['conta_id'] : null;
            $inv->nome         = (string)$data['nome'];
            $inv->ticker       = $data['ticker'] ? strtoupper($data['ticker']) : null;
            $inv->quantidade   = isset($data['quantidade']) ? (float)$data['quantidade'] : 0.0;
            $inv->preco_medio  = isset($data['preco_medio']) ? (float)$data['preco_medio'] : 0.0;
            $inv->preco_atual  = ($data['preco_atual'] !== '' && $data['preco_atual'] !== null) ? (float)$data['preco_atual'] : null;
            $inv->data_compra  = !empty($data['data_compra']) ? $data['data_compra'] : null;
            $inv->observacoes  = $data['observacoes'] ?: null;
            $inv->save();

            if ($isFormSubmit && !$this->request->isAjax()) {
                $_SESSION['message'] = 'Investimento criado com sucesso!';
                $_SESSION['message_type'] = 'success';
                Response::redirectTo(BASE_URL . 'investimentos');
                return;
            }

            Response::success(['message' => 'Investimento criado com sucesso', 'id' => (int)$inv->id], 201);
        } catch (Exception $e) {
            error_log('[ERROR INVESTIMENTOS STORE] ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            LogService::error('Falha ao criar investimento', [
                'user_id'   => $this->resolveUserId(),
                'payload'   => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            if ($isFormSubmit && !$this->request->isAjax()) {
                $_SESSION['message'] = 'Erro ao criar investimento.';
                $_SESSION['message_type'] = 'danger';
                Response::redirectTo(BASE_URL . 'investimentos');
                return;
            }
            Response::error('Falha ao criar investimento', 500);
        }
    }

    public function update($routeParam = null): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $id = 0;
            if (is_array($routeParam) && isset($routeParam['id'])) {
                $id = (int)$routeParam['id'];
            } elseif (is_numeric($routeParam)) {
                $id = (int)$routeParam;
            }
            if ($id <= 0) {
                Response::validationError(['id' => 'ID invalido']);
                return;
            }

            $inv = Investimento::where('user_id', $uid)->find($id);
            if (!$inv) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $payload = $this->request->post();

            // normaliza vírgulas
            $this->normalizeNumerics($payload, ['quantidade', 'preco_medio', 'preco_atual']);

            $gump->validation_rules([
                'categoria_id' => 'integer|min_numeric,1',
                'conta_id'     => 'integer',
                'nome'         => 'min_len,2|max_len,200',
                'ticker'       => 'max_len,20',
                'quantidade'   => 'numeric|min_numeric,0',
                'preco_medio'  => 'numeric|min_numeric,0',
                'preco_atual'  => 'numeric',
                'data_compra'  => 'date',
            ]);
            $gump->filter_rules([
                'nome'        => 'trim|sanitize_string',
                'ticker'      => 'trim|sanitize_string',
                'observacoes' => 'trim',
            ]);
            $data = $gump->run($payload ?? []);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            foreach (['categoria_id', 'conta_id', 'nome', 'ticker', 'observacoes'] as $f) {
                if (array_key_exists($f, $data)) $inv->{$f} = $data[$f] !== '' ? $data[$f] : null;
            }
            foreach (['quantidade', 'preco_medio', 'preco_atual'] as $f) {
                if (array_key_exists($f, $data)) $inv->{$f} = $data[$f] !== '' ? (float)$data[$f] : null;
            }
            if (array_key_exists('data_compra', $data)) $inv->data_compra = $data['data_compra'] ?: null;

            $inv->save();
            Response::success(['message' => 'Investimento atualizado com sucesso']);
        } catch (Exception $e) {
            LogService::error('Falha ao atualizar investimento', [
                'user_id'   => $this->resolveUserId(),
                'payload'   => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao atualizar investimento', 500);
        }
    }

    public function destroy($routeParam = null): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $id = 0;
            if (is_array($routeParam) && isset($routeParam['id'])) {
                $id = (int)$routeParam['id'];
            } elseif (is_numeric($routeParam)) {
                $id = (int)$routeParam;
            }
            if ($id <= 0) {
                Response::validationError(['id' => 'ID invalido']);
                return;
            }

            $inv = Investimento::where('user_id', $uid)->find($id);
            if (!$inv) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $inv->delete();
            Response::success(['message' => 'Investimento excluido com sucesso']);
        } catch (Exception $e) {
            LogService::error('Falha ao excluir investimento', [
                'user_id'   => $this->resolveUserId(),
                'payload'   => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao excluir investimento', 500);
        }
    }

    public function atualizarPreco($routeParam = null): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $id = 0;
            if (is_array($routeParam) && isset($routeParam['id'])) $id = (int)$routeParam['id'];
            elseif (is_numeric($routeParam))                        $id = (int)$routeParam;
            if ($id <= 0) {
                Response::validationError(['id' => 'ID invalido']);
                return;
            }

            $inv = Investimento::where('user_id', $uid)->find($id);
            if (!$inv) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $preco = $this->request->post('preco_atual');
            if ($preco === null || $preco === '') {
                Response::validationError(['preco_atual' => 'Informe o preco_atual']);
                return;
            }

            $preco = (float)str_replace(',', '.', (string)$preco);
            $inv->preco_atual = $preco;
            $inv->save();

            Response::success(['message' => 'Preco atualizado com sucesso']);
        } catch (Exception $e) {
            LogService::error('Falha ao atualizar preco de investimento', [
                'user_id'   => $this->resolveUserId(),
                'payload'   => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao atualizar preco', 500);
        }
    }

    public function categorias(): void
    {
        try {
            $this->requireAuth();
            $cats = CategoriaInvestimento::orderBy('nome', 'asc')->get();
            Response::success($cats);
        } catch (Exception $e) {
            LogService::error('Falha ao listar categorias investimento', [
                'user_id'   => $this->resolveUserId(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao listar categorias', 500);
        }
    }

    public function transacoes(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $exists = Investimento::where('user_id', $uid)->find($investimentoId);
            if (!$exists) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $tx = TransacaoInvestimento::where('investimento_id', $investimentoId)
                ->orderBy('data_transacao', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Response::success($tx);
        } catch (Exception $e) {
            LogService::error('Falha ao listar transacoes de investimento', [
                'user_id'        => $this->resolveUserId(),
                'investimento_id' => $investimentoId,
                'exception'      => $e->getMessage(),
            ]);
            Response::error('Falha ao listar transacoes', 500);
        }
    }

    public function criarTransacao(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $inv = Investimento::where('user_id', $uid)->find($investimentoId);
            if (!$inv) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $data = [
                'tipo'           => $this->request->post('tipo'),
                'quantidade'     => $this->request->post('quantidade'),
                'preco'          => $this->request->post('preco'),
                'taxas'          => $this->request->post('taxas'),
                'data_transacao' => $this->request->post('data_transacao'),
                'observacoes'    => $this->request->post('observacoes'),
            ];
            $this->normalizeNumerics($data, ['quantidade', 'preco', 'taxas']);

            $gump->validation_rules([
                'tipo'           => 'required|contains_list,compra;venda',
                'quantidade'     => 'required|numeric|min_numeric,0.0001',
                'preco'          => 'required|numeric|min_numeric,0',
                'taxas'          => 'numeric',
                'data_transacao' => 'required|date',
            ]);
            $gump->filter_rules([
                'tipo'           => 'trim|lower',
                'observacoes'    => 'trim',
            ]);
            $data = $gump->run($data);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            $tx = new TransacaoInvestimento();
            $tx->investimento_id = (int)$inv->id;
            $tx->tipo            = $data['tipo'];
            $tx->quantidade      = (float)$data['quantidade'];
            $tx->preco           = (float)$data['preco'];
            $tx->taxas           = isset($data['taxas']) && $data['taxas'] !== '' ? (float)$data['taxas'] : 0.0;
            $tx->data_transacao  = $data['data_transacao'];
            $tx->observacoes     = $data['observacoes'] ?: null;
            $tx->save();

            Response::success(['message' => 'Transacao registrada com sucesso', 'id' => (int)$tx->id], 201);
        } catch (Exception $e) {
            LogService::error('Falha ao criar transacao de investimento', [
                'user_id'        => $this->resolveUserId(),
                'investimento_id' => $investimentoId,
                'payload'        => $this->request->all(),
                'exception'      => $e->getMessage(),
            ]);
            $msg = stripos($e->getMessage(), 'Quantidade indispon') !== false
                ? 'Quantidade indisponivel para venda'
                : 'Falha ao registrar transacao';
            Response::error($msg, 422);
        }
    }

    public function proventos(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $exists = Investimento::where('user_id', $uid)->find($investimentoId);
            if (!$exists) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $items = Provento::where('investimento_id', $investimentoId)
                ->orderBy('data_pagamento', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Response::success($items);
        } catch (Exception $e) {
            LogService::error('Falha ao listar proventos', [
                'user_id'        => $this->resolveUserId(),
                'investimento_id' => $investimentoId,
                'exception'      => $e->getMessage(),
            ]);
            Response::error('Falha ao listar proventos', 500);
        }
    }

    public function criarProvento(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $inv = Investimento::where('user_id', $uid)->find($investimentoId);
            if (!$inv) {
                Response::error('Investimento nao encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $data = [
                'valor'          => $this->request->post('valor'),
                'tipo'           => $this->request->post('tipo'),
                'data_pagamento' => $this->request->post('data_pagamento'),
                'observacoes'    => $this->request->post('observacoes'),
            ];
            $this->normalizeNumerics($data, ['valor']);

            $gump->validation_rules([
                'valor'          => 'required|numeric|min_numeric,0.01',
                'tipo'           => 'required|contains_list,dividendo;jcp;rendimento',
                'data_pagamento' => 'required|date',
            ]);
            $gump->filter_rules([
                'tipo'        => 'trim|lower',
                'observacoes' => 'trim',
            ]);
            $data = $gump->run($data);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            $p = new Provento();
            $p->investimento_id = (int)$inv->id;
            $p->valor           = (float)$data['valor'];
            $p->tipo            = $data['tipo'];
            $p->data_pagamento  = $data['data_pagamento'];
            $p->observacoes     = $data['observacoes'] ?: null;
            $p->save();

            Response::success(['message' => 'Provento registrado com sucesso', 'id' => (int)$p->id], 201);
        } catch (Exception $e) {
            LogService::error('Falha ao criar provento', [
                'user_id'        => $this->resolveUserId(),
                'investimento_id' => $investimentoId,
                'payload'        => $this->request->all(),
                'exception'      => $e->getMessage(),
            ]);
            Response::error('Falha ao registrar provento', 500);
        }
    }

    public function stats(): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessao expirada', 401);
                return;
            }

            $itens = Investimento::where('user_id', $uid)->get();
            $totInvestido = 0.0;
            $totAtual     = 0.0;

            foreach ($itens as $i) {
                $totInvestido += (float)$i->quantidade * (float)$i->preco_medio;
                $totAtual     += (float)$i->quantidade * (float)($i->preco_atual ?? 0);
            }

            $lucro = $totAtual - $totInvestido;
            $rent  = $totInvestido > 0 ? round(($lucro / $totInvestido) * 100, 2) : 0.0;

            Response::success([
                'total_investido'  => round($totInvestido, 2),
                'valor_atual'      => round($totAtual, 2),
                'lucro'            => round($lucro, 2),
                'rentabilidade'    => $rent,
                'quantidade_itens' => (int)$itens->count(),
            ]);
        } catch (Exception $e) {
            LogService::error('Falha ao calcular stats investimentos', [
                'user_id'   => $this->resolveUserId(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao calcular estatisticas', 500);
        }
    }
}
