<?php

declare(strict_types=1);

namespace Demo\Controller;

use Demo\Factory;
use Demo\Http\Response;
use Xakki\FileUploader\Protocol\ResponseFactory;

/**
 * Uniform download endpoint (filesystem or S3) — streams through the core storage,
 * forcing an attachment download with a correct Content-Length (the /dl route).
 */
final class DownloadController
{
    public function __construct(
        private readonly Factory $factory,
        private readonly Response $response,
    ) {}

    public function download(): never
    {
        $p = (string) ($_GET['p'] ?? '');
        // Confine downloads to the caller's own session folder — paths are
        // attacker-supplied (?p=), so a bare exists() check would leak other
        // guests' files across `uploads/<id>/` boundaries.
        if ($p === '' || ! str_starts_with($p, $this->factory->scopePrefix())) {
            $this->response->json(ResponseFactory::error('Not found'), 404);
        }
        $storage = $this->factory->storage();
        if (! $storage->exists($p)) {
            $this->response->json(ResponseFactory::error('Not found'), 404);
        }
        $stream = $storage->readStream($p);
        if (! $stream) {
            $this->response->json(ResponseFactory::error('Not found'), 404);
        }

        $this->response->download(
            $stream,
            basename($p),
            $storage->mimeType($p) ?: 'application/octet-stream',
            (int) ($storage->size($p) ?: 0),
        );
    }
}
