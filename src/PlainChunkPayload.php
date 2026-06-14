<?php

declare(strict_types=1);

namespace Demo;

use RuntimeException;
use Xakki\FileUploader\Contracts\ChunkPayload;

/**
 * ChunkPayload built from plain PHP superglobals ($_POST + $_FILES). Shows that the
 * framework-agnostic core needs nothing more than a stream + scalars per chunk.
 */
final class PlainChunkPayload implements ChunkPayload
{
    /**
     * @param  array<string, mixed>  $post
     * @param  array<string, mixed>  $file  one entry of $_FILES
     */
    public function __construct(
        private array $post,
        private array $file,
    ) {}

    public function uploadId(): string
    {
        return (string) ($this->post['uploadId'] ?? '');
    }

    public function chunkIndex(): int
    {
        return (int) ($this->post['chunkIndex'] ?? 0);
    }

    public function totalChunks(): int
    {
        return (int) ($this->post['totalChunks'] ?? 1);
    }

    public function fileName(): string
    {
        return (string) ($this->post['fileName'] ?? 'file');
    }

    public function fileSize(): int
    {
        return (int) ($this->post['fileSize'] ?? 0);
    }

    public function mimeType(): string
    {
        return (string) ($this->post['mimeType'] ?? 'application/octet-stream');
    }

    public function fileLastModified(): int
    {
        return (int) ($this->post['fileLastModified'] ?? 0);
    }

    public function fileHash(): ?string
    {
        $hash = $this->post['fileHash'] ?? null;

        return $hash !== null && $hash !== '' ? (string) $hash : null;
    }

    public function locale(): ?string
    {
        $locale = $this->post['locale'] ?? null;

        return $locale !== null && $locale !== '' ? (string) $locale : null;
    }

    public function detectedMimeType(): ?string
    {
        $tmp = $this->file['tmp_name'] ?? null;
        if (! is_string($tmp) || $tmp === '') {
            return null;
        }
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $mime = finfo_file($finfo, $tmp);
        // No finfo_close(): on PHP 8 finfo is an auto-freed object and the call is
        // deprecated on 8.5 — with display_errors it would corrupt the chunk JSON.

        return $mime ?: null;
    }

    public function chunkStream()
    {
        $tmp = $this->file['tmp_name'] ?? null;
        $stream = is_string($tmp) ? fopen($tmp, 'rb') : false;
        if ($stream === false) {
            throw new RuntimeException('Unable to open uploaded chunk stream.');
        }

        return $stream;
    }
}
