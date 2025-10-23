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

/**
 * API de Investimentos (Lukrato)
 *
 * Observações:
 * - Usa triggers do banco para validar venda e recalcular posição/PM.
 * - Campos calculados de resposta: valor_investido, valor_atual, lucro, rentabilidade.
 */
class InvestimentosController extends BaseController
{
    /** Lista investimentos do usuário (filtros opcionais) */
    public function index(): void
    {
        try {
            $this->requireAuth();

            $q           = trim((string)$this->request->get('q', ''));
            $categoriaId = (int)$this->request->get('categoria_id', 0);
            $contaId     = (int)$this->request->get('conta_id', 0);
            $ticker      = trim((string)$this->request->get('ticker', ''));
            $order       = in_array($this->request->get('order'), ['nome', 'ticker', 'quantidade', 'preco_medio', 'preco_atual', 'atualizado_em'])
                ? $this->request->get('order') : 'nome';
            $dir         = strtolower($this->request->get('dir')) === 'desc' ? 'desc' : 'asc';

            $query = Investimento::where('usuario_id', $this->userId);

            if ($q !== '') $query->where(function ($w) use ($q) {
                $w->where('nome', 'like', "%{$q}%")
                    ->orWhere('ticker', 'like', "%{$q}%");
            });
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
                    'nome'            => $i->nome,
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
                'user_id' => $this->userId ?? null,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao listar investimentos', 500);
        }
    }

