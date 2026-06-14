<?php

declare(strict_types=1);

namespace Demo\Http;

/**
 * Terminal HTTP responses. Each method writes headers + body and exits, exactly
 * like the procedural front controller did (send/echo/fpassthru + exit).
 */
final class Response
{
    /**
     * JSON envelope — the old global `send()`.
     *
     * @param  array<string, mixed>  $payload
     */
    public function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    public function html(string $body): never
    {
        header('Content-Type: text/html; charset=utf-8');
        echo $body;
        exit;
    }

    /**
     * Stream a download with attachment disposition + Content-Length (the /dl route).
     *
     * @param  resource  $stream
     */
    public function download($stream, string $name, string $mime, int $size): never
    {
        header('Content-Type: '.$mime);
        // attachment → force a download (inline made the browser preview text/images instead).
        header('Content-Disposition: attachment; filename="'.$name.'"');
        header('Content-Length: '.(string) $size);
        fpassthru($stream);
        fclose($stream);
        exit;
    }
}
