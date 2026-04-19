<?php

declare(strict_types=1);

namespace Application\Services\Admin;

use Application\Enums\LogCategory;
use Application\Models\AssinaturaUsuario;
use Application\Models\Cupom;
use Application\Models\CupomUsado;
use Application\Models\Usuario;
use Application\Services\Infrastructure\LogService;
use Throwable;

class CupomAdminWorkflowService
{
    /**
     * @return array<string, mixed>
     */
    public function listCoupons(): array
    {
        try {
            $this->atualizarCuponsExpirados();
            $cupons = Cupom::orderBy('created_at', 'desc')->get();

            return $this->success([
                'cupons' => $cupons->map(fn(Cupom $cupom): array => $this->formatCoupon($cupom, true)),
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao listar cupons.', [
                'action' => 'cupom_admin_list',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createCoupon(array $data): array
    {
        try {
            if (empty($data['codigo'])) {
                return $this->failure('Código do cupom é obrigatório', 400);
            }

            if (empty($data['tipo_desconto']) || !in_array($data['tipo_desconto'], ['percentual', 'fixo'], true)) {
                return $this->failure('Tipo de desconto inválido', 400);
            }

            if (!isset($data['valor_desconto']) || $data['valor_desconto'] <= 0) {
                return $this->failure('Valor do desconto deve ser maior que zero', 400);
            }

            if ($data['tipo_desconto'] === 'percentual' && $data['valor_desconto'] > 100) {
                return $this->failure('Desconto percentual não pode ser maior que 100%', 400);
            }

            $codigoExiste = Cupom::whereRaw('UPPER(codigo) = ?', [strtoupper((string) $data['codigo'])])->exists();
            if ($codigoExiste) {
                return $this->failure('Já existe um cupom com este código', 400);
            }

            $cupom = new Cupom();
            $cupom->codigo = strtoupper(trim((string) $data['codigo']));
            $cupom->tipo_desconto = $data['tipo_desconto'];
            $cupom->valor_desconto = $data['valor_desconto'];
            $cupom->valido_ate = $this->resolveValidade($data);
            $cupom->limite_uso = $data['limite_uso'] ?? 0;
            $cupom->uso_atual = 0;
            $cupom->ativo = isset($data['ativo']) ? (bool) $data['ativo'] : true;
            $cupom->apenas_primeira_assinatura = isset($data['apenas_primeira_assinatura']) ? (bool) $data['apenas_primeira_assinatura'] : true;
            $cupom->permite_reativacao = isset($data['permite_reativacao']) ? (bool) $data['permite_reativacao'] : false;
            $cupom->meses_inatividade_reativacao = isset($data['meses_inatividade_reativacao']) ? (int) $data['meses_inatividade_reativacao'] : 3;
            $cupom->descricao = $data['descricao'] ?? null;
            $cupom->save();

            return $this->success([
                'message' => 'Cupom criado com sucesso!',
                'cupom' => $this->formatCoupon($cupom, true),
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao criar cupom.', [
                'action' => 'cupom_admin_create',
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function deleteCoupon(array $data): array
    {
        try {
            if (empty($data['id'])) {
                return $this->failure('ID do cupom é obrigatório', 400);
            }

            $cupom = Cupom::find($data['id']);
            if (!$cupom) {
                return $this->failure('Cupom não encontrado', 404);
            }

            $foiUsado = CupomUsado::where('cupom_id', $cupom->id)->exists();

            if ($foiUsado) {
                $cupom->ativo = false;
                $cupom->save();

                return $this->success([
                    'message' => 'Cupom desativado com sucesso (não pode ser excluído pois já foi usado)',
                ]);
            }

            $cupom->delete();

            return $this->success([
                'message' => 'Cupom excluído com sucesso!',
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao excluir cupom.', [
                'action' => 'cupom_admin_delete',
                'cupom_id' => $data['id'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function validateCoupon(Usuario $user, ?string $codigo): array
    {
        try {
            $this->atualizarCuponsExpirados();
            $codigo = trim((string) ($codigo ?? ''));

            if ($codigo === '') {
                return $this->failure('Código do cupom é obrigatório', 400);
            }

            $cupom = Cupom::findByCodigo($codigo);
            if (!$cupom) {
                return $this->failure('Cupom não encontrado', 404);
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

                return $this->failure($motivo, 400);
            }

            $jaUsou = CupomUsado::where('cupom_id', $cupom->id)
                ->where('usuario_id', $user->id)
                ->exists();

            if ($jaUsou) {
                return $this->failure('Você já utilizou este cupom anteriormente', 400);
            }

            $elegibilidadeErro = $this->verificarElegibilidade($user, $cupom);
            if ($elegibilidadeErro !== null) {
                return $this->failure($elegibilidadeErro, 400);
            }

            return $this->success([
                'message' => 'Cupom válido!',
                'cupom' => [
                    'id' => $cupom->id,
                    'codigo' => $cupom->codigo,
                    'tipo_desconto' => $cupom->tipo_desconto,
                    'valor_desconto' => $cupom->valor_desconto,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'descricao' => $cupom->descricao,
                ],
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao validar cupom.', [
                'action' => 'cupom_admin_validate',
                'user_id' => $user->id ?? null,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateCoupon(array $data): array
    {
        try {
            if (empty($data['id'])) {
                return $this->failure('ID do cupom é obrigatório', 400);
            }

            $cupom = Cupom::find($data['id']);
            if (!$cupom) {
                return $this->failure('Cupom não encontrado', 404);
            }

            if (isset($data['ativo'])) {
                $cupom->ativo = (bool) $data['ativo'];
            }

            if (isset($data['descricao'])) {
                $cupom->descricao = $data['descricao'];
            }

            if (array_key_exists('valido_ate', $data)) {
                $cupom->valido_ate = $this->resolveValidade($data);
            }

            if (isset($data['limite_uso'])) {
                $cupom->limite_uso = $data['limite_uso'];
            }

            $cupom->save();

            return $this->success([
                'message' => 'Cupom atualizado com sucesso!',
                'cupom' => $this->formatCoupon($cupom, false),
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao atualizar cupom.', [
                'action' => 'cupom_admin_update',
                'cupom_id' => $data['id'] ?? null,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function getStatistics(mixed $cupomId): array
    {
        try {
            if (!$cupomId) {
                return $this->failure('ID do cupom é obrigatório', 400);
            }

            $cupom = Cupom::find($cupomId);
            if (!$cupom) {
                return $this->failure('Cupom não encontrado', 404);
            }

            $usos = CupomUsado::where('cupom_id', $cupomId)
                ->with(['usuario'])
                ->orderBy('usado_em', 'desc')
                ->get();

            $totalDesconto = $usos->sum('desconto_aplicado');
            $totalValorOriginal = $usos->sum('valor_original');

            return $this->success([
                'cupom' => [
                    'codigo' => $cupom->codigo,
                    'desconto_formatado' => $cupom->getDescontoFormatado(),
                    'uso_atual' => $cupom->uso_atual,
                    'limite_uso' => $cupom->limite_uso,
                ],
                'estatisticas' => [
                    'total_usos' => $usos->count(),
                    'total_desconto' => number_format($totalDesconto, 2, ',', '.'),
                    'total_valor_original' => number_format($totalValorOriginal, 2, ',', '.'),
                ],
                'usos' => $usos->map(function ($uso): array {
                    return [
                        'usuario' => $uso->usuario->nome ?? 'Usuário removido',
                        'email' => $uso->usuario->email ?? '-',
                        'valor_original' => 'R$ ' . number_format($uso->valor_original, 2, ',', '.'),
                        'desconto_aplicado' => 'R$ ' . number_format($uso->desconto_aplicado, 2, ',', '.'),
                        'valor_final' => 'R$ ' . number_format($uso->valor_final, 2, ',', '.'),
                        'usado_em' => $uso->usado_em->format('d/m/Y H:i'),
                    ];
                }),
            ]);
        } catch (Throwable $e) {
            return $this->internalFailure($e, 'Erro ao buscar estatísticas do cupom.', [
                'action' => 'cupom_admin_statistics',
                'cupom_id' => $cupomId,
            ]);
        }
    }

    private function verificarElegibilidade(Usuario $user, Cupom $cupom): ?string
    {
        $assinaturasEfetivas = AssinaturaUsuario::where('user_id', $user->id)
            ->where('gateway', 'asaas')
            ->whereIn('status', [
                AssinaturaUsuario::ST_ACTIVE,
                AssinaturaUsuario::ST_CANCELED,
                AssinaturaUsuario::ST_EXPIRED,
                AssinaturaUsuario::ST_PAST_DUE,
                AssinaturaUsuario::ST_PAUSED,
            ]);

        if (!$assinaturasEfetivas->exists()) {
            return null;
        }

        if ($cupom->permite_reativacao ?? false) {
            $mesesInatividade = $cupom->meses_inatividade_reativacao ?? 3;

            $temAtiva = AssinaturaUsuario::where('user_id', $user->id)
                ->where('gateway', 'asaas')
                ->where('status', AssinaturaUsuario::ST_ACTIVE)
                ->exists();

            if ($temAtiva) {
                return 'Você já possui uma assinatura ativa.';
            }

            $ultimaAssinatura = AssinaturaUsuario::where('user_id', $user->id)
                ->where('gateway', 'asaas')
                ->whereIn('status', [AssinaturaUsuario::ST_CANCELED, AssinaturaUsuario::ST_EXPIRED])
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($ultimaAssinatura) {
                $dataRef = $ultimaAssinatura->cancelada_em ?? $ultimaAssinatura->updated_at;
                $mesesDesde = now()->diffInMonths($dataRef);

                if ($mesesDesde >= $mesesInatividade) {
                    return null;
                }

                return "Este cupom é válido para ex-assinantes inativos há pelo menos {$mesesInatividade} meses.";
            }
        }

        return 'Este cupom é válido apenas para a primeira assinatura.';
    }

    private function atualizarCuponsExpirados(): void
    {
        try {
            $agora = date('Y-m-d H:i:s');

            Cupom::where('ativo', 1)
                ->whereNotNull('valido_ate')
                ->where('valido_ate', '<', $agora)
                ->update(['ativo' => 0]);

            Cupom::where('ativo', 1)
                ->where('limite_uso', '>', 0)
                ->whereRaw('uso_atual >= limite_uso')
                ->update(['ativo' => 0]);
        } catch (Throwable $e) {
            LogService::safeErrorLog('Erro ao atualizar cupons expirados: ' . $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function success(array $data): array
    {
        return [
            'success' => true,
            'data' => $data,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failure(string $message, int $status, mixed $errors = null): array
    {
        $result = [
            'success' => false,
            'status' => $status,
            'message' => $message,
        ];

        if ($errors !== null) {
            $result['errors'] = $errors;
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    private function internalFailure(Throwable $e, string $publicMessage, array $context = []): array
    {
        $errorId = LogService::reportException(
            e: $e,
            publicMessage: $publicMessage,
            context: $context,
            category: LogCategory::GENERAL
        );

        return $this->failure($publicMessage, 500, [
            'error_id' => $errorId,
            'request_id' => LogService::currentRequestId(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatCoupon(Cupom $cupom, bool $includeCreatedAt): array
    {
        $data = [
            'id' => $cupom->id,
            'codigo' => $cupom->codigo,
            'tipo_desconto' => $cupom->tipo_desconto,
            'valor_desconto' => $cupom->valor_desconto,
            'desconto_formatado' => $cupom->getDescontoFormatado(),
            'valido_ate' => $cupom->valido_ate ? $cupom->valido_ate->format('d/m/Y H:i') : 'Sem limite',
            'limite_uso' => $cupom->limite_uso,
            'uso_atual' => $cupom->uso_atual,
            'ativo' => $cupom->ativo,
            'apenas_primeira_assinatura' => $cupom->apenas_primeira_assinatura ?? true,
            'is_valid' => $cupom->isValid(),
            'descricao' => $cupom->descricao,
        ];

        if ($includeCreatedAt) {
            $data['created_at'] = $cupom->created_at->format('d/m/Y H:i');
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function resolveValidade(array $data): ?string
    {
        if (empty($data['valido_ate'])) {
            return null;
        }

        $hora = $data['hora_valido_ate'] ?? '23:59';

        return $data['valido_ate'] . ' ' . $hora . ':59';
    }
}
