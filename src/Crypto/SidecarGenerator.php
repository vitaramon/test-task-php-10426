<?php

declare(strict_types=1);

namespace WhatsAppCryptoDecorator\Crypto;

final class SidecarGenerator
{
    private const CHUNK_SIZE = 65536;
    private const OVERLAP    = 16;

    /**
     * @param string $encData
     * @param string $macKey
     * @return string
     */
    public static function generateFromEncrypted(string $encData, string $macKey): string
    {
        $sidecar = '';
        $pos = 0;
        $len = strlen($encData);

        while ($pos < $len) {
            $chunkEnd = min($pos + self::CHUNK_SIZE + self::OVERLAP, $len);
            $chunk    = substr($encData, $pos, $chunkEnd - $pos);

            $hmac = hash_hmac('sha256', $chunk, $macKey, true);
            $sidecar .= substr($hmac, 0, 10);

            $pos += self::CHUNK_SIZE;
        }

        return $sidecar;
    }
}
