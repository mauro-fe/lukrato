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
use ValueError;
use Throwable;

// --- Enums para Tipos Fixos (PHP 8.1+) ---

enum TransacaoTipo: string
{
    case COMPRA = 'compra';
    case VENDA = 'venda';
    
    public static function listValues(): string
    {
        return implode(';', array_column(self::cases(), 'value'));
    }
}

enum ProventoTipo: string
{
    case DIVIDENDO = 'dividendo';
    case JCP = 'jcp';
    case RENDIMENTO = 'rendimento';

    public static function listValues(): string
    {
        return implode(';', array_column(self::cases(), 'value'));
    }
}

class InvestimentosController extends BaseController
{
    // --- Utilidades ---

    /** Resolve o ID do usuário logado de forma robusta. */
    private function resolveUserId(): ?int
    {
        // Uso de Nullsafe para checagem mais limpa, mantendo fallbacks originais.
        $id = $this->userId
            ?? ($_SESSION['user']['id'] ?? null)
            ?? ($_SESSION['auth']['id'] ?? null)
            ?? ($_SESSION['usuario_id'] ?? null);

        return $id !== null ? (int)$id : null;
    }

    /** Normaliza números com vírgula para ponto. */
    private function normalizeNumerics(array &$data, array $keys): void
    {
        foreach ($keys as $k) {
            if (isset($data[$k]) && $data[$k] !== '') {
                $data[$k] = str_replace(',', '.', (string)$data[$k]);
            }
        }
    }
    
    /** Extrai o ID da rota ou de arrays genéricos. */
    private function extractId(mixed $routeParam): int
    {
        if (is_array($routeParam) && isset($routeParam['id'])) {
            return (int)$routeParam['id'];
        }
        if (is_numeric($routeParam)) {
            return (int)$routeParam;
        }
        return 0;
    }
    
    /** Calcula as métricas de rentabilidade de um investimento. */
    private function calculateInvestmentMetrics(Investimento $i): array
    {
        $valorInvestido = (float)$i->quantidade * (float)$i->preco_medio;
        $valorAtual     = (float)$i->quantidade * (float)($i->preco_atual ?? $i->preco_medio ?? 0); // fallback para preço médio
        $lucro          = $valorAtual - $valorInvestido;
        $rentabilidade  = $valorInvestido > 0 ? round(($lucro / $valorInvestido) * 100, 2) : 0.0;

        return [
            'valor_investido' => round($valorInvestido, 2),
            'valor_atual'     => round($valorAtual, 2),
            'lucro'           => round($lucro, 2),
            'rentabilidade'   => $rentabilidade,
        ];
    }
    
    // --- Endpoints ---

    /**
     * Lista todos os investimentos do usuário com filtros opcionais.
     */
    public function index(): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            // 1. Parsing e tipagem de filtros
            $q           = trim((string)$this->request->get('q', ''));
            $categoriaId = (int)($this->request->get('categoria_id', 0) ?: 0);
            $contaId     = (int)($this->request->get('conta_id', 0) ?: 0);
            $ticker      = trim((string)$this->request->get('ticker', ''));
            
            $allowedOrder = ['nome', 'ticker', 'quantidade', 'preco_medio', 'preco_atual', 'atualizado_em'];
            $order       = $this->request->get('order') && in_array($this->request->get('order'), $allowedOrder)
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

            $items = $query->orderBy($order, $dir)->get()->map(function (Investimento $i) {
                $metrics = $this->calculateInvestmentMetrics($i);
                
                return array_merge([
                    'id'              => (int)$i->id,
                    'categoria_id'    => (int)$i->categoria_id,
                    'conta_id'        => $i->conta_id ? (int)$i->conta_id : null,
                    'nome'            => (string)$i->nome,
                    'ticker'          => $i->ticker,
                    'quantidade'      => (float)$i->quantidade,
                    'preco_medio'     => (float)$i->preco_medio,
                    // Uso do Nullsafe para acesso seguro
                    'preco_atual'     => $i->preco_atual !== null ? (float)$i->preco_atual : null,
                    'atualizado_em'   => (string)$i->atualizado_em,
                ], $metrics);
            });

