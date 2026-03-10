<?php

declare(strict_types=1);

namespace Application\Services\AI\Handlers;

use Application\DTO\AI\AIRequestDTO;
use Application\DTO\AI\AIResponseDTO;
use Application\Enums\AI\IntentType;
use Application\Models\PendingAiAction;
use Application\Repositories\ContaRepository;
use Application\Services\AI\Contracts\AIProvider;
use Application\Services\AI\IntentRules\EntityCreationIntentRule;
use Application\Validators\CategoriaValidator;
use Application\Validators\LancamentoValidator;
use Application\Validators\MetaValidator;
use Application\Validators\OrcamentoValidator;
use Application\Validators\SubcategoriaValidator;

/**
 * Handler para criação de entidades financeiras via IA.
 *
 * Pipeline: Regex extraction (0 tokens) → LLM fallback → Validação → PendingAiAction → Confirmação
 */
class EntityCreationHandler implements AIHandlerInterface
{
    private ?AIProvider $provider = null;

    public function setProvider(AIProvider $provider): void
    {
        $this->provider = $provider;
    }

    public function supports(IntentType $intent): bool
    {
        return $intent === IntentType::CREATE_ENTITY;
    }

    public function handle(AIRequestDTO $request): AIResponseDTO
    {
        $message = trim($request->message);
        $userId  = $request->userId;

        if (!$userId) {
            return AIResponseDTO::fail('Usuário não identificado.', IntentType::CREATE_ENTITY);
        }

        $entityType = EntityCreationIntentRule::detectEntityType($message);

        if (!$entityType) {
            return AIResponseDTO::fail(
                'Não consegui identificar o que você quer criar. Tente: "criar lançamento", "criar meta", "criar orçamento", "criar categoria" ou "criar subcategoria".',
                IntentType::CREATE_ENTITY
            );
        }

        // Extrair dados via regex primeiro (0 tokens)
        $extracted = $this->extractByRegex($message, $entityType);

        // Se faltam campos obrigatórios, tentar LLM
        $missing = $this->getMissingFields($extracted, $entityType);
        if (!empty($missing) && $this->provider) {
            $extracted = $this->extractWithAI($message, $entityType, $extracted);
            $missing = $this->getMissingFields($extracted, $entityType);
        }

        // Se ainda faltam campos obrigatórios, pedir ao usuário
        if (!empty($missing)) {
            $labels = $this->getFieldLabels($entityType);
            $missingLabels = array_map(fn($f) => $labels[$f] ?? $f, $missing);

            return AIResponseDTO::fromRule(
                "Para criar " . $this->getEntityLabel($entityType) . ", preciso que você informe: **" . implode('**, **', $missingLabels) . "**.\n\nTente algo como: " . $this->getExample($entityType),
                ['action' => 'missing_fields', 'missing' => $missing, 'entity_type' => $entityType],
                IntentType::CREATE_ENTITY
            );
        }

        // Validar com os validators (para lancamento, pular validação de conta_id — será adicionado na confirmação)
        $errors = $this->validate($extracted, $entityType, $userId);
        if ($entityType === 'lancamento') {
            unset($errors['conta_id']);
        }
        if (!empty($errors)) {
            $errorMessages = array_values($errors);
            return AIResponseDTO::fromRule(
                "⚠️ Encontrei alguns problemas:\n• " . implode("\n• ", $errorMessages) . "\n\nPor favor, corrija e tente novamente.",
                ['action' => 'validation_error', 'errors' => $errors, 'entity_type' => $entityType],
                IntentType::CREATE_ENTITY
            );
        }

        // Para lancamento, buscar contas do usuário
        $accountsList = [];
        if ($entityType === 'lancamento') {
            $contaRepo = new ContaRepository();
            $contas = $contaRepo->findActive($userId);

            if ($contas->isEmpty()) {
                return AIResponseDTO::fromRule(
                    '⚠️ Você precisa ter pelo menos uma conta cadastrada para criar lançamentos.',
                    ['action' => 'no_accounts'],
                    IntentType::CREATE_ENTITY
                );
            }

            if ($contas->count() === 1) {
                $extracted['conta_id'] = $contas->first()->id;
            }

            $accountsList = $contas->map(fn($c) => ['id' => $c->id, 'nome' => $c->nome])->values()->toArray();
        }

        // Criar PendingAiAction para confirmação
        $conversationId = $request->context['conversation_id'] ?? null;

        $pending = PendingAiAction::create([
            'user_id'         => $userId,
            'conversation_id' => $conversationId,
            'action_type'     => 'create_' . $entityType,
            'payload'         => $extracted,
            'status'          => 'awaiting_confirm',
            'expires_at'      => now()->addMinutes(10),
        ]);

        $preview = $this->formatPreview($extracted, $entityType);

        $responseData = [
            'action'      => 'confirm',
            'pending_id'  => $pending->id,
            'entity_type' => $entityType,
            'preview'     => $extracted,
        ];

        if (!empty($accountsList)) {
            $responseData['accounts'] = $accountsList;
        }

        return AIResponseDTO::fromRule(
            $preview . "\n\n**Deseja confirmar a criação?** Responda **sim** para confirmar ou **não** para cancelar.",
            $responseData,
            IntentType::CREATE_ENTITY
        );
    }

