<?php

declare(strict_types=1);

/**
 * Tiny dependency-free smoke test for the per-session isolation wiring.
 * Run via `make smoke`. Exits non-zero on the first failed assertion.
 */

use Demo\Config;
use Demo\Session;

require __DIR__.'/../vendor/autoload.php';

$failures = 0;
function check(string $label, bool $ok): void
{
    global $failures;
    echo ($ok ? '  ok   ' : '  FAIL ').$label."\n";
    if (! $ok) {
        $failures++;
    }
}

// A valid hex cookie is kept (lower-cased), not regenerated.
$kept = new Session(['fu_sid' => 'ABCDEF0123456789']);
check('valid cookie is reused', $kept->id() === 'abcdef0123456789');

// A path-traversal / junk value is rejected and a fresh 32-hex id is issued.
$evil = new Session(['fu_sid' => '../../etc/passwd']);
check('traversal value rejected', (bool) preg_match('/^[a-f0-9]{32}$/', $evil->id()));
check('rejected value not used as id', ! str_contains($evil->id(), '..'));

// No cookie at all → fresh 32-hex id.
$fresh = new Session([]);
check('missing cookie → new id', (bool) preg_match('/^[a-f0-9]{32}$/', $fresh->id()));

// Config scopes every storage dir under uploads/<sid>/.
$sid = $kept->id();
$cfg = (new Config)->managerConfig($sid);
check('directory scoped',           $cfg['directory'] === "uploads/$sid");
check('metadata_directory scoped',  $cfg['metadata_directory'] === "uploads/$sid/.meta");
check('temporary_directory scoped', $cfg['temporary_directory'] === "uploads/$sid/.chunks");
check('trash_directory scoped',     $cfg['trash_directory'] === "uploads/$sid/.trash");

// No scope → flat layout (back-compat).
$flat = (new Config)->managerConfig();
check('no scope → flat directory', $flat['directory'] === 'uploads');

// Config env knobs: defaults survive when unset; env wins (and a literal "0"
// survives as 0 = disabled, like maxFiles). Each `new Config` reads getenv live,
// so unset (bare putenv, no '=') after every mutating assertion to leave defaults.
check('EXPIRE_MINUTES default is 60', (new Config)->expireMinutes === 60);

putenv('EXPIRE_MINUTES=0');
check('EXPIRE_MINUTES=0 → disabled (0)', (new Config)->expireMinutes === 0);
putenv('EXPIRE_MINUTES');

putenv('FU_MAX_FILES=15');
check('FU_MAX_FILES read from env', (new Config)->maxFiles === 15);
putenv('FU_MAX_FILES');

putenv('FU_MAX_SIZE=52428800');
check('FU_MAX_SIZE read from env', (new Config)->maxSize === 52428800);
putenv('FU_MAX_SIZE');

// gc "is-expired" decision is a pure age-vs-window comparison (mirrors bin/gc.php).
$isExpired = static fn (string $createdAt, int $windowSec): bool => (new DateTimeImmutable('now'))->getTimestamp()
    - (new DateTimeImmutable($createdAt))->getTimestamp() > $windowSec;
$old = (new DateTimeImmutable('now'))->modify('-2 hours')->format('c');
$new = (new DateTimeImmutable('now'))->format('c');
check('gc expires file older than window', $isExpired($old, 3600));
check('gc keeps fresh file', ! $isExpired($new, 3600));

echo $failures === 0 ? "\nPASS\n" : "\nFAILED: $failures\n";
exit($failures === 0 ? 0 : 1);
