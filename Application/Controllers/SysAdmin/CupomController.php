<?php

namespace Application\Controllers\SysAdmin;

use Application\Controllers\BaseController;
use Application\Core\Response;
use Application\Models\Cupom;
use Application\Models\CupomUsado;
use Application\Lib\Auth;
use Illuminate\Database\Capsule\Manager as DB;

class CupomController extends BaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Verifica se o usuário é admin
     */
    private function isAdmin(): bool
    {
        $user = Auth::user();
        return $user && $user->is_admin == 1;
    }

    /**
     * Obtém o corpo da requisição
     */
    private function getRequestBody(): array
    {
        return $this->getJson() ?? [];
    }

    /**
     * Atualiza cupons expirados para inativo
     */
    private function atualizarCuponsExpirados(): void
    {
        try {
            $hoje = date('Y-m-d');
            
            // Atualizar cupons que venceram (data menor que hoje e ainda ativos)
            Cupom::where('ativo', 1)
                ->whereNotNull('valido_ate')
                ->where('valido_ate', '<', $hoje)
                ->update(['ativo' => 0]);
                
            // Atualizar cupons que atingiram o limite de uso
            Cupom::where('ativo', 1)
                ->where('limite_uso', '>', 0)
                ->whereRaw('uso_atual >= limite_uso')
                ->update(['ativo' => 0]);
                
        } catch (\Exception $e) {
            error_log("Erro ao atualizar cupons expirados: " . $e->getMessage());
        }
    }

    /**
     * Lista todos os cupons
     */
    public function index(): void
    {
        $this->requireAuthApi();

        // Verificar se é admin
        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            // Atualizar cupons expirados antes de listar
            $this->atualizarCuponsExpirados();
            
            $cupons = Cupom::orderBy('created_at', 'desc')->get();

            $cuponsFormatados = $cupons->map(function ($cupom) {
                return [
                    'id' => $cupom->id,
                    'codigo' => $cupom->codigo,
                    'tipo_desconto' => $cupom->tipo_desconto,
                    'valor_desconto' => $cupom->valor_desconto,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'valido_ate' => $cupom->valido_ate ? $cupom->valido_ate->format('d/m/Y') : 'Sem limite',
                    'limite_uso' => $cupom->limite_uso,
                    'uso_atual' => $cupom->uso_atual,
                    'ativo' => $cupom->ativo,
                    'is_valid' => $cupom->isValid(),
                    'descricao' => $cupom->descricao,
                    'created_at' => $cupom->created_at->format('d/m/Y H:i')
                ];
            });

            Response::success(['cupons' => $cuponsFormatados]);
        } catch (\Exception $e) {
            error_log("Erro ao listar cupons: " . $e->getMessage());
            Response::error('Erro ao listar cupons: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cria um novo cupom
     */
    public function store(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $data = $this->getRequestBody();

            // Validações
            if (empty($data['codigo'])) {
                Response::error('Código do cupom é obrigatório', 400);
                return;
            }

            if (empty($data['tipo_desconto']) || !in_array($data['tipo_desconto'], ['percentual', 'fixo'])) {
                Response::error('Tipo de desconto inválido', 400);
                return;
            }

            if (!isset($data['valor_desconto']) || $data['valor_desconto'] <= 0) {
                Response::error('Valor do desconto deve ser maior que zero', 400);
                return;
            }

            // Validar percentual
            if ($data['tipo_desconto'] === 'percentual' && $data['valor_desconto'] > 100) {
                Response::error('Desconto percentual não pode ser maior que 100%', 400);
                return;
            }

            // Verificar se o código já existe
            $codigoExiste = Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper($data['codigo'])])->exists();
            if ($codigoExiste) {
                Response::error('Já existe um cupom com este código', 400);
                return;
            }

            // Criar cupom
            $cupom = new Cupom();
            $cupom->codigo = strtoupper(trim($data['codigo']));
            $cupom->tipo_desconto = $data['tipo_desconto'];
            $cupom->valor_desconto = $data['valor_desconto'];
            $cupom->valido_ate = !empty($data['valido_ate']) ? $data['valido_ate'] : null;
            $cupom->limite_uso = $data['limite_uso'] ?? 0;
            $cupom->uso_atual = 0;
            $cupom->ativo = isset($data['ativo']) ? (bool)$data['ativo'] : true;
            $cupom->descricao = $data['descricao'] ?? null;
            $cupom->save();

            Response::success([
                'message' => 'Cupom criado com sucesso!',
                'cupom' => [
                    'id' => $cupom->id,
                    'codigo' => $cupom->codigo,
                    'tipo_desconto' => $cupom->tipo_desconto,
                    'valor_desconto' => $cupom->valor_desconto,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'valido_ate' => $cupom->valido_ate ? $cupom->valido_ate->format('d/m/Y') : 'Sem limite',
                    'limite_uso' => $cupom->limite_uso,
                    'uso_atual' => $cupom->uso_atual,
                    'ativo' => $cupom->ativo,
                    'is_valid' => $cupom->isValid(),
                    'descricao' => $cupom->descricao,
                    'created_at' => $cupom->created_at->format('d/m/Y H:i')
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao criar cupom: " . $e->getMessage());
            Response::error('Erro ao criar cupom: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Exclui um cupom
     */
    public function destroy(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $data = $this->getRequestBody();

            if (empty($data['id'])) {
                Response::error('ID do cupom é obrigatório', 400);
                return;
            }

            $cupom = Cupom::find($data['id']);

            if (!$cupom) {
                Response::error('Cupom não encontrado', 404);
                return;
            }

            // Verificar se o cupom foi usado
            $foiUsado = CupomUsado::where('cupom_id', $cupom->id)->exists();

            if ($foiUsado) {
                // Se foi usado, apenas desativa
                $cupom->ativo = false;
                $cupom->save();
                Response::success(['message' => 'Cupom desativado com sucesso (não pode ser excluído pois já foi usado)']);
            } else {
                // Se não foi usado, pode excluir
                $cupom->delete();
                Response::success(['message' => 'Cupom excluído com sucesso!']);
            }
        } catch (\Exception $e) {
            error_log("Erro ao excluir cupom: " . $e->getMessage());
            Response::error('Erro ao excluir cupom: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Valida um cupom (usado no checkout)
     */
    public function validar(): void
    {
        $this->requireAuthApi();

        try {
            // Atualizar cupons expirados antes de validar
            $this->atualizarCuponsExpirados();
            
            $codigo = $_GET['codigo'] ?? '';

            if (empty($codigo)) {
                Response::error('Código do cupom é obrigatório', 400);
                return;
            }

            $cupom = Cupom::findByCodigo($codigo);

            if (!$cupom) {
                Response::error('Cupom não encontrado', 404);
                return;
            }

            // Verificar se o usuário já usou este cupom
            $user = Auth::user();
            $jaUsou = CupomUsado::where('cupom_id', $cupom->id)
                ->where('usuario_id', $user->id)
                ->exists();

            if ($jaUsou) {
                Response::error('Você já utilizou este cupom anteriormente', 400);
                return;
            }

            if (!$cupom->isValid()) {
                $motivo = 'Cupom inválido';

                if (!$cupom->ativo) {
                    $motivo = 'Cupom inativo';
                } elseif ($cupom->valido_ate && now() > $cupom->valido_ate) {
                    $motivo = 'Cupom expirado';
                } elseif ($cupom->limite_uso > 0 && $cupom->uso_atual >= $cupom->limite_uso) {
                    $motivo = 'Cupom esgotado';
                }

                Response::error($motivo, 400);
                return;
            }

            Response::success([
                'message' => 'Cupom válido!',
                'cupom' => [
                    'id' => $cupom->id,
                    'codigo' => $cupom->codigo,
                    'tipo_desconto' => $cupom->tipo_desconto,
                    'valor_desconto' => $cupom->valor_desconto,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'descricao' => $cupom->descricao
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao validar cupom: " . $e->getMessage());
            Response::error('Erro ao validar cupom: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza um cupom
     */
    public function update(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $data = $this->getRequestBody();

            if (empty($data['id'])) {
                Response::error('ID do cupom é obrigatório', 400);
                return;
            }

            $cupom = Cupom::find($data['id']);

            if (!$cupom) {
                Response::error('Cupom não encontrado', 404);
                return;
            }

            // Atualizar apenas campos permitidos
            if (isset($data['ativo'])) {
                $cupom->ativo = (bool)$data['ativo'];
            }

            if (isset($data['descricao'])) {
                $cupom->descricao = $data['descricao'];
            }

            if (isset($data['valido_ate'])) {
                $cupom->valido_ate = $data['valido_ate'];
            }

            if (isset($data['limite_uso'])) {
                $cupom->limite_uso = $data['limite_uso'];
            }

            $cupom->save();

            Response::success([
                'message' => 'Cupom atualizado com sucesso!',
                'cupom' => [
                    'id' => $cupom->id,
                    'codigo' => $cupom->codigo,
                    'tipo_desconto' => $cupom->tipo_desconto,
                    'valor_desconto' => $cupom->valor_desconto,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'valido_ate' => $cupom->valido_ate ? $cupom->valido_ate->format('d/m/Y') : 'Sem limite',
                    'limite_uso' => $cupom->limite_uso,
                    'uso_atual' => $cupom->uso_atual,
                    'ativo' => $cupom->ativo,
                    'is_valid' => $cupom->isValid(),
                    'descricao' => $cupom->descricao
                ]
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao atualizar cupom: " . $e->getMessage());
            Response::error('Erro ao atualizar cupom: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtém estatísticas de uso de um cupom
     */
    public function estatisticas(): void
    {
        $this->requireAuthApi();

        if (!$this->isAdmin()) {
            Response::error('Acesso negado', 403);
            return;
        }

        try {
            $cupomId = $_GET['id'] ?? null;

            if (!$cupomId) {
                Response::error('ID do cupom é obrigatório', 400);
                return;
            }

            $cupom = Cupom::find($cupomId);

            if (!$cupom) {
                Response::error('Cupom não encontrado', 404);
                return;
            }

            $usos = CupomUsado::where('cupom_id', $cupomId)
                ->with(['usuario'])
                ->orderBy('usado_em', 'desc')
                ->get();

            $totalDesconto = $usos->sum('desconto_aplicado');
            $totalValorOriginal = $usos->sum('valor_original');

            Response::success([
                'cupom' => [
                    'codigo' => $cupom->codigo,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'uso_atual' => $cupom->uso_atual,
                    'limite_uso' => $cupom->limite_uso
                ],
                'estatisticas' => [
                    'total_usos' => $usos->count(),
                    'total_desconto' => number_format($totalDesconto, 2, ',', '.'),
                    'total_valor_original' => number_format($totalValorOriginal, 2, ',', '.'),
                ],
                'usos' => $usos->map(function ($uso) {
                    return [
                        'usuario' => $uso->usuario->nome ?? 'Usuário removido',
                        'email' => $uso->usuario->email ?? '-',
                        'valor_original' => 'R$ ' . number_format($uso->valor_original, 2, ',', '.'),
                        'desconto_aplicado' => 'R$ ' . number_format($uso->desconto_aplicado, 2, ',', '.'),
                        'valor_final' => 'R$ ' . number_format($uso->valor_final, 2, ',', '.'),
                        'usado_em' => $uso->usado_em->format('d/m/Y H:i')
                    ];
                })
            ]);
        } catch (\Exception $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            Response::error('Erro ao buscar estatísticas: ' . $e->getMessage(), 500);
        }
    }
}
