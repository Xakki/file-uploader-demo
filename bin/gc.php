<?php

declare(strict_types=1);

/**
 * Standalone garbage collector for the demo. Run by the `cron` container in a
 * loop (see docker-compose.yml) or once via `make gc`.
 *
 * Two passes, both gated on EXPIRE_MINUTES (0 = disabled, the gc is a no-op):
 *   1. Expire actives — for every guest scope `uploads/<sid>/`, hard-delete files
 *      whose metadata createdAt is older than EXPIRE_MINUTES.
 *   2. Sweep stale chunks — drop orphaned `.chunks/` parts left by aborted uploads.
 *
 * The gc must never hard-fail the cron loop: each scope/chunk is wrapped in
 * try/catch and the script always exits 0.
 */

use Demo\Config;
use Demo\Factory;
use Demo\Log\JsonStderrLogger;
use Psr\Log\NullLogger;
use Xakki\FileUploader\Auth\NullUserResolver;
use Xakki\FileUploader\Clock\SystemClock;
use Xakki\FileUploader\FileManager;
use Xakki\FileUploader\Storage\FlysystemStorage;

require __DIR__.'/../vendor/autoload.php';

$config = new Config;
$fs = Factory::buildFilesystem($config);
$storage = new FlysystemStorage($fs, static fn (string $p): string => '/dl?p='.rawurlencode($p));

// Best-effort structured logger (same fallback as Factory::logger()).
try {
    $logger = new JsonStderrLogger('file-uploader-demo');
} catch (Throwable) {
    $logger = new NullLogger;
}

$removedFiles = 0;
$sweptChunks = 0;
$expireSeconds = $config->expireMinutes * 60;

// --- Pass 1: expire active files per guest scope -----------------------------
// Discover guest session ids from metadata paths: uploads/<sid>/.meta/<hash>.json.
$sids = [];
$metaRe = '#^'.preg_quote($config->fuDir, '#').'/([a-f0-9]{8,64})/\.meta/[^/]+\.json$#';
foreach ($storage->allFiles($config->fuDir) as $path) {
    if (preg_match($metaRe, $path, $m) === 1) {
        $sids[$m[1]] = true;
    }
}
$scopes = array_keys($sids);

if ($config->expireMinutes > 0) {
    $now = new DateTimeImmutable('now');

    foreach ($scopes as $sid) {
        try {
            // Cron manager: hard-delete (array_merge so `soft_delete => false` wins
            // over the `true` baked into managerConfig), ownership bypassed via the
            // NullUserResolver + allow_delete_all_files already in the scope config.
            $manager = new FileManager(
                array_merge($config->managerConfig($sid), ['soft_delete' => false]),
                $storage,
                new NullUserResolver,
                $logger,
                new SystemClock,
            );

            foreach ($manager->list() as $row) {
                $age = $now->getTimestamp() - (new DateTimeImmutable($row['createdAt']))->getTimestamp();
                if ($age > $expireSeconds && $manager->delete($row['id'])) {
                    $removedFiles++;
                }
            }
        } catch (Throwable $e) {
            $logger->warning('gc scope failed', ['scope' => $sid, 'error' => (string) $e]);

            continue;
        }
    }
}

// --- Pass 2: sweep stale chunk parts -----------------------------------------
// Aborted uploads leave parts under `<sid>/.chunks/`; drop ones older than the
// expiry window by file mtime (no metadata to read for orphans).
if ($config->expireMinutes > 0) {
    try {
        foreach ($fs->listContents($config->fuDir, true) as $attrs) {
            if (! $attrs->isFile()) {
                continue;
            }
            $path = $attrs->path();
            if (! preg_match('#/\.chunks/#', $path)) {
                continue;
            }

            try {
                $mtime = $fs->lastModified($path);
                if ((time() - $mtime) > $expireSeconds && $storage->delete($path)) {
                    $sweptChunks++;
                }
            } catch (Throwable $e) {
                $logger->warning('gc chunk failed', ['path' => $path, 'error' => (string) $e]);

                continue;
            }
        }
    } catch (Throwable $e) {
        $logger->warning('gc chunk sweep failed', ['error' => (string) $e]);
    }
}

$logger->info('gc run', [
    'removed_files' => $removedFiles,
    'swept_chunks' => $sweptChunks,
    'scopes' => count($scopes),
    'expire_minutes' => $config->expireMinutes,
]);

exit(0);
