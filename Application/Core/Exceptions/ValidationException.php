<?php

namespace Application\Core\Exceptions;

/**
 * Exceção customizada para falhas de validação.
 * Carrega um array de erros.
 */
class ValidationException extends \Exception
{
    /**
     * Construtor da Exceção de Validação.
     *
     * @param public readonly array $errors O array de mensagens de erro (ex: ['email' => 'E-mail inválido']).
     * @param string $message Mensagem geral da exceção.
     * @param int $code O código de status HTTP (padrão 422).
     * @param \Throwable|null $previous A exceção anterior (para encadeamento).
     */
    public function __construct(
        public readonly array $errors, // Propriedade promovida (PHP 8.1+)
        string $message = 'Validation failed',
        int $code = 422, // 422 é mais específico para validação que 400
        \Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Retorna os erros de validação.
     * (Nota: Este método é opcional se a propriedade $errors for 'public readonly',
     * mas é mantido por convenção se você preferir encapsulamento).
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}