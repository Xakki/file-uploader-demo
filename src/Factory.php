<?php

declare(strict_types=1);

namespace Demo;

use AsyncAws\S3\S3Client;
use Demo\Log\JsonStderrLogger;
use League\Flysystem\AsyncAwsS3\AsyncAwsS3Adapter;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;
use Xakki\FileUploader\Clock\SystemClock;
use Xakki\FileUploader\Contracts\Storage;
use Xakki\FileUploader\FileManager;
use Xakki\FileUploader\Storage\FlysystemStorage;

/**
 * Wires the framework-agnostic core for standalone use. Storage target is chosen
 * by Config (STORAGE env): `local` (default) or `s3` (MinIO via async-aws).
 *
 * Every guest is isolated under `<fuDir>/<session id>/`: the manager is built with
 * that scope and a SessionUserResolver, and downloads are confined to the prefix.
 */
final class Factory
{
    public function __construct(
        private readonly Config $config,
        private readonly Session $session,
    ) {}

    public function manager(): FileManager
    {
        return new FileManager(
            $this->config->managerConfig($this->session->id()),
            $this->storage(),
            new SessionUserResolver($this->session),
            $this->logger(),
            new SystemClock,
        );
    }

    /**
     * Storage path prefix the current guest is allowed to read, e.g.
     * `uploads/<session id>/`. Used to confine the /dl endpoint to own files.
     */
    public function scopePrefix(): string
    {
        return trim($this->config->fuDir, '/').'/'.$this->session->id().'/';
    }

    public function storage(): Storage
    {
        // Uniform download URL for both backends: stream through the API.
        return new FlysystemStorage(self::buildFilesystem($this->config), static fn (string $path): string => '/dl?p='.rawurlencode($path));
    }

    /**
     * Config-only Flysystem builder (no Session), so CLI tools like bin/gc.php can
     * reuse the same local/S3 wiring. Local is the default: the storage root is
     * created on first use; S3 goes through async-aws (MinIO-compatible).
     */
    public static function buildFilesystem(Config $config): Filesystem
    {
        if ($config->isS3()) {
            $client = new S3Client([
                'endpoint' => $config->s3Endpoint,
                'accessKeyId' => $config->s3Key,
                'accessKeySecret' => $config->s3Secret,
                'region' => $config->s3Region,
                'pathStyleEndpoint' => true,
            ]);

            return new Filesystem(new AsyncAwsS3Adapter($client, $config->s3Bucket));
        }

        $root = $config->fuRoot ?? __DIR__.'/../storage';
        if (! is_dir($root)) {
            mkdir($root, 0o777, true);
        }

        return new Filesystem(new LocalFilesystemAdapter($root));
    }

    /**
     * Best-effort structured JSON logger: emits single-line JSON to stderr, which
     * php-fpm forwards to the fluent-bit pipeline shipped by xakki/log-fluent.
     * Logging must never break a request, so any failure falls back to NullLogger.
     */
    public function logger(): LoggerInterface
    {
        try {
            return new JsonStderrLogger('file-uploader-demo');
        } catch (Throwable) {
            return new NullLogger;
        }
    }

}
