<?php

declare(strict_types=1);

namespace WhatsApp\Media\Crypto;

use WhatsApp\Media\Enum\MediaType;

final class MediaKeyDeriver
{

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

        $expanded = hash_hkdf('sha256', $mediaKey, 112, $type->value, '');

        if (strlen($expanded) !== 112) {
            throw new \RuntimeException('HKDF expansion failed');
        }

        return $expanded;
    }
}
