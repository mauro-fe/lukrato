<?php

declare(strict_types=1);

namespace Application\Services\AI\WhatsApp;

use Application\Config\WhatsAppRuntimeConfig;
use Application\Container\ApplicationContainer;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Baixa anexos do WhatsApp Cloud API a partir do media id.
 */
class WhatsAppMediaDownloader
{
    private const MAX_FILE_SIZE = 25 * 1024 * 1024; // 25MB

    private WhatsAppMediaHttpClient $http;
    private string $token;
    private WhatsAppRuntimeConfig $runtimeConfig;

    public function __construct(?WhatsAppMediaHttpClient $http = null, ?WhatsAppRuntimeConfig $runtimeConfig = null)
    {
        $this->runtimeConfig = ApplicationContainer::resolveOrNew($runtimeConfig, WhatsAppRuntimeConfig::class);
        $this->token = $this->runtimeConfig->token();
        $this->http = ApplicationContainer::resolveOrNew($http, WhatsAppMediaHttpClient::class);
    }

    /**
     * @return array{content:string,mime_type:?string,file_size:?int,filename:?string}|null
     */
    public function downloadByMediaId(string $mediaId, ?string $filename = null): ?array
    {
        if ($this->token === '') {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[WhatsAppMediaDownloader] WHATSAPP_TOKEN nao configurado.');
            return null;
        }

        try {
            $metaResponse = $this->http->get($mediaId);
            $meta = json_decode($metaResponse->getBody()->getContents(), true);
            $url = $meta['url'] ?? null;

            if (!is_string($url) || $url === '') {
                \Application\Services\Infrastructure\LogService::safeErrorLog('[WhatsAppMediaDownloader] URL de media ausente para ' . $mediaId);
                return null;
            }

            $downloadResponse = $this->http->get($url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                ],
            ]);

            $content = $downloadResponse->getBody()->getContents();
            if (strlen($content) > self::MAX_FILE_SIZE) {
                \Application\Services\Infrastructure\LogService::safeErrorLog('[WhatsAppMediaDownloader] Arquivo excede limite de 25MB.');
                return null;
            }

            return [
                'content' => $content,
                'mime_type' => $meta['mime_type'] ?? null,
                'file_size' => isset($meta['file_size']) ? (int) $meta['file_size'] : null,
                'filename' => $filename,
            ];
        } catch (GuzzleException $e) {
            \Application\Services\Infrastructure\LogService::safeErrorLog('[WhatsAppMediaDownloader] Erro ao baixar media: ' . $e->getMessage());
            return null;
        }
    }
}
