<?php

declare(strict_types=1);

namespace WhatsAppCryptoDecorator\Exception;

final class WhatsAppMediaException extends \RuntimeException
{
    /**
     * @param string $message
     * @return self
     */
    public static function encryptionFailed(string $message): self
    {
        return new self('Encryption failed: ' . $message);
    }

    /**
     * @param string $message
     * @return self
     */
    public static function decryptionFailed(string $message): self
    {
        return new self('Decryption failed: ' . $message);
    }

    /**
     * @param string $message
     * @return self
     */
    public static function invalidSidecar(string $message): self
    {
        return new self('Invalid sidecar: ' . $message);
    }
}
