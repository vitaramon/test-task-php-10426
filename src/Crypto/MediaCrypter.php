<?php

declare(strict_types=1);

namespace WhatsApp\Media\Crypto;

use WhatsApp\Media\Enum\MediaType;

final class MediaCrypter
{
    public const MAC_LENGTH = 10;
    private const AES_METHOD = 'aes-256-cbc';

    /**
     * @param string $plain
     * @param string $mediaKey
     * @param MediaType $type
     * @return string
     */
    public static function encrypt(string $plain, string $mediaKey, MediaType $type): string
    {
        $expanded = MediaKeyDeriver::expand($mediaKey, $type);
        $iv        = substr($expanded, 0, 16);
        $cipherKey = substr($expanded, 16, 32);
        $macKey    = substr($expanded, 48, 32);

        $enc = openssl_encrypt($plain, self::AES_METHOD, $cipherKey, OPENSSL_RAW_DATA, $iv);
        if ($enc === false) {
            throw new \RuntimeException('AES encryption failed: ' . openssl_error_string());
        }

        $hmac = hash_hmac('sha256', $iv . $enc, $macKey, true);
        $mac  = substr($hmac, 0, self::MAC_LENGTH);

        return $enc . $mac;
    }

    /**
     * @param string $encrypted
     * @param string $mediaKey
     * @param MediaType $type
     * @return string
     */
    public static function decrypt(string $encrypted, string $mediaKey, MediaType $type): string
    {
        if (strlen($encrypted) < self::MAC_LENGTH) {
            throw new \InvalidArgumentException('Encrypted data too short');
        }

        $file = substr($encrypted, 0, -self::MAC_LENGTH);
        $mac  = substr($encrypted, -self::MAC_LENGTH);

        $expanded = MediaKeyDeriver::expand($mediaKey, $type);
        $iv        = substr($expanded, 0, 16);
        $cipherKey = substr($expanded, 16, 32);
        $macKey    = substr($expanded, 48, 32);

        $computed = hash_hmac('sha256', $iv . $file, $macKey, true);
        $computedTruncated = substr($computed, 0, self::MAC_LENGTH);

        if (!hash_equals($mac, $computedTruncated)) {
            throw new \RuntimeException('MAC validation failed - possible tampering');
        }

        $dec = openssl_decrypt($file, self::AES_METHOD, $cipherKey, OPENSSL_RAW_DATA, $iv);
        if ($dec === false) {
            throw new \RuntimeException('AES decryption failed: ' . openssl_error_string());
        }

        $pad = ord($dec[strlen($dec) - 1]);
        if ($pad < 1 || $pad > 16 || substr($dec, -$pad) !== str_repeat(chr($pad), $pad)) {
            throw new \RuntimeException('Invalid PKCS7 padding');
        }

        return substr($dec, 0, -$pad);
    }
}