    /** Detalhe de um investimento */
    public function show(int $id): void
    {
        try {
            $this->requireAuth();

            $i = Investimento::where('usuario_id', $this->userId)->find($id);
            if (!$i) {
                Response::error('Investimento não encontrado', 404);
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
                'nome'            => $i->nome,
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
                'user_id' => $this->userId ?? null,
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao buscar investimento', 500);
        }
    }

    /** Cria um investimento */
    public function store(): void
    {
        try {
            $this->requireAuth();

            $gump = new GUMP();
            $data = [
                'categoria_id' => $this->request->post('categoria_id'),
                'conta_id'     => $this->request->post('conta_id'),
                'nome'         => $this->request->post('nome'),
                'ticker'       => $this->request->post('ticker'),
                'preco_atual'  => $this->request->post('preco_atual'),
                'observacoes'  => $this->request->post('observacoes'),
            ];
            $gump->validation_rules([
                'categoria_id' => 'required|integer|min_numeric,1',
                'conta_id'     => 'integer',
                'nome'         => 'required|min_len,2|max_len,200',
                'ticker'       => 'max_len,20',
                'preco_atual'  => 'numeric',
            ]);
            $gump->filter_rules([
                'nome'        => 'trim|sanitize_string',
                'ticker'      => 'trim|upper',
                'observacoes' => 'trim',
            ]);
            $data = $gump->run($data);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            $inv = new Investimento();
            $inv->usuario_id   = $this->userId;
            $inv->categoria_id = (int)$data['categoria_id'];
            $inv->conta_id     = !empty($data['conta_id']) ? (int)$data['conta_id'] : null;
            $inv->nome         = $data['nome'];
            $inv->ticker       = $data['ticker'] ?: null;
            $inv->preco_atual  = $data['preco_atual'] !== '' ? (float)$data['preco_atual'] : null;
            $inv->save();

            Response::success(['message' => 'Investimento criado com sucesso', 'id' => (int)$inv->id], 201);
        } catch (Exception $e) {
            LogService::error('Falha ao criar investimento', [
                'user_id' => $this->userId ?? null,
                'payload' => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao criar investimento', 500);
        }
    }

    /** Atualiza um investimento */
    public function update(int $id): void
    {
        try {
            $this->requireAuth();

            $inv = Investimento::where('usuario_id', $this->userId)->find($id);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $data = [
                'categoria_id' => $this->request->post('categoria_id'),
                'conta_id'     => $this->request->post('conta_id'),
                'nome'         => $this->request->post('nome'),
                'ticker'       => $this->request->post('ticker'),
                'preco_atual'  => $this->request->post('preco_atual'),
                'observacoes'  => $this->request->post('observacoes'),
            ];
            $gump->validation_rules([
                'categoria_id' => 'required|integer|min_numeric,1',
                'conta_id'     => 'integer',
                'nome'         => 'required|min_len,2|max_len,200',
                'ticker'       => 'max_len,20',
                'preco_atual'  => 'numeric',
            ]);
            $gump->filter_rules([
                'nome'        => 'trim|sanitize_string',
                'ticker'      => 'trim|upper',
                'observacoes' => 'trim',
            ]);
            $data = $gump->run($data);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            $inv->categoria_id = (int)$data['categoria_id'];
            $inv->conta_id     = !empty($data['conta_id']) ? (int)$data['conta_id'] : null;
            $inv->nome         = $data['nome'];
            $inv->ticker       = $data['ticker'] ?: null;
            $inv->preco_atual  = $data['preco_atual'] !== '' ? (float)$data['preco_atual'] : null;
            $inv->observacoes  = $data['observacoes'] ?: null;
            $inv->save();

            Response::success(['message' => 'Investimento atualizado com sucesso']);
        } catch (Exception $e) {
            LogService::error('Falha ao atualizar investimento', [
                'user_id' => $this->userId ?? null,
                'id' => $id,
                'payload' => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao atualizar investimento', 500);
        }
    }

    /** Exclui um investimento (cascata apaga transações/proventos) */
    public function destroy(int $id): void
    {
        try {
            $this->requireAuth();

            $inv = Investimento::where('usuario_id', $this->userId)->find($id);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $inv->delete();
            Response::success(['message' => 'Investimento excluído com sucesso']);
        } catch (Exception $e) {
            LogService::error('Falha ao excluir investimento', [
                'user_id' => $this->userId ?? null,
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao excluir investimento', 500);
        }
    }

    /** Atualiza somente o preço atual */
    public function atualizarPreco(int $id): void
    {
        try {
            $this->requireAuth();

            $preco = $this->request->post('preco_atual');
            if ($preco === null || $preco === '') {
                Response::validationError(['preco_atual' => 'Informe o preço atual']);
                return;
            }

            $inv = Investimento::where('usuario_id', $this->userId)->find($id);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $inv->preco_atual = (float)$preco; // trigger de auditoria registra mudança
            $inv->save();

            Response::success(['message' => 'Preço atualizado com sucesso']);
        } catch (Exception $e) {
            LogService::error('Falha ao atualizar preço de investimento', [
                'user_id' => $this->userId ?? null,
                'id' => $id,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao atualizar preço', 500);
        }
    }

    /** Lista categorias */
    public function categorias(): void
    {
        try {
            $this->requireAuth();
            $cats = CategoriaInvestimento::orderBy('nome', 'asc')->get();
            Response::success($cats);
        } catch (Exception $e) {
            LogService::error('Falha ao listar categorias de investimento', [
                'user_id' => $this->userId ?? null,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao listar categorias', 500);
        }
    }

    /** Lista transações do investimento */
    public function transacoes(int $investimentoId): void
    {
        try {
            $this->requireAuth();

            // segurança: investimento deve ser do usuário
            $exists = Investimento::where('usuario_id', $this->userId)->find($investimentoId);
            if (!$exists) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $tx = TransacaoInvestimento::where('investimento_id', $investimentoId)
                ->orderBy('data_transacao', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Response::success($tx);
        } catch (Exception $e) {
            LogService::error('Falha ao listar transações de investimento', [
                'user_id' => $this->userId ?? null,
                'investimento_id' => $investimentoId,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao listar transações', 500);
        }
    }

    /** Cria transação (compra/venda) */
    public function criarTransacao(int $investimentoId): void
    {
        try {
            $this->requireAuth();

            $inv = Investimento::where('usuario_id', $this->userId)->find($investimentoId);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
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
            $tx->save(); // triggers do BD validam/atualizam posição

            Response::success(['message' => 'Transação registrada com sucesso', 'id' => (int)$tx->id], 201);
        } catch (Exception $e) {
            LogService::error('Falha ao criar transação de investimento', [
                'user_id' => $this->userId ?? null,
                'investimento_id' => $investimentoId,
                'payload' => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            // se vier erro de trigger (SQLSTATE '45000'), propagar mensagem amigável
            $msg = stripos($e->getMessage(), 'Quantidade indisponível') !== false
                ? 'Quantidade indisponível para venda'
                : 'Falha ao registrar transação';
            Response::error($msg, 422);
        }
    }

    /** Lista proventos do investimento */
    public function proventos(int $investimentoId): void
    {
        try {
            $this->requireAuth();

            $exists = Investimento::where('usuario_id', $this->userId)->find($investimentoId);
            if (!$exists) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $items = Provento::where('investimento_id', $investimentoId)
                ->orderBy('data_pagamento', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Response::success($items);
        } catch (Exception $e) {
            LogService::error('Falha ao listar proventos', [
                'user_id' => $this->userId ?? null,
                'investimento_id' => $investimentoId,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao listar proventos', 500);
        }
    }

    /** Cria provento (dividendo/JCP/rendimento) */
    public function criarProvento(int $investimentoId): void
    {
        try {
            $this->requireAuth();

            $inv = Investimento::where('usuario_id', $this->userId)->find($investimentoId);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $data = [
                'valor'          => $this->request->post('valor'),
                'tipo'           => $this->request->post('tipo'),
                'data_pagamento' => $this->request->post('data_pagamento'),
                'observacoes'    => $this->request->post('observacoes'),
            ];
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
                'user_id' => $this->userId ?? null,
                'investimento_id' => $investimentoId,
                'payload' => $this->request->all(),
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao registrar provento', 500);
        }
    }

    /** Estatísticas rápidas para cards do dashboard */
    public function stats(): void
    {
        try {
            $this->requireAuth();

            $itens = Investimento::where('usuario_id', $this->userId)->get();
            $totInvestido = 0.0;
            $totAtual     = 0.0;

            foreach ($itens as $i) {
                $totInvestido += (float)$i->quantidade * (float)$i->preco_medio;
                $totAtual     += (float)$i->quantidade * (float)($i->preco_atual ?? 0);
            }

            $lucro = $totAtual - $totInvestido;
            $rent  = $totInvestido > 0 ? round(($lucro / $totInvestido) * 100, 2) : 0.0;

            Response::success([
                'total_investido' => round($totInvestido, 2),
                'valor_atual'     => round($totAtual, 2),
                'lucro'           => round($lucro, 2),
                'rentabilidade'   => $rent,
                'quantidade_itens' => (int)$itens->count(),
            ]);
        } catch (Exception $e) {
            LogService::error('Falha ao calcular stats de investimentos', [
                'user_id' => $this->userId ?? null,
                'exception' => $e->getMessage(),
            ]);
            Response::error('Falha ao calcular estatísticas', 500);
        }
    }
}
