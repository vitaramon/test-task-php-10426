<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Psr7\Utils;
use WhatsApp\Media\Crypto\MediaKeyDeriver;
use WhatsApp\Media\DecryptingStream;
use WhatsApp\Media\EncryptingStream;
use WhatsApp\Media\Enum\MediaType;
use WhatsApp\Media\Crypto\SidecarGenerator;

echo "Basic usage example for sample files" . PHP_EOL;

$types = [
    MediaType::IMAGE,
    MediaType::VIDEO,
    MediaType::AUDIO,
    MediaType::DOCUMENT,
];

foreach ($types as $type) {
    $name = $type->name;
    $keyPath = "samples/{$name}.key";
    $originalPath = "samples/{$name}.original";
    $encryptedPath = "samples/{$name}.encrypted";

    $mediaKey = file_get_contents($keyPath);

    $decStream = new DecryptingStream(
        Utils::streamFor(fopen($encryptedPath, 'rb')),
        $mediaKey,
        $type
    );
    $decrypted = $decStream->getContents();
    file_put_contents("{$name}.decrypted", $decrypted);
    echo "[success] {$name} — decryption OK (" . strlen($decrypted) . " bytes)" . PHP_EOL;

    $plainStream = Utils::streamFor(fopen($originalPath, 'rb'));
    $encStream = new EncryptingStream($plainStream, $mediaKey, $type);
    $newEncrypted = $encStream->getContents();

    if ($newEncrypted === file_get_contents($encryptedPath)) {
        echo "[success] {$name} — roundtrip (encrypt → decrypt) 100% match" . PHP_EOL;
    } else {
        echo "[problem] {$name} — roundtrip (encrypt → decrypt) doesn't match provided file" . PHP_EOL;
    }

    if (in_array($type, [MediaType::VIDEO, MediaType::AUDIO], true)) {
        $sidecar = SidecarGenerator::generateFromEncrypted(
            file_get_contents($encryptedPath),
            MediaKeyDeriver::expand($mediaKey, $type)
        );
        file_put_contents("{$name}.sidecar.generated", $sidecar);

        $providedSidecar = file_get_contents("samples/{$name}.sidecar") ?? '';
        if (strlen($sidecar) === strlen($providedSidecar)) {
            echo "[success] {$name} — sidecar generated and match provided length" . PHP_EOL;
        } else {
            echo "[problem]  {$name} — sidecar generated, but not match provided length (" . strlen($sidecar) . " bytes)" . PHP_EOL;
        }
    };
}
