<?php

declare(strict_types=1);

namespace Application\Services\Infrastructure;

use Application\Config\SecurityRuntimeConfig;
use Application\Container\ApplicationContainer;

class CpfProtectionService
{
    private const CIPHER = 'aes-256-gcm';
    private SecurityRuntimeConfig $runtimeConfig;

    public function __construct(?SecurityRuntimeConfig $runtimeConfig = null)
    {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, SecurityRuntimeConfig::class);
    }

    public function normalize(string $cpf): string
    {
        return preg_replace('/\D+/', '', $cpf) ?? '';
    }

    public function hash(string $cpf): string
    {
        return hash('sha256', $this->normalize($cpf));
    }

    public function encrypt(string $value): string
    {
        $normalized = $this->normalize($value);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);

        if ($ivLength === false || $ivLength < 1) {
            throw new \RuntimeException('Cipher inválido para criptografia de CPF.');
        }

        $iv = random_bytes($ivLength);
        $tag = '';
        $encrypted = openssl_encrypt(
            $normalized,
            self::CIPHER,
            $this->resolveKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new \RuntimeException('Falha ao criptografar CPF.');
        }

        return base64_encode(json_encode([
            'v' => 1,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'value' => base64_encode($encrypted),
        ], JSON_THROW_ON_ERROR));
    }

    public function decrypt(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            throw new \RuntimeException('Payload criptografado de CPF inválido.');
        }

        $payload = json_decode($decoded, true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Estrutura criptografada de CPF inválida.');
        }

        $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
        $tag = base64_decode((string) ($payload['tag'] ?? ''), true);
        $ciphertext = base64_decode((string) ($payload['value'] ?? ''), true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new \RuntimeException('Blocos criptografados de CPF inválidos.');
        }

        $decrypted = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->resolveKey(),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new \RuntimeException('Falha ao descriptografar CPF.');
        }

        return $this->normalize($decrypted);
    }

    private function resolveKey(): string
    {
        $key = $this->runtimeConfig->cpfEncryptionKey();

        $key = trim((string) $key);
        if ($key === '') {
            throw new \RuntimeException('CPF_ENCRYPTION_KEY ou APP_KEY deve estar configurada.');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded === false || $decoded === '') {
                throw new \RuntimeException('Chave de criptografia CPF inválida.');
            }

            return hash('sha256', $decoded, true);
        }

        return hash('sha256', $key, true);
    }
}