            Response::success(['data' => $items]);
        } catch (Throwable $e) {
            LogService::error('Falha ao listar investimentos', ['user_id' => $this->resolveUserId(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao listar investimentos', 500);
        }
    }

    /**
     * Exibe um investimento específico.
     */
    public function show(int $id): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            /** @var Investimento|null $i */
            $i = Investimento::where('user_id', $uid)->find($id);
            if (!$i) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $metrics = $this->calculateInvestmentMetrics($i);

            Response::success(array_merge([
                'id'              => (int)$i->id,
                'categoria_id'    => (int)$i->categoria_id,
                'conta_id'        => $i->conta_id ? (int)$i->conta_id : null,
                'nome'            => (string)$i->nome,
                'ticker'          => $i->ticker,
                'quantidade'      => (float)$i->quantidade,
                'preco_medio'     => (float)$i->preco_medio,
                'preco_atual'     => $i->preco_atual !== null ? (float)$i->preco_atual : null,
                'observacoes'     => $i->observacoes,
                'atualizado_em'   => (string)$i->atualizado_em,
            ], $metrics));
        } catch (Throwable $e) {
            LogService::error('Falha ao buscar investimento', ['user_id' => $this->resolveUserId(), 'id' => $id, 'exception' => $e->getMessage()]);
            Response::error('Falha ao buscar investimento', 500);
        }
    }

    /**
     * Cria um novo investimento. (Lógica de redirect removida, focada em JSON API)
     */
    public function store(): void
    {
        try {
            $this->requireAuth();
            $userId = $this->resolveUserId();

            if ($userId === null) {
                Response::error('Sessão expirada', 401);
                return;
            }
            
            // 1. Coleta de dados (API deve aceitar JSON ou POST)
            $data = $this->request->all();

            $gump = new GUMP();
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
                'ticker'      => 'trim|sanitize_string|upper', // Adicionado strtoupper
                'observacoes' => 'trim',
            ]);
            $data = $gump->run($data);
            
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            // 2. Persistência
            $inv = new Investimento();
            $inv->user_id      = $userId;
            $inv->categoria_id = (int)$data['categoria_id'];
            $inv->conta_id     = !empty($data['conta_id']) ? (int)$data['conta_id'] : null;
            $inv->nome         = (string)$data['nome'];
            $inv->ticker       = $data['ticker'] ?: null; // Ticker já foi upper-cased no filtro
            $inv->quantidade   = (float)$data['quantidade'];
            $inv->preco_medio  = (float)$data['preco_medio'];
            // Verifica se preco_atual existe e não é string vazia antes de converter para float
            $inv->preco_atual  = ($data['preco_atual'] !== '' && $data['preco_atual'] !== null) ? (float)$data['preco_atual'] : null;
            $inv->data_compra  = !empty($data['data_compra']) ? $data['data_compra'] : null;
            $inv->observacoes  = $data['observacoes'] ?: null;
            $inv->save();

            Response::success(['message' => 'Investimento criado com sucesso', 'id' => (int)$inv->id], 201);

        } catch (Throwable $e) {
            LogService::error('Falha ao criar investimento', ['user_id' => $userId, 'payload' => $this->request->all(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao criar investimento', 500);
        }
    }

    /**
     * Atualiza um investimento existente.
     */
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

            /** @var Investimento|null $inv */
            $inv = Investimento::where('user_id', $uid)->find($id);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $payload = $this->request->all(); // Usa all() para pegar JSON/POST
            
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
                'ticker'      => 'trim|sanitize_string|upper', // Adicionado strtoupper
                'observacoes' => 'trim',
            ]);
            
            $data = $gump->run($payload);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            // Mapeamento e atualização
            foreach ($data as $f => $val) {
                if ($f === 'id' || $f === 'user_id') continue; 
                
                if (in_array($f, ['quantidade', 'preco_medio', 'preco_atual']) && $val !== '') {
                    $inv->{$f} = (float)$val;
                } elseif ($val !== '') {
                    $inv->{$f} = $val;
                } elseif (in_array($f, ['conta_id', 'data_compra', 'ticker', 'observacoes'])) {
                    // Permite definir como NULL/vazio se a chave for enviada vazia
                    $inv->{$f} = null;
                }
            }
            
            $inv->save();
            Response::success(['message' => 'Investimento atualizado com sucesso']);
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar investimento', ['user_id' => $this->resolveUserId(), 'payload' => $this->request->all(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao atualizar investimento', 500);
        }
    }

    /**
     * Exclui um investimento.
     */
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

            $inv = Investimento::where('user_id', $uid)->find($id);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $inv->delete();
            Response::success(['message' => 'Investimento excluído com sucesso']);
        } catch (Throwable $e) {
            LogService::error('Falha ao excluir investimento', ['user_id' => $this->resolveUserId(), 'payload' => $this->request->all(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao excluir investimento', 500);
        }
    }

    /**
     * Atualiza apenas o preço atual de um investimento.
     */
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

            $inv = Investimento::where('user_id', $uid)->find($id);
            if (!$inv) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $precoRaw = $this->request->post('preco_atual');
            if ($precoRaw === null || $precoRaw === '') {
                Response::validationError(['preco_atual' => 'Informe o preço atual']);
                return;
            }
            
            // Sanitização robusta
            $preco = (float)str_replace(',', '.', (string)$precoRaw);
            if (!is_numeric($preco) || $preco < 0) {
                Response::validationError(['preco_atual' => 'Preço inválido.']);
                return;
            }

            $inv->preco_atual = $preco;
            $inv->atualizado_em = date('Y-m-d H:i:s');
            $inv->save();

            Response::success(['message' => 'Preço atualizado com sucesso']);
        } catch (Throwable $e) {
            LogService::error('Falha ao atualizar preço de investimento', ['user_id' => $this->resolveUserId(), 'payload' => $this->request->all(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao atualizar preço', 500);
        }
    }

    /**
     * Lista as categorias de investimento disponíveis.
     */
    public function categorias(): void
    {
        try {
            $this->requireAuth();
            $cats = CategoriaInvestimento::orderBy('nome', 'asc')->get();
            Response::success($cats);
        } catch (Throwable $e) {
            LogService::error('Falha ao listar categorias investimento', ['user_id' => $this->resolveUserId(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao listar categorias', 500);
        }
    }
    
    /**
     * Lista as transações de um investimento.
     */
    public function transacoes(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            if (!Investimento::where('user_id', $uid)->find($investimentoId)) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $tx = TransacaoInvestimento::where('investimento_id', $investimentoId)
                ->orderBy('data_transacao', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Response::success($tx);
        } catch (Throwable $e) {
            LogService::error('Falha ao listar transacoes de investimento', ['user_id' => $this->resolveUserId(), 'investimento_id' => $investimentoId, 'exception' => $e->getMessage()]);
            Response::error('Falha ao listar transações', 500);
        }
    }

    /**
     * Cria uma transação de compra/venda para um investimento.
     */
    public function criarTransacao(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            if (!Investimento::where('user_id', $uid)->find($investimentoId)) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $data = $this->request->all();
            $this->normalizeNumerics($data, ['quantidade', 'preco', 'taxas']);

            $gump->validation_rules([
                'tipo'           => 'required|contains_list,' . TransacaoTipo::listValues(),
                'quantidade'     => 'required|numeric|min_numeric,0.0001',
                'preco'          => 'required|numeric|min_numeric,0',
                'taxas'          => 'numeric',
                'data_transacao' => 'required|date',
            ]);
            $gump->filter_rules([
                'tipo'          => 'trim|lower',
                'observacoes'   => 'trim',
            ]);
            $data = $gump->run($data);
            if ($data === false) {
                Response::validationError($gump->get_errors_array());
                return;
            }

            $tx = new TransacaoInvestimento();
            $tx->investimento_id = $investimentoId;
            $tx->tipo            = $data['tipo'];
            $tx->quantidade      = (float)$data['quantidade'];
            $tx->preco           = (float)$data['preco'];
            $tx->taxas           = isset($data['taxas']) && $data['taxas'] !== '' ? (float)$data['taxas'] : 0.0;
            $tx->data_transacao  = $data['data_transacao'];
            $tx->observacoes     = $data['observacoes'] ?: null;
            $tx->save();

            Response::success(['message' => 'Transação registrada com sucesso', 'id' => (int)$tx->id], 201);
        } catch (Throwable $e) {
            LogService::error('Falha ao criar transacao de investimento', ['user_id' => $uid, 'investimento_id' => $investimentoId, 'payload' => $this->request->all(), 'exception' => $e->getMessage()]);
            
            // Tratamento de erro específico (Ex: lançado pelo Model ao tentar vender mais do que tem)
            $msg = stripos($e->getMessage(), 'Quantidade indispon') !== false
                ? 'Quantidade indisponível para venda.'
                : 'Falha ao registrar transação.';
            Response::error($msg, 422);
        }
    }

    /**
     * Lista os proventos de um investimento.
     */
    public function proventos(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            if (!Investimento::where('user_id', $uid)->find($investimentoId)) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $items = Provento::where('investimento_id', $investimentoId)
                ->orderBy('data_pagamento', 'desc')
                ->orderBy('id', 'desc')
                ->get();

            Response::success($items);
        } catch (Throwable $e) {
            LogService::error('Falha ao listar proventos', ['user_id' => $uid, 'investimento_id' => $investimentoId, 'exception' => $e->getMessage()]);
            Response::error('Falha ao listar proventos', 500);
        }
    }

    /**
     * Cria um provento (Dividendo/JCP/Rendimento) para um investimento.
     */
    public function criarProvento(int $investimentoId): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            if (!Investimento::where('user_id', $uid)->find($investimentoId)) {
                Response::error('Investimento não encontrado', 404);
                return;
            }

            $gump = new GUMP();
            $data = $this->request->all();
            $this->normalizeNumerics($data, ['valor']);

            $gump->validation_rules([
                'valor'          => 'required|numeric|min_numeric,0.01',
                'tipo'           => 'required|contains_list,' . ProventoTipo::listValues(),
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
            $p->investimento_id = $investimentoId;
            $p->valor           = (float)$data['valor'];
            $p->tipo            = $data['tipo'];
            $p->data_pagamento  = $data['data_pagamento'];
            $p->observacoes     = $data['observacoes'] ?: null;
            $p->save();

            Response::success(['message' => 'Provento registrado com sucesso', 'id' => (int)$p->id], 201);
        } catch (Throwable $e) {
            LogService::error('Falha ao criar provento', ['user_id' => $uid, 'investimento_id' => $investimentoId, 'payload' => $this->request->all(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao registrar provento', 500);
        }
    }

    /**
     * Retorna estatísticas agregadas (total investido, lucro, rentabilidade) para todos os investimentos.
     */
    public function stats(): void
    {
        try {
            $this->requireAuth();
            $uid = $this->resolveUserId();
            if ($uid === null) {
                Response::error('Sessão expirada', 401);
                return;
            }

            /** @var \Illuminate\Support\Collection $itens */
            $itens = Investimento::where('user_id', $uid)->get();
            $totInvestido = 0.0;
            $totAtual     = 0.0;

            foreach ($itens as $i) {
                $metrics = $this->calculateInvestmentMetrics($i);
                $totInvestido += $metrics['valor_investido'];
                $totAtual     += $metrics['valor_atual'];
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
        } catch (Throwable $e) {
            LogService::error('Falha ao calcular stats investimentos', ['user_id' => $this->resolveUserId(), 'exception' => $e->getMessage()]);
            Response::error('Falha ao calcular estatísticas', 500);
        }
    }
}

