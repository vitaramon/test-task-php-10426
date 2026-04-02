<?php

declare(strict_types=1);

namespace WhatsAppCryptoDecorator\Stream;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use WhatsAppCryptoDecorator\Crypto\MediaCrypter;
use WhatsAppCryptoDecorator\Enum\MediaType;
use WhatsAppCryptoDecorator\Exception\WhatsAppMediaException;

final class DecryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;
    private string $mediaKey;
    private MediaType $type;
    private ?string $decryptedBuffer = null;

    public function __construct(StreamInterface $encryptedStream, string $mediaKey, MediaType $type)
    {
        $this->stream = $encryptedStream;
        $this->mediaKey = $mediaKey;
        $this->type = $type;
    }

    /**
     * @param $length
     * @return string
     */
    public function read($length): string
    {
        if ($this->decryptedBuffer === null) {
            $data = $this->stream->getContents();
            $this->decryptedBuffer = MediaCrypter::decrypt($data, $this->mediaKey, $this->type);
        }

        $out = substr($this->decryptedBuffer, 0, $length);
        $this->decryptedBuffer = substr($this->decryptedBuffer, $length);
        return $out;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->decryptedBuffer !== null ? strlen($this->decryptedBuffer) : null;
    }

    /**
     * @return bool
     */
    public function isSeekable(): bool
    {
        return false;
    }

    /**
     * @param $offset
     * @param $whence
     * @return void
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        throw WhatsAppMediaException::decryptionFailed('Stream is not seekable (MAC validation requires full read)');
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        throw WhatsAppMediaException::decryptionFailed('Stream is not seekable');
    }

    /**
     * @return int
     */
    public function tell(): int
    {
        throw WhatsAppMediaException::decryptionFailed('tell() not supported on non-seekable stream');
    }

    /**
     * @return bool
     */
    public function eof(): bool
    {
        return $this->decryptedBuffer === null || $this->decryptedBuffer === '';
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->stream->close();
        $this->decryptedBuffer = null;
    }

    /**
     * @return bool
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * @param $string
     * @return int
     */
    public function write($string): int
    {
        throw WhatsAppMediaException::decryptionFailed('Stream is read-only');
    }
}
