<?php

declare(strict_types=1);

namespace Application\Services\Fatura;

use Application\Models\CartaoCredito;
use Application\Models\Fatura;
use Application\Models\FaturaCartaoItem;
use Application\Services\Infrastructure\LogService;
use DateTime;
use Exception;
use Illuminate\Database\Capsule\Manager as DB;
use InvalidArgumentException;

class FaturaCreationService
{
    private const VALOR_MINIMO = 0.01;
    private const PARCELAS_MINIMAS = 1;
    private const PARCELAS_MAXIMAS = 120;

    public function __construct(
        private ?FaturaInstallmentCalculatorService $calculatorService = null
    ) {
        $this->calculatorService ??= new FaturaInstallmentCalculatorService();
    }

    public function criar(array $dados): ?int
    {
        DB::beginTransaction();

        try {
            $this->validarDadosCriacao($dados);

            $cartao = $this->buscarCartaoValidado($dados['cartao_credito_id'], $dados['user_id']);
            $fatura = $this->criarFatura($dados);
            $this->criarItensFatura($fatura, $dados, $cartao);

            DB::commit();

            LogService::info("Fatura criada com sucesso", [
                'fatura_id' => $fatura->id,
                'usuario_id' => $dados['user_id']
            ]);

            return $fatura->id;
        } catch (Exception $e) {
            DB::rollBack();

            LogService::error("Erro ao criar fatura", [
                'dados' => $dados,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    private function validarDadosCriacao(array $dados): void
    {
        $erros = [];

        if (empty($dados['user_id'])) {
            $erros[] = "Usuário não informado";
        }

        if (empty($dados['cartao_credito_id'])) {
            $erros[] = "Cartão não informado";
        }

        if (empty($dados['descricao']) || strlen(trim($dados['descricao'])) < 3) {
            $erros[] = "Descrição inválida (mínimo 3 caracteres)";
        }

        if (empty($dados['valor_total']) || $dados['valor_total'] < self::VALOR_MINIMO) {
            $erros[] = sprintf("Valor inválido (mínimo R$ %.2f)", self::VALOR_MINIMO);
        }

        if (
            empty($dados['numero_parcelas']) ||
            $dados['numero_parcelas'] < self::PARCELAS_MINIMAS ||
            $dados['numero_parcelas'] > self::PARCELAS_MAXIMAS
        ) {
            $erros[] = sprintf(
                "Número de parcelas inválido (entre %d e %d)",
                self::PARCELAS_MINIMAS,
                self::PARCELAS_MAXIMAS
            );
        }

        if (empty($dados['data_compra']) || !$this->validarData($dados['data_compra'])) {
            $erros[] = "Data da compra inválida";
        }

        if (!empty($erros)) {
            throw new InvalidArgumentException(implode("; ", $erros));
        }
    }

    private function validarData(string $data): bool
    {
        $parsedDate = DateTime::createFromFormat('Y-m-d', $data);

        return $parsedDate && $parsedDate->format('Y-m-d') === $data;
    }

    private function buscarCartaoValidado(int $cartaoId, int $usuarioId): CartaoCredito
    {
        $cartao = CartaoCredito::where('id', $cartaoId)
            ->where('user_id', $usuarioId)
            ->first();

        if (!$cartao) {
            throw new InvalidArgumentException("Cartão não encontrado ou não pertence ao usuário");
        }

        if (empty($cartao->dia_vencimento) || empty($cartao->dia_fechamento)) {
            throw new InvalidArgumentException("Cartão sem configuração de vencimento/fechamento");
        }

        return $cartao;
    }

    private function criarFatura(array $dados): Fatura
    {
        return Fatura::create([
            'user_id' => $dados['user_id'],
            'cartao_credito_id' => $dados['cartao_credito_id'],
            'descricao' => trim($dados['descricao']),
            'valor_total' => round((float) $dados['valor_total'], 2),
            'numero_parcelas' => (int) $dados['numero_parcelas'],
            'data_compra' => $dados['data_compra'],
            'status' => Fatura::STATUS_PENDENTE,
        ]);
    }

    private function criarItensFatura(Fatura $fatura, array $dados, CartaoCredito $cartao): void
    {
        $valorTotal = round((float) $dados['valor_total'], 2);
        $numeroParcelas = (int) $dados['numero_parcelas'];

        $valoresParcelas = $this->calculatorService->calcularValoresParcelas($valorTotal, $numeroParcelas);

        $dataCompra = new DateTime($dados['data_compra']);
        $diaCompra = (int) $dataCompra->format('d');
        $mesCompra = (int) $dataCompra->format('m');
        $anoCompra = (int) $dataCompra->format('Y');

        $itemPaiId = null;

        $competenciaBase = $this->calculatorService->calcularCompetenciaFatura(
            $diaCompra,
            $mesCompra,
            $anoCompra,
            $cartao->dia_fechamento
        );

        for ($parcelaAtual = 1; $parcelaAtual <= $numeroParcelas; $parcelaAtual++) {
            $vencimento = $this->calculatorService->calcularDataVencimento(
                $diaCompra,
                $mesCompra,
                $anoCompra,
                $parcelaAtual,
                $cartao->dia_vencimento,
                $cartao->dia_fechamento
            );

            $mesCompetencia = $competenciaBase['mes'] + ($parcelaAtual - 1);
            $anoCompetencia = $competenciaBase['ano'];
            while ($mesCompetencia > 12) {
                $mesCompetencia -= 12;
                $anoCompetencia++;
            }

            $item = FaturaCartaoItem::create([
                'user_id' => $dados['user_id'],
                'cartao_credito_id' => $dados['cartao_credito_id'],
                'fatura_id' => $fatura->id,
                'descricao' => trim($dados['descricao']),
                'valor' => $valoresParcelas[$parcelaAtual - 1],
                'data_compra' => $dados['data_compra'],
                'data_vencimento' => $vencimento['data'],
                'categoria_id' => $dados['categoria_id'] ?? null,
                'parcela_atual' => $parcelaAtual,
                'total_parcelas' => $numeroParcelas,
                'mes_referencia' => $mesCompetencia,
                'ano_referencia' => $anoCompetencia,
                'pago' => false,
                'item_pai_id' => $itemPaiId,
            ]);

            if ($parcelaAtual === 1) {
                $itemPaiId = $item->id;
            }
        }
    }
}
