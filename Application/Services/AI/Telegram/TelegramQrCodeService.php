<?php

declare(strict_types=1);

namespace Application\Services\AI\Telegram;

/**
 * Gera QR code SVG para links do Telegram sem depender de servico externo.
 */
class TelegramQrCodeService
{
    private const DEFAULT_MODULE_SIZE = 6;

    /**
     * Retorna um data URI SVG pronto para uso em <img src="...">.
     */
    public static function makeDataUri(string $url, int $moduleSize = self::DEFAULT_MODULE_SIZE): string
    {
        $barcode = new \TCPDF2DBarcode($url, 'QRCODE,H');
        $svg = $barcode->getBarcodeSVGcode($moduleSize, $moduleSize, '#0f172a');

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
}
