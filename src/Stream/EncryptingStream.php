<?php

declare(strict_types=1);

namespace WhatsApp\Media;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;
use WhatsApp\Media\Crypto\MediaCrypter;
use WhatsApp\Media\Crypto\MediaKeyDeriver;
use WhatsApp\Media\Enum\MediaType;
use WhatsApp\Media\Crypto\SidecarGenerator;
use WhatsApp\Media\Exception\WhatsAppMediaException;

final class EncryptingStream implements StreamInterface
{
    use StreamDecoratorTrait;

    private StreamInterface $stream;
    private string $mediaKey;
    private MediaType $type;
    private ?string $encryptedBuffer = null;
    private ?string $sidecar = null;

    public function __construct(StreamInterface $stream, string $mediaKey, MediaType $type)
    {
        $this->stream = $stream;
        $this->mediaKey = $mediaKey;
        $this->type = $type;
    }

    /**
     * @param $length
     * @return string
     */
    public function read($length): string
    {
        if ($this->encryptedBuffer === null) {
            $plain = $this->stream->getContents();
            $this->encryptedBuffer = MediaCrypter::encrypt($plain, $this->mediaKey, $this->type);

            if (in_array($this->type, [MediaType::VIDEO, MediaType::AUDIO], true)) {
                $expanded = MediaKeyDeriver::expand($this->mediaKey, $this->type);
                $macKey = substr($expanded, 48, 32);
                $this->sidecar = SidecarGenerator::generateFromEncrypted($this->encryptedBuffer, $macKey);
            }
        }

        $data = substr($this->encryptedBuffer, 0, $length);
        $this->encryptedBuffer = substr($this->encryptedBuffer, $length);
        return $data;
    }

    /**
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->encryptedBuffer !== null ? strlen($this->encryptedBuffer) : null;
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
        throw WhatsAppMediaException::encryptionFailed('Stream is not seekable (CBC + MAC requires full read)');
    }

    /**
     * @return void
     */
    public function rewind(): void
    {
        throw WhatsAppMediaException::encryptionFailed('Stream is not seekable');
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
        throw WhatsAppMediaException::encryptionFailed('Stream is read-only');
    }

    /**
     * @return string|null
     */
    public function getSidecar(): ?string
    {
        return $this->sidecar;
    }
}