    // ─── Regex extractors ───────────────────────────────────────

    private function extractByRegex(string $message, string $entityType): array
    {
        return match ($entityType) {
            'lancamento'   => $this->extractLancamento($message),
            'meta'         => $this->extractMeta($message),
            'orcamento'    => $this->extractOrcamento($message),
            'categoria'    => $this->extractCategoria($message),
            'subcategoria' => $this->extractSubcategoria($message),
            default        => [],
        };
    }

    private function extractLancamento(string $message): array
    {
        $data = [];

        // tipo: receita ou despesa
        if (preg_match('/\b(receita|ganho|sal[áa]rio|renda|entrada)\b/iu', $message)) {
            $data['tipo'] = 'receita';
        } else {
            $data['tipo'] = 'despesa';
        }

        // valor: R$ 100, 100 reais, 1.500,00, etc.
        if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)\s*(?:reais)?/iu', $message, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['valor'] = (float) $valor;
        }

        // data: hoje, amanhã, DD/MM, DD/MM/YYYY
        if (preg_match('/\bhoje\b/iu', $message)) {
            $data['data'] = date('Y-m-d');
        } elseif (preg_match('/\bamanh[ãa]\b/iu', $message)) {
            $data['data'] = date('Y-m-d', strtotime('+1 day'));
        } elseif (preg_match('/\bontem\b/iu', $message)) {
            $data['data'] = date('Y-m-d', strtotime('-1 day'));
        } elseif (preg_match('/(\d{1,2})\s*[\/\-]\s*(\d{1,2})(?:\s*[\/\-]\s*(\d{2,4}))?/u', $message, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = str_pad($m[2], 2, '0', STR_PAD_LEFT);
            $year = isset($m[3]) ? (strlen($m[3]) === 2 ? '20' . $m[3] : $m[3]) : date('Y');
            $data['data'] = "{$year}-{$month}-{$day}";
        } else {
            $data['data'] = date('Y-m-d');
        }

        // descricao: tenta extrair "de <descricao>" ou texto após valor
        if (preg_match('/\b(?:de|do|da|para|com|no|na)\s+(.{3,60})$/iu', $message, $m)) {
            $desc = trim($m[1]);
            // Remover partes que são data ou valor
            $desc = preg_replace('/\b(?:hoje|amanh[ãa]|ontem|\d{1,2}\/\d{1,2}(?:\/\d{2,4})?|R?\$?\s*[\d.,]+\s*reais?)\b/iu', '', $desc);
            $desc = trim($desc, " \t\n\r\0\x0B,.");
            if (mb_strlen($desc) >= 3) {
                $data['descricao'] = mb_substr($desc, 0, 190);
            }
        }

        return $data;
    }

    private function extractMeta(string $message): array
    {
        $data = [];

        // valor: R$ 5000, 5000 reais, etc.
        if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)\s*(?:reais)?/iu', $message, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['valor_alvo'] = (float) $valor;
        }

        // titulo: "meta de <titulo>", "meta para <titulo>"
        if (preg_match('/meta\s+(?:de|para|pra)\s+(.{3,100})/iu', $message, $m)) {
            $titulo = trim($m[1]);
            // Limpar valor do título
            $titulo = preg_replace('/\b(?:R?\$?\s*[\d.,]+\s*(?:reais)?)\b/iu', '', $titulo);
            $titulo = preg_replace('/\b(?:de|no valor|com valor|at[eé])\s*$/iu', '', $titulo);
            $titulo = trim($titulo, " \t\n\r\0\x0B,.");
            if (mb_strlen($titulo) >= 2) {
                $data['titulo'] = mb_substr($titulo, 0, 150);
            }
        }

        return $data;
    }

    private function extractOrcamento(string $message): array
    {
        $data = [];

        // valor_limite
        if (preg_match('/R?\$?\s*(\d{1,3}(?:\.\d{3})*[,\.]\d{2}|\d+(?:[,\.]\d{1,2})?)\s*(?:reais)?/iu', $message, $m)) {
            $valor = str_replace('.', '', $m[1]);
            $valor = str_replace(',', '.', $valor);
            $data['valor_limite'] = (float) $valor;
        }

        // mes e ano
        $meses = [
            'janeiro' => 1,
            'fevereiro' => 2,
            'mar[çc]o' => 3,
            'abril' => 4,
            'maio' => 5,
            'junho' => 6,
            'julho' => 7,
            'agosto' => 8,
            'setembro' => 9,
            'outubro' => 10,
            'novembro' => 11,
            'dezembro' => 12,
        ];
        foreach ($meses as $pattern => $num) {
            if (preg_match('/\b' . $pattern . '\b/iu', $message)) {
                $data['mes'] = $num;
                break;
            }
        }
        if (!isset($data['mes'])) {
            $data['mes'] = (int) date('m');
        }

        if (preg_match('/\b(20\d{2})\b/', $message, $m)) {
            $data['ano'] = (int) $m[1];
        } else {
            $data['ano'] = (int) date('Y');
        }

        return $data;
    }

    private function extractCategoria(string $message): array
    {
        $data = [];

        // tipo
        if (preg_match('/\b(receita|despesa|transferencia|transfer[eê]ncia|ambas)\b/iu', $message, $m)) {
            $tipo = mb_strtolower($m[1]);
            $tipo = str_replace(['transferência', 'transferencia'], 'transferencia', $tipo);
            $data['tipo'] = $tipo;
        }

        // nome: "categoria <nome>", after tipo
        if (preg_match('/categoria\s+(.{2,60})/iu', $message, $m)) {
            $nome = trim($m[1]);
            // Limpar tipo e outras keywords
            $nome = preg_replace('/\b(?:tipo|de\s+(?:receita|despesa|transferencia|ambas))\b/iu', '', $nome);
            $nome = preg_replace('/\b(?:receita|despesa|transferencia|ambas)\b/iu', '', $nome);
            $nome = trim($nome, " \t\n\r\0\x0B,.");
            if (mb_strlen($nome) >= 2) {
                $data['nome'] = mb_substr($nome, 0, 100);
            }
        }

        return $data;
    }

    private function extractSubcategoria(string $message): array
    {
        $data = [];

        // nome da subcategoria
        if (preg_match('/sub[\s-]?categoria\s+(.{2,60})/iu', $message, $m)) {
            $nome = trim($m[1]);
            $nome = preg_replace('/\b(?:em|na|no|para|da|do)\s+.+$/iu', '', $nome);
            $nome = trim($nome, " \t\n\r\0\x0B,.");
            if (mb_strlen($nome) >= 2) {
                $data['nome'] = mb_substr($nome, 0, 100);
            }
        }

        return $data;
    }

    // ─── LLM fallback ───────────────────────────────────────────

    private function extractWithAI(string $message, string $entityType, array $partial): array
    {
        try {
            $prompt = $this->buildExtractionPrompt($message, $entityType, $partial);
            $response = $this->provider->chat($prompt, [], [
                'temperature' => 0.1,
                'max_tokens'  => 300,
            ]);

            $json = $this->parseJsonResponse($response);
            if ($json === null) {
                return $partial;
            }

            // Merge: regex has priority (already extracted)
            return array_merge($json, $partial);
        } catch (\Throwable) {
            return $partial;
        }
    }

    private function buildExtractionPrompt(string $message, string $entityType, array $partial): string
    {
        $fields = match ($entityType) {
            'lancamento'   => 'tipo (receita/despesa), data (YYYY-MM-DD), valor (number), descricao (string)',
            'meta'         => 'titulo (string), valor_alvo (number)',
            'orcamento'    => 'categoria_id (number), valor_limite (number), mes (1-12), ano (YYYY)',
            'categoria'    => 'nome (string), tipo (receita/despesa/transferencia/ambas)',
            'subcategoria' => 'nome (string)',
            default => '',
        };

        $already = !empty($partial) ? 'Já extraído: ' . json_encode($partial, JSON_UNESCAPED_UNICODE) . '. ' : '';

        return "Extraia os campos de criação de {$entityType} da mensagem do usuário. " .
            "Campos esperados: {$fields}. {$already}" .
            "Retorne APENAS um JSON com os campos encontrados, sem explicação. " .
            "Se não conseguir extrair um campo, omita-o do JSON.\n\n" .
            "Mensagem: \"{$message}\"";
    }

    private function parseJsonResponse(string $response): ?array
    {
        if (preg_match('/\{[^}]+\}/s', $response, $match)) {
            $data = json_decode($match[0], true);
            return is_array($data) ? $data : null;
        }
        return null;
    }

    // ─── Validation ─────────────────────────────────────────────

    private function getMissingFields(array $data, string $entityType): array
    {
        $required = match ($entityType) {
            'lancamento'   => ['tipo', 'data', 'valor'],
            'meta'         => ['titulo', 'valor_alvo'],
            'orcamento'    => ['valor_limite'],
            'categoria'    => ['nome', 'tipo'],
            'subcategoria' => ['nome'],
            default        => [],
        };

        $missing = [];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }

        return $missing;
    }

    private function validate(array $data, string $entityType, int $userId): array
    {
        return match ($entityType) {
            'lancamento'   => LancamentoValidator::validateCreate($data),
            'meta'         => MetaValidator::validateCreate($data),
            'orcamento'    => $this->validateOrcamento($data),
            'categoria'    => CategoriaValidator::validateCreate($data),
            'subcategoria' => SubcategoriaValidator::validateCreate($data),
            default        => [],
        };
    }

    private function validateOrcamento(array $data): array
    {
        $errors = OrcamentoValidator::validateSave($data);
        $monthErrors = OrcamentoValidator::validateMonth($data);
        return array_merge($errors, $monthErrors);
    }

    // ─── Preview / Labels ───────────────────────────────────────

    private function formatPreview(array $data, string $entityType): string
    {
        return match ($entityType) {
            'lancamento'   => $this->previewLancamento($data),
            'meta'         => $this->previewMeta($data),
            'orcamento'    => $this->previewOrcamento($data),
            'categoria'    => $this->previewCategoria($data),
            'subcategoria' => $this->previewSubcategoria($data),
            default        => '📋 Entidade a ser criada.',
        };
    }

    private function previewLancamento(array $d): string
    {
        $icon = ($d['tipo'] ?? 'despesa') === 'receita' ? '💰' : '💸';
        $tipo = ucfirst($d['tipo'] ?? 'despesa');
        $valor = 'R$ ' . number_format((float) ($d['valor'] ?? 0), 2, ',', '.');
        $desc = $d['descricao'] ?? 'Sem descrição';
        $dataFormatted = isset($d['data']) ? date('d/m/Y', strtotime($d['data'])) : date('d/m/Y');

        return "{$icon} **{$tipo}**: {$desc}\n📅 Data: {$dataFormatted}\n💵 Valor: {$valor}";
    }

    private function previewMeta(array $d): string
    {
        $valor = 'R$ ' . number_format((float) ($d['valor_alvo'] ?? 0), 2, ',', '.');
        return "🎯 **Meta**: {$d['titulo']}\n💵 Valor alvo: {$valor}";
    }

    private function previewOrcamento(array $d): string
    {
        $valor = 'R$ ' . number_format((float) ($d['valor_limite'] ?? 0), 2, ',', '.');
        $mes = str_pad((string) ($d['mes'] ?? date('m')), 2, '0', STR_PAD_LEFT);
        $ano = $d['ano'] ?? date('Y');
        return "📊 **Orçamento**: {$valor}\n📅 Período: {$mes}/{$ano}";
    }

    private function previewCategoria(array $d): string
    {
        $tipo = ucfirst($d['tipo'] ?? '');
        return "📁 **Categoria**: {$d['nome']}\n🏷️ Tipo: {$tipo}";
    }

    private function previewSubcategoria(array $d): string
    {
        return "📂 **Subcategoria**: {$d['nome']}";
    }

    private function getEntityLabel(string $entityType): string
    {
        return match ($entityType) {
            'lancamento'   => 'um lançamento',
            'meta'         => 'uma meta',
            'orcamento'    => 'um orçamento',
            'categoria'    => 'uma categoria',
            'subcategoria' => 'uma subcategoria',
            default        => 'uma entidade',
        };
    }

    private function getFieldLabels(string $entityType): array
    {
        return match ($entityType) {
            'lancamento' => [
                'tipo' => 'Tipo (receita/despesa)',
                'data' => 'Data',
                'valor' => 'Valor',
                'descricao' => 'Descrição',
            ],
            'meta' => [
                'titulo' => 'Título',
                'valor_alvo' => 'Valor alvo',
            ],
            'orcamento' => [
                'categoria_id' => 'Categoria',
                'valor_limite' => 'Valor limite',
            ],
            'categoria' => [
                'nome' => 'Nome',
                'tipo' => 'Tipo (receita/despesa/transferencia/ambas)',
            ],
            'subcategoria' => [
                'nome' => 'Nome',
            ],
            default => [],
        };
    }

    private function getExample(string $entityType): string
    {
        return match ($entityType) {
            'lancamento'   => '"criar despesa de R$ 150 de conta de luz hoje"',
            'meta'         => '"criar meta de viagem de R$ 5.000"',
            'orcamento'    => '"criar orçamento de R$ 800 para alimentação"',
            'categoria'    => '"criar categoria Pets tipo despesa"',
            'subcategoria' => '"criar subcategoria Ração"',
            default        => '',
        };
    }
}
