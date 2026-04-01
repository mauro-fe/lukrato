<?php

declare(strict_types=1);

namespace Application\Services\Cartao;

use Application\Enums\LogCategory;
use Application\Models\CartaoCredito;
use Application\Services\Infrastructure\LogService;
use Application\Services\User\OnboardingProgressService;
use Illuminate\Database\Capsule\Manager as DB;
use Throwable;

class CartaoLifecycleService
{
    public function __construct(
        private OnboardingProgressService $onboardingProgressService
    ) {}

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

    public function listarCartoesArquivados(int $userId): array
    {
        return CartaoCredito::where('user_id', $userId)
            ->where('arquivado', true)
            ->with('conta.instituicaoFinanceira')
            ->orderBy('nome_cartao')
            ->get()
            ->toArray();
    }

    public function excluirCartaoPermanente(int $cartaoId, int $userId, bool $force = false): array
    {
        $cartao = CartaoCredito::where('user_id', $userId)
            ->where('id', $cartaoId)
            ->first();

        if (!$cartao) {
            return ['success' => false, 'message' => 'Cartão não encontrado.'];
        }

        $totalLancamentos = $cartao->lancamentos()->count();
        $totalFaturas = DB::table('faturas')->where('cartao_credito_id', $cartaoId)->count();
        $totalItens = DB::table('faturas_cartao_itens')->where('cartao_credito_id', $cartaoId)->count();
        $totalVinculados = $totalLancamentos + $totalFaturas + $totalItens;

        if ($totalVinculados > 0 && !$force) {
            $mensagem = "Este cartão possui dados vinculados:\n";
            if ($totalLancamentos > 0) {
                $mensagem .= "- {$totalLancamentos} lançamento(s)\n";
            }
            if ($totalFaturas > 0) {
                $mensagem .= "- {$totalFaturas} fatura(s)\n";
            }
            if ($totalItens > 0) {
                $mensagem .= "- {$totalItens} item(ns) de fatura\n";
            }
            $mensagem .= 'Confirme para excluir tudo.';

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
            DB::table('faturas_cartao_itens')->where('cartao_credito_id', $cartaoId)->delete();
            DB::table('faturas')->where('cartao_credito_id', $cartaoId)->delete();
            $cartao->lancamentos()->delete();
            $cartao->delete();
        });

        $this->syncOnboardingStateAfterDeletion($userId);

        return [
            'success' => true,
            'message' => 'Cartão e todos os dados vinculados foram excluídos permanentemente.',
            'deleted_lancamentos' => $totalLancamentos,
            'deleted_faturas' => $totalFaturas,
            'deleted_itens' => $totalItens,
        ];
    }

    private function syncOnboardingStateAfterDeletion(int $userId): void
    {
        try {
            $this->onboardingProgressService->resyncState($userId);
        } catch (Throwable $e) {
            LogService::captureException($e, LogCategory::GENERAL, [
                'action' => 'sync_onboarding_after_cartao_delete',
                'user_id' => $userId,
            ]);
        }
    }
}
