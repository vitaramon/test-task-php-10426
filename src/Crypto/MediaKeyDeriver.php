<?php

declare(strict_types=1);

namespace WhatsApp\Media\Crypto;

use WhatsApp\Media\Enum\MediaType;

final class MediaKeyDeriver
{

    /**
     * @param string $ikm
     * @param string $info
     * @param int $length
     * @return string
     */
    private static function hkdf(string $ikm, string $info, int $length = 112): string
    {
        $hashLen = 32; // SHA-256
        $prk = hash_hmac('sha256', $ikm, '', true); // salt = empty

        $okm = '';
        $t = '';
        $i = 1;

        while (strlen($okm) < $length) {
            $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
            $okm .= $t;
            $i++;
        }

        return substr($okm, 0, $length);
    }

    /**
     * @param string $mediaKey
     * @param MediaType $type
     * @return string
     */
    public static function expand(string $mediaKey, MediaType $type): string
    {
        if (strlen($mediaKey) !== 32) {
            throw new \InvalidArgumentException('MediaKey must be exactly 32 bytes');
        }

        $expanded = self::hkdf($mediaKey, $type->value, 112);

        if (strlen($expanded) !== 112) {
            throw new \RuntimeException('HKDF expansion failed');
        }

        return $expanded;
    }
}
