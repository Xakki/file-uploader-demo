<?php

declare(strict_types=1);

namespace Demo;

/**
 * Single place that reads the environment once and exposes typed getters.
 * Storage settings (local/S3) feed the Factory; the app constants below feed
 * the router prefix, the core FileManager config, and the widget/form JS config.
 */
final class Config
{
    /** API endpoint prefix the widget talks to. */
    public const ENDPOINT_PREFIX = '/file-upload';

    /** Default upload chunk size in bytes when FU_CHUNK_SIZE is unset. */
    public const CHUNK_SIZE = 1048576;

    /** Default max upload size in bytes when FU_MAX_SIZE is unset (200 MiB). */
    public const MAX_SIZE = 1024 * 1024 * 200;

    /** Default allow-list when FU_ALLOWED_EXTENSIONS is unset: images + text. */
    public const DEFAULT_ALLOWED_EXTENSIONS = 'jpg,jpeg,png,gif,webp,svg,bmp,txt,csv,md';

    /** Default trash TTL in days when FU_TRASH_TTL_DAYS is unset. */
    public const TRASH_TTL_DAYS = 30;

    /** Default cap on active files when FU_MAX_FILES is unset (0 = unlimited). */
    public const MAX_FILES = 100;

    /** Default minutes before an active guest file is hard-deleted by gc (0 = disabled). */
    public const EXPIRE_MINUTES = 60;

    public readonly string $storage;

    public readonly string $s3Endpoint;

    public readonly string $s3Key;

    public readonly string $s3Secret;

    public readonly string $s3Bucket;

    public readonly string $s3Region;

    public readonly string $fuDir;

    public readonly ?string $fuRoot;

    /** Upload chunk size in bytes (widget slicing + advertised core config). */
    public readonly int $chunkSize;

    /** Max accepted upload size in bytes (0 = unlimited). */
    public readonly int $maxSize;

    /** Allowed extensions as a CSV string consumed by the core guard. */
    public readonly string $allowedExtensions;

    /** Days a soft-deleted file lingers in trash before cleanup. */
    public readonly int $trashTtlDays;

    /** Cap on active (non-deleted) files (0 = unlimited). */
    public readonly int $maxFiles;

    /** Minutes before an active guest file is hard-deleted by the gc (0 = disabled). */
    public readonly int $expireMinutes;

    public function __construct()
    {
        $this->storage = getenv('STORAGE') ?: 'local';
        $this->s3Endpoint = getenv('S3_ENDPOINT') ?: 'http://minio:9000';
        $this->s3Key = getenv('S3_KEY') ?: 'minioadmin';
        $this->s3Secret = getenv('S3_SECRET') ?: 'minioadmin';
        $this->s3Bucket = getenv('S3_BUCKET') ?: 'uploads';
        $this->s3Region = getenv('S3_REGION') ?: 'us-east-1';
        $this->fuDir = getenv('FU_DIR') ?: 'uploads';
        $root = getenv('FU_ROOT');
        $this->fuRoot = $root !== false && $root !== '' ? $root : null;

        $this->chunkSize = self::envInt('FU_CHUNK_SIZE', self::CHUNK_SIZE);
        $this->maxSize = self::envInt('FU_MAX_SIZE', self::MAX_SIZE);
        $this->allowedExtensions = self::envString('FU_ALLOWED_EXTENSIONS', self::DEFAULT_ALLOWED_EXTENSIONS);
        $this->trashTtlDays = self::envInt('FU_TRASH_TTL_DAYS', self::TRASH_TTL_DAYS);
        $this->maxFiles = self::envInt('FU_MAX_FILES', self::MAX_FILES);
        $this->expireMinutes = self::envInt('EXPIRE_MINUTES', self::EXPIRE_MINUTES);
    }

    /**
     * Read an integer env var. Unlike `getenv() ?: $default`, a literal "0"
     * survives as 0 (so FU_MAX_FILES=0 / FU_MAX_SIZE=0 mean "unlimited", not the
     * default).
     */
    private static function envInt(string $key, int $default): int
    {
        $value = getenv($key);

        return $value !== false && $value !== '' ? (int) $value : $default;
    }

    private static function envString(string $key, string $default): string
    {
        $value = getenv($key);

        return $value !== false && $value !== '' ? $value : $default;
    }

    public function isS3(): bool
    {
        return $this->storage === 's3';
    }

    /**
     * Storage label rendered into the landing/widget pages (HTML-escaped, as today).
     */
    public function storageLabel(): string
    {
        return htmlspecialchars($this->storage, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Config array for the core FileManager (mirrors the previous Factory::config()).
     *
     * When $scope is given (the guest session id), every storage directory is
     * nested under `<fuDir>/<scope>/`, isolating each guest's files, metadata,
     * chunks and trash. $scope is expected pre-sanitized (see Session).
     *
     * @return array<string, mixed>
     */
    public function managerConfig(?string $scope = null): array
    {
        $dir = $scope !== null && $scope !== '' ? $this->fuDir.'/'.$scope : $this->fuDir;

        return [
            'disk' => $this->storage,
            'directory' => $dir,
            'temporary_directory' => $dir.'/.chunks',
            'metadata_directory' => $dir.'/.meta',
            'trash_directory' => $dir.'/.trash',
            'chunk_size' => $this->chunkSize,
            'max_size' => $this->maxSize,
            'max_files' => $this->maxFiles,
            'allowed_extensions' => $this->allowedExtensions,
            'soft_delete' => true,
            'trash_ttl_days' => $this->trashTtlDays,
            'allow_list' => true,
            'allow_delete' => true,
            'allow_cleanup' => true,
            'allow_delete_all_files' => true, // no auth in the demo
            'full_access' => ['users' => [], 'roles' => []],
        ];
    }
}
