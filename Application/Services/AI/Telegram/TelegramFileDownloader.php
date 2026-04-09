<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

use Application\Config\TelegramRuntimeConfig;
use Application\Container\ApplicationContainer;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Faz download de arquivos (áudio, fotos) via Telegram Bot API.
 *
 * Fluxo:
 *  1. getFile(file_id) → retorna file_path
 *  2. downloadFile(file_path) → retorna conteúdo binário
 *
 * Env vars:
 *  TELEGRAM_BOT_TOKEN=...
 */
class TelegramFileDownloader
{
    private const MAX_FILE_SIZE = 20 * 1024 * 1024; // 20MB (limite Telegram Bot API)

    private Client $http;
    private string $token;
    private TelegramRuntimeConfig $runtimeConfig;

    public function __construct(?Client $http = null, ?TelegramRuntimeConfig $runtimeConfig = null)
    {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, TelegramRuntimeConfig::class);
        $this->token = $this->runtimeConfig->botToken();
        $this->http = ApplicationContainer::resolveOrNew($http, TelegramFileHttpClient::class);
    }

    /**
     * Obtém o file_path de um arquivo no Telegram via getFile.
     */
    public function getFilePath(string $fileId): ?string
    {
        if ($this->token === '') {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[TelegramFileDownloader] TELEGRAM_BOT_TOKEN não configurado.');
            return null;
        }

        try {
            $response = $this->http->post(
                "https://api.telegram.org/bot{$this->token}/getFile",
                ['json' => ['file_id' => $fileId]]
            );

            $result = json_decode($response->getBody()->getContents(), true);

            if (!($result['ok'] ?? false)) {
                \Application\Services\Infrastructure\LogService::safeErrorLog('[TelegramFileDownloader] getFile falhou: ' . json_encode($result));
                return null;
            }

            return $result['result']['file_path'] ?? null;
        } catch (GuzzleException $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[TelegramFileDownloader] Erro em getFile: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Baixa o conteúdo binário de um arquivo do Telegram.
     */
    public function downloadFile(string $filePath): ?string
    {
        if ($this->token === '') {
            return null;
        }

        try {
            $url = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
            $response = $this->http->get($url);

            $content = $response->getBody()->getContents();

            if (strlen($content) > self::MAX_FILE_SIZE) {
                \Application\Services\Infrastructure\LogService::safeErrorLog('[TelegramFileDownloader] Arquivo excede limite de 20MB.');
                return null;
            }

            return $content;
        } catch (GuzzleException $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[TelegramFileDownloader] Erro ao baixar arquivo: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Conveniência: obtém file_path e baixa conteúdo em uma chamada.
     *
     * @return array{content: string, file_path: string, extension: string, filename: string}|null
     */
    public function downloadByFileId(string $fileId): ?array
    {
        $filePath = $this->getFilePath($fileId);
        if ($filePath === null) {
            return null;
        }

        $content = $this->downloadFile($filePath);
        if ($content === null) {
            return null;
        }

        $extension = pathinfo($filePath, PATHINFO_EXTENSION) ?: 'bin';

        return [
            'content'   => $content,
            'file_path' => $filePath,
            'extension' => strtolower($extension),
            'filename'  => basename($filePath),
        ];
    }
}
