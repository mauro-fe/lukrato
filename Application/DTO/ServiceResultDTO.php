<?php

declare(strict_types=1);

namespace Application\DTO;

/**
 * Resultado padronizado de operações em services.
 * Substitui arrays associativos ['success' => bool, 'message' => string, ...] sem tipagem.
 */
readonly class ServiceResultDTO
{
    /**
     * @param bool        $success   Se a operação foi bem-sucedida
     * @param string      $message   Mensagem descritiva para o frontend
     * @param array       $data      Payload de dados (lancamento, usage, gamification, etc.)
     * @param int         $httpCode  Código HTTP sugerido para a resposta
     */
    public function __construct(
        public bool   $success,
        public string $message,
        public array  $data = [],
        public int    $httpCode = 200,
    ) {}

    // ─── Factories ──────────────────────────────────────────

    public static function ok(string $message, array $data = []): self
    {
        return new self(true, $message, $data, 201);
    }

    public static function fail(string $message, int $httpCode = 422): self
    {
        return new self(false, $message, [], $httpCode);
    }

    /**
     * Resultado de falha de validação com erros por campo.
     */
    public static function validationFail(array $errors): self
    {
        return new self(false, 'Erro de validação', ['errors' => $errors], 422);
    }

    // ─── Helpers ────────────────────────────────────────────

    public function isError(): bool
    {
        return !$this->success;
    }

    public function isValidationError(): bool
    {
        return !$this->success && isset($this->data['errors']);
    }

    /**
     * Retorna os dados com merge de informações extras (usage, gamification, etc.)
     */
    public function withExtra(array $extra): self
    {
        return new self(
            $this->success,
            $this->message,
            array_merge($this->data, $extra),
            $this->httpCode,
        );
    }

    /**
     * Converte para o formato de resposta legado (compatível com controllers existentes)
     */
    public function toArray(): array
    {
        return [
            'success'  => $this->success,
            'message'  => $this->message,
            'response' => $this->data,
            'code'     => $this->httpCode,
        ];
    }
}
