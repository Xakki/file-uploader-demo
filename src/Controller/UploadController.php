<?php

declare(strict_types=1);

namespace Demo\Controller;

use Demo\Factory;
use Demo\Http\Response;
use Demo\PlainChunkPayload;
use Xakki\FileUploader\Dto\FileMetadata;
use Xakki\FileUploader\FileManager;
use Xakki\FileUploader\Protocol\ResponseFactory;

/**
 * Upload Protocol v1 API: chunk intake, listing, trash cleanup, restore, delete.
 * The core FileManager is built lazily on first use so page routes stay free of
 * any storage/logger side effects.
 */
final class UploadController
{
    private ?FileManager $manager = null;

    public function __construct(
        private readonly Factory $factory,
        private readonly Response $response,
    ) {}

    public function chunks(): never
    {
        $payload = new PlainChunkPayload($_POST, $_FILES['fileChunk'] ?? []);
        if (! preg_match('/^upload-[0-9]{13}-[a-z0-9]{8}$/', $payload->uploadId())) {
            $this->response->json(ResponseFactory::error('Validation failed', ['uploadId' => ['Invalid uploadId format.']]), 422);
        }
        $result = $this->manager()->handleChunk($payload);
        $completed = $result instanceof FileMetadata;
        $data = ['completed' => $completed];
        if ($completed) {
            $data['metadata'] = $this->manager()->formatFileForResponse($result);
        }
        $this->response->json(ResponseFactory::success($data, $completed ? 'Upload finished' : 'Chunk received'));
    }

    public function list(): never
    {
        $this->response->json(ResponseFactory::success(['files' => $this->manager()->list()], 'ok'));
    }

    public function cleanup(): never
    {
        $this->response->json(ResponseFactory::success(['count' => $this->manager()->cleanupTrash()], 'cleanup done'));
    }

    public function restore(string $id): never
    {
        if (! $this->manager()->restore($id)) {
            $this->response->json(ResponseFactory::error('Not found'), 404);
        }
        $this->response->json(ResponseFactory::success(['id' => $id], 'restored'));
    }

    public function delete(string $id): never
    {
        if (! $this->manager()->delete($id)) {
            $this->response->json(ResponseFactory::error('Not found'), 404);
        }
        $this->response->json(ResponseFactory::success(['id' => $id], 'moved to trash'));
    }

    private function manager(): FileManager
    {
        return $this->manager ??= $this->factory->manager();
    }
}
