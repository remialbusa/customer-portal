<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use InvalidArgumentException;

/**
 * Value object for a signature captured on the TSR form.
 *
 * The form posts a base64 data URL like:
 *   data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA...
 *
 * We accept that as-is, validate it, decode it on demand, and let
 * SignatureStorage decide where to put the file.
 *
 * Allowed mime types: image/png (canvas pad), image/jpeg (camera
 * capture fallback). Other types are rejected at the validator.
 */
final readonly class SignatureBlob
{
    public function __construct(
        public string $name,
        public string $dataUrl,
        public ?string $email = null,
    ) {
    }

    /** @return array{name:string, signature:string, email_address?:string} */
    public function toArray(): array
    {
        $out = [
            'name'      => $this->name,
            'signature' => $this->dataUrl,
        ];
        if ($this->email !== null && $this->email !== '') {
            $out['email_address'] = $this->email;
        }
        return $out;
    }

    public function mimeType(): string
    {
        if (preg_match('#^data:([^;]+);base64,#', $this->dataUrl, $m) === 1) {
            return strtolower($m[1]);
        }
        throw new InvalidArgumentException('Signature is not a data URL.');
    }

    public function isValid(): bool
    {
        try {
            $mime = $this->mimeType();
        } catch (InvalidArgumentException) {
            return false;
        }
        if (! in_array($mime, ['image/png', 'image/jpeg'], true)) {
            return false;
        }
        $raw = $this->rawBytes();
        return $raw !== false && strlen($raw) > 200; // a blank pad is < 200 bytes
    }

    /** @return string|false  Raw image bytes, or false on bad base64. */
    public function rawBytes(): string|false
    {
        $comma = strpos($this->dataUrl, ',');
        if ($comma === false) {
            return false;
        }
        return base64_decode(substr($this->dataUrl, $comma + 1), true);
    }
}
