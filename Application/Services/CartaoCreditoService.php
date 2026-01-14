<?php

namespace Application\Services;

use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\DTO\CreateCartaoCreditoDTO;
use Application\DTO\UpdateCartaoCreditoDTO;
use Application\Validators\CartaoCreditoValidator;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class CartaoCreditoService
{
    public function __construct(
        private readonly CartaoCreditoValidator $validator = new CartaoCreditoValidator()
    ) {}

    /**
     * Listar cartões do usuário
     */
    public function listarCartoes(
        int $userId,
        ?int $contaId = null,
        bool $apenasAtivos = true
    ): array {
        $query = CartaoCredito::forUser($userId)->with('conta.instituicaoFinanceira');

        if ($contaId) {
            $query->daConta($contaId);
        }

        if ($apenasAtivos) {
            $query->ativos();
        }

        return $query->orderBy('nome_cartao')->get()->toArray();
    }

    /**
     * Buscar cartão por ID
     */
    public function buscarCartao(int $cartaoId, int $userId): ?array
    {
        $cartao = CartaoCredito::forUser($userId)
            ->with('conta.instituicaoFinanceira')
            ->find($cartaoId);

        return $cartao?->toArray();
    }

    /**
     * Criar novo cartão de crédito
     */
    public function criarCartao(CreateCartaoCreditoDTO $dto): array
    {
        $data = $dto->toArray();

        if (!$this->validator->validateCreate($data)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors(),
                'message' => $this->validator->getFirstError(),
            ];
        }

        // Validar que limite_disponivel não seja maior que limite_total
        $limiteTotal = (float) $data['limite_total'];
        $limiteDisponivel = (float) ($data['limite_disponivel'] ?? $limiteTotal);

        if ($limiteDisponivel > $limiteTotal) {
            return [
                'success' => false,
                'message' => 'Limite disponível não pode ser maior que o limite total.',
            ];
        }

        // Verificar se a conta pertence ao usuário
        $conta = Conta::forUser($dto->userId)->find($dto->contaId);
        if (!$conta) {
            return [
                'success' => false,
                'message' => 'Conta não encontrada ou não pertence ao usuário.',
            ];
        }

        DB::beginTransaction();
        try {
            $cartao = new CartaoCredito($data);
            $cartao->save();

            // Garantir que o limite_disponivel esteja sincronizado com
            // possíveis lançamentos já existentes vinculados ao cartão.
            $cartao->atualizarLimiteDisponivel();

            DB::commit();

            return [
                'success' => true,
                'data' => $cartao->fresh()->load('conta.instituicaoFinanceira')->toArray(),
                'id' => $cartao->id,
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            error_log('❌ Erro ao salvar cartão: ' . $e->getMessage());
            return [
                'message' => 'Erro ao criar cartão: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Atualizar cartão existente
     */
    public function atualizarCartao(int $cartaoId, int $userId, UpdateCartaoCreditoDTO $dto): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return [
                'success' => false,
                'message' => 'Cartão não encontrado.',
            ];
        }

        $data = $dto->toArray();

        if (!empty($data) && !$this->validator->validateUpdate($data)) {
            return [
                'success' => false,
                'errors' => $this->validator->getErrors(),
                'message' => $this->validator->getFirstError(),
            ];
        }

        // Validar limites se foram alterados
        if (isset($data['limite_total']) || isset($data['limite_disponivel'])) {
            $limiteTotal = (float) ($data['limite_total'] ?? $cartao->limite_total);
            $limiteDisponivel = (float) ($data['limite_disponivel'] ?? $cartao->limite_disponivel);

            if ($limiteDisponivel > $limiteTotal) {
                return [
                    'success' => false,
                    'message' => 'Limite disponível não pode ser maior que o limite total.',
                ];
            }
        }

        DB::beginTransaction();
        try {
            // Atualizar campos usando fill() ou atribuição direta
            $cartao->fill($data);

            // Se o limite total foi alterado, recalcular limite disponível
            if (isset($data['limite_total'])) {
                $cartao->atualizarLimiteDisponivel();
            }

            $cartao->save();

            DB::commit();

            return [
                'success' => true,
                'data' => $cartao->fresh()->load('conta.instituicaoFinanceira')->toArray(),
            ];
        } catch (Throwable $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Erro ao atualizar cartão: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Desativar cartão
     */
    public function desativarCartao(int $cartaoId, int $userId): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        $cartao->ativo = false;
        $cartao->save();

        return ['success' => true, 'message' => 'Cartão desativado com sucesso.'];
    }

    /**
     * Reativar cartão
     */
    public function reativarCartao(int $cartaoId, int $userId): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        $cartao->ativo = true;
        $cartao->save();

        return ['success' => true, 'message' => 'Cartão reativado com sucesso.'];
    }

    /**
     * Arquivar cartão (soft delete)
     */
    public function arquivarCartao(int $cartaoId, int $userId): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        $cartao->arquivado = true;
        $cartao->ativo = false;
        $cartao->save();

        return ['success' => true, 'message' => 'Cartão arquivado com sucesso.'];
    }

    /**
     * Restaurar cartão arquivado
     */
    public function restaurarCartao(int $cartaoId, int $userId): array
    {
        $cartao = CartaoCredito::where('user_id', $userId)
            ->where('id', $cartaoId)
            ->first();

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        $cartao->arquivado = false;
        $cartao->ativo = true;
        $cartao->save();

        return ['success' => true, 'message' => 'Cartão restaurado com sucesso.'];
    }

    /**
     * Listar cartões arquivados
     */
    public function listarCartoesArquivados(int $userId): array
    {
        return CartaoCredito::where('user_id', $userId)
            ->where('arquivado', true)
            ->with('conta.instituicaoFinanceira')
            ->orderBy('nome_cartao')
            ->get()
            ->toArray();
    }

    /**
     * Excluir cartão permanentemente (somente de cartões arquivados)
     */
    public function excluirCartaoPermanente(int $cartaoId, int $userId, bool $force = false): array
    {
        $cartao = CartaoCredito::where('user_id', $userId)
            ->where('id', $cartaoId)
            ->first();

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        // Verificar se há dados vinculados
        $totalLancamentos = $cartao->lancamentos()->count();
        $totalFaturas = DB::table('faturas')->where('cartao_credito_id', $cartaoId)->count();
        $totalItens = DB::table('faturas_cartao_itens')->where('cartao_credito_id', $cartaoId)->count();
        $totalVinculados = $totalLancamentos + $totalFaturas + $totalItens;

        if ($totalVinculados > 0 && !$force) {
            $mensagem = "Este cartão possui dados vinculados:\n";
            if ($totalLancamentos > 0) $mensagem .= "- {$totalLancamentos} lançamento(s)\n";
            if ($totalFaturas > 0) $mensagem .= "- {$totalFaturas} fatura(s)\n";
            if ($totalItens > 0) $mensagem .= "- {$totalItens} item(ns) de fatura\n";
            $mensagem .= "Confirme para excluir tudo.";

            return [
                'success' => false,
                'requires_confirmation' => true,
                'message' => $mensagem,
                'total_lancamentos' => $totalLancamentos,
                'total_faturas' => $totalFaturas,
                'total_itens' => $totalItens,
            ];
        }

        DB::transaction(function () use ($cartao, $cartaoId) {
            // Excluir itens de fatura vinculados
            DB::table('faturas_cartao_itens')->where('cartao_credito_id', $cartaoId)->delete();

            // Excluir faturas vinculadas
            DB::table('faturas')->where('cartao_credito_id', $cartaoId)->delete();

            // Excluir lançamentos vinculados
            $cartao->lancamentos()->delete();

            // Excluir cartão
            $cartao->delete();
        });

        return [
            'success' => true,
            'message' => 'Cartão e todos os dados vinculados foram excluídos permanentemente.',
            'deleted_lancamentos' => $totalLancamentos,
            'deleted_faturas' => $totalFaturas,
            'deleted_itens' => $totalItens,
        ];
    }

    /**
     * Excluir cartão (método antigo - mantido por compatibilidade)
     * @deprecated Use arquivarCartao() seguido de excluirCartaoPermanente()
     */
    public function excluirCartao(int $cartaoId, int $userId, bool $force = false): array
    {
        // Por segurança, redireciona para arquivar
        return $this->arquivarCartao($cartaoId, $userId);
    }

    /**
     * Atualizar limite disponível de um cartão
     */
    public function atualizarLimiteDisponivel(int $cartaoId, int $userId): array
    {
        $cartao = CartaoCredito::forUser($userId)->find($cartaoId);

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        try {
            $cartao->atualizarLimiteDisponivel();

            return [
                'success' => true,
                'limite_disponivel' => $cartao->limite_disponivel,
                'limite_utilizado' => $cartao->limite_utilizado,
                'percentual_uso' => $cartao->percentual_uso,
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'message' => 'Erro ao atualizar limite: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obter resumo de todos os cartões do usuário
     */
    public function obterResumo(int $userId): array
    {
        $cartoes = CartaoCredito::forUser($userId)->ativos()->get();

        $totalLimite = $cartoes->sum('limite_total');
        $totalDisponivel = $cartoes->sum('limite_disponivel');
        $totalUtilizado = $totalLimite - $totalDisponivel;
        $percentualUsoGeral = $totalLimite > 0
            ? round(($totalUtilizado / $totalLimite) * 100, 2)
            : 0;

        return [
            'total_cartoes' => $cartoes->count(),
            'limite_total' => $totalLimite,
            'limite_disponivel' => $totalDisponivel,
            'limite_utilizado' => $totalUtilizado,
            'percentual_uso' => $percentualUsoGeral,
            'cartoes' => $cartoes->toArray(),
        ];
    }

    /**
     * Verificar cartões com limite baixo (< 20%)
     */
    public function verificarLimitesBaixos(int $userId): array
    {
        try {
            $cartoes = CartaoCredito::forUser($userId)->ativos()->get();
            $alertas = [];

            foreach ($cartoes as $cartao) {
                try {
                    $percentualDisponivel = $cartao->limite_total > 0
                        ? ($cartao->limite_disponivel / $cartao->limite_total) * 100
                        : 0;

                    if ($percentualDisponivel < 20) {
                        $alertas[] = [
                            'cartao_id' => $cartao->id,
                            'nome_cartao' => $cartao->nome_cartao,
                            'limite_total' => (float) $cartao->limite_total,
                            'limite_disponivel' => (float) $cartao->limite_disponivel,
                            'percentual_disponivel' => round($percentualDisponivel, 2),
                            'tipo' => 'limite_baixo',
                            'gravidade' => $percentualDisponivel < 10 ? 'critico' : 'atencao',
                        ];
                    }
                } catch (\Exception $e) {
                    error_log("Erro ao verificar limite do cartão {$cartao->id}: " . $e->getMessage());
                    continue;
                }
            }

            return $alertas;
        } catch (\Exception $e) {
            error_log("Erro geral em verificarLimitesBaixos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validar integridade dos limites dos cartões
     * Verifica se limite_utilizado corresponde à soma de lançamentos não pagos
     */
    public function validarIntegridadeLimites(int $userId, bool $corrigirAutomaticamente = false): array
    {
        $cartoes = CartaoCredito::forUser($userId)->get();
        $relatorio = [
            'total_cartoes' => $cartoes->count(),
            'cartoes_ok' => 0,
            'cartoes_com_divergencia' => 0,
            'divergencias' => [],
            'corrigidos' => 0,
        ];

        foreach ($cartoes as $cartao) {
            // Calcula o limite utilizado real (soma de lançamentos não pagos)
            $limiteUtilizadoReal = \Application\Models\Lancamento::where('cartao_credito_id', $cartao->id)
                ->where('pago', false)
                ->where(function ($query) {
                    $query->where('eh_parcelado', false)
                        ->orWhere(function ($subQuery) {
                            $subQuery->where('eh_parcelado', true)
                                ->whereNotNull('parcela_atual');
                        });
                })
                ->sum('valor');

            $limiteUtilizadoAtual = $cartao->limite_total - $cartao->limite_disponivel;
            $diferenca = abs($limiteUtilizadoReal - $limiteUtilizadoAtual);

            // Considera divergente se a diferença for maior que 0.01 (1 centavo)
            if ($diferenca > 0.01) {
                $divergencia = [
                    'cartao_id' => $cartao->id,
                    'nome_cartao' => $cartao->nome_cartao,
                    'limite_total' => $cartao->limite_total,
                    'limite_disponivel_atual' => $cartao->limite_disponivel,
                    'limite_utilizado_registrado' => $limiteUtilizadoAtual,
                    'limite_utilizado_real' => $limiteUtilizadoReal,
                    'diferenca' => $diferenca,
                    'limite_disponivel_correto' => $cartao->limite_total - $limiteUtilizadoReal,
                ];

                $relatorio['divergencias'][] = $divergencia;
                $relatorio['cartoes_com_divergencia']++;

                // Corrigir automaticamente se solicitado
                if ($corrigirAutomaticamente) {
                    try {
                        $novoLimiteDisponivel = max(0, min($cartao->limite_total, $cartao->limite_total - $limiteUtilizadoReal));

                        $cartao->limite_disponivel = $novoLimiteDisponivel;
                        $cartao->save();

                        $relatorio['corrigidos']++;
                        $divergencia['corrigido'] = true;
                    } catch (\Exception $e) {
                        $divergencia['erro_correcao'] = $e->getMessage();
                    }
                }
            } else {
                $relatorio['cartoes_ok']++;
            }
        }

        return $relatorio;
    }
}
