<?php

declare(strict_types=1);

namespace Demo\Log;

use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

/**
 * Best-effort PSR-3 logger that writes ONE single-line JSON record per call to
 * stderr (php-fpm forwards fd/2). fluent-bit's `log_format: php` route parses
 * single-line JSON (json_default) into Graylog — so each record must stay on one
 * physical line. json_encode() escapes embedded newlines (e.g. exception stack
 * traces the core logs via (string) $e) inside the JSON string, keeping it single
 * line. Logging must never break a request, so all failures are swallowed.
 *
 * The xakki/log-fluent package (in vendor) supplies the Docker pipeline, not a PHP
 * logger; this tiny adapter is the PHP side that emits the structured JSON it ships.
 */
final class JsonStderrLogger extends AbstractLogger
{
    public function __construct(
        private readonly string $channel = 'app',
    ) {}

    /**
     * @param  mixed  $level
     * @param  array<string, mixed>  $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        try {
            $record = [
                'datetime' => date('c'),
                'channel' => $this->channel,
                'level' => (string) $level,
                'message' => (string) $message,
            ];
            if ($context !== []) {
                $record['context'] = $context;
            }

            $json = json_encode(
                $record,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR,
            );
            if ($json === false) {
                return;
            }

            $stream = fopen('php://stderr', 'wb');
            if ($stream === false) {
                return;
            }
            fwrite($stream, $json."\n");
            fclose($stream);
        } catch (Throwable) {
            // Best-effort: never let logging break a request.
        }
    }
}
