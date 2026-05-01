<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\CartaoCredito;
use Application\Models\Conta;
use Application\Models\FaturaCartaoItem;
use Application\Models\PendingAiAction;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\Infrastructure\LogService;
use Application\Services\AI\NLP\CardNameExtractor;
use Application\Services\AI\NLP\PeriodExtractor;
use Carbon\Carbon;

/**
 * Handler para pagamento de fatura de cartão de crédito via chat.
 *
 * Fluxo: Extrai cartão/mês → Preview → PendingAiAction → ConfirmationHandler → PayFaturaAction
 */
class PayFaturaHandler implements AIHandlerInterface
{
    public function setProvider(AIProvider $provider): void
    {
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::PAY_FATURA;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $message = mb_strtolower(trim($request->message));
        $userId  = $request->userId;

        if (!$userId) {
            return AIResponseDTO::fail('Usuário não identificado.', IntentType::PAY_FATURA);
        }

        try {
            // 1. Extrair nome do cartão e período
            $nomeCartao = $this->extractCartaoName($message);
            $period = $this->extractPeriod($message);
            [$month, $year] = $period ?? [(int) now()->month, (int) now()->year];
            $label = $period !== null
                ? Carbon::createFromDate($year, $month, 1)->translatedFormat('F/Y')
                : now()->translatedFormat('F/Y');

            // 2. Buscar cartão(ões) do usuário
            $cartoes = CartaoCredito::where('user_id', $userId)
                ->where('ativo', true)
                ->get(['id', 'nome_cartao', 'conta_id', 'dia_vencimento']);

            if ($cartoes->isEmpty()) {
                return AIResponseDTO::fromRule(
                    '💳 Você não tem nenhum cartão de crédito cadastrado.',
                    ['action' => 'no_cards'],
                    IntentType::PAY_FATURA
                );
            }

            // 3. Resolver qual cartão
            $cartao = null;
            if ($nomeCartao !== null) {
                $cartao = $cartoes->first(fn($c) => stripos($c->nome_cartao, $nomeCartao) !== false);
            }

            if ($cartao === null && $cartoes->count() === 1) {
                $cartao = $cartoes->first();
            }

            if ($cartao === null) {
                // Múltiplos cartões e nenhum especificado — listar
                $lista = $cartoes->map(fn($c) => "• {$c->nome_cartao}")->implode("\n");
                return AIResponseDTO::fromRule(
                    "💳 Qual cartão você quer pagar? Diga: \"pagar fatura do {nome do cartão}\"\n\n{$lista}",
                    ['action' => 'choose_card', 'cards' => $cartoes->pluck('nome_cartao')->toArray()],
                    IntentType::PAY_FATURA
                );
            }

            // 4. Buscar itens pendentes da fatura
            $itensPendentes = FaturaCartaoItem::forUser($userId)
                ->where('cartao_credito_id', $cartao->id)
                ->doMesAno($month, $year)
                ->whereNull('cancelado_em')
                ->pendentes()
                ->get(['id', 'valor']);

            if ($itensPendentes->isEmpty()) {
                return AIResponseDTO::fromRule(
                    "✅ A fatura do **{$cartao->nome_cartao}** de {$label} já está toda paga!",
                    ['action' => 'already_paid', 'cartao' => $cartao->nome_cartao],
                    IntentType::PAY_FATURA
                );
            }

            $totalPendente = (float) $itensPendentes->sum('valor');
            $countPendente = $itensPendentes->count();
            $fmtTotal = 'R$ ' . number_format($totalPendente, 2, ',', '.');

            // 5. Resolver conta para débito
            $contaId = $cartao->conta_id;
            $contaNome = 'padrão';
            if ($contaId) {
                $conta = Conta::find($contaId);
                if ($conta) {
                    $contaNome = $conta->nome;
                } else {
                    $contaId = null;
                }
            }

            if (!$contaId) {
                // Tentar auto-preencher com primeira conta ativa do usuário
                $contaPadrao = Conta::where('user_id', $userId)
                    ->where('ativo', true)
                    ->first();
                if ($contaPadrao) {
                    $contaId = $contaPadrao->id;
                    $contaNome = $contaPadrao->nome;
                } else {
                    return AIResponseDTO::fromRule(
                        '⚠️ Seu cartão **' . $cartao->nome_cartao . '** não tem uma conta vinculada e você não possui contas ativas. Cadastre uma conta primeiro.',
                        ['action' => 'no_account'],
                        IntentType::PAY_FATURA
                    );
                }
            }

            // 6. Criar PendingAiAction
            $pending = PendingAiAction::create([
                'user_id'         => $userId,
                'conversation_id' => $request->context['conversation_id'] ?? null,
                'action_type'     => 'pay_fatura',
                'payload'         => [
                    'cartao_id'   => $cartao->id,
                    'cartao_nome' => $cartao->nome_cartao,
                    'mes'         => $month,
                    'ano'         => $year,
                    'valor'       => $totalPendente,
                    'conta_id'    => $contaId,
                    'conta_nome'  => $contaNome,
                ],
                'status'     => 'awaiting_confirm',
                'expires_at' => now()->addMinutes(10),
            ]);

            // 7. Preview
            $msg = "💳 Pagar fatura do **{$cartao->nome_cartao}** de {$label}: **{$fmtTotal}** ({$countPendente} itens pendentes).";
            if ($contaId) {
                $msg .= "\nA conta **{$contaNome}** será debitada.";
            }
            $msg .= "\n\n**Confirma?** Responda **sim** ou **não**.";

            return AIResponseDTO::fromRule(
                $msg,
                [
                    'action'     => 'confirm',
                    'pending_id' => $pending->id,
                    'cartao'     => $cartao->nome_cartao,
                    'valor'      => $totalPendente,
                    'period'     => $label,
                ],
                IntentType::PAY_FATURA
            );
        } catch (\Throwable $e) {
            LogService::warning('PayFaturaHandler.handle', ['error' => $e->getMessage()]);

            return AIResponseDTO::fail(
                'Erro ao processar pagamento de fatura. Tente novamente.',
                IntentType::PAY_FATURA
            );
        }
    }

    // ─── Helpers ─────────────────────────────────────────────

    private function extractCartaoName(string $message): ?string
    {
        return CardNameExtractor::extract($message);
    }

    private function extractPeriod(string $message): ?array
    {
        return PeriodExtractor::extract($message);
    }
}
