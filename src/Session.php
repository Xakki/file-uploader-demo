<?php

declare(strict_types=1);

namespace Demo;

/**
 * Guest identity for the demo: a random id kept in a long-lived cookie. There is
 * no login here, so the cookie *is* the user — it scopes every upload to its own
 * `uploads/<id>/` folder (see Factory / Config::managerConfig).
 *
 * The id is also a filesystem path segment, so it is constrained to a safe charset
 * on the way in; anything else is treated as missing and a fresh id is issued.
 */
final class Session
{
    /** Cookie name carrying the guest id. */
    public const COOKIE = 'fu_sid';

    /** Cookie lifetime in seconds (1 year). */
    private const TTL = 31_536_000;

    private readonly string $id;

    /**
     * @param  array<string, mixed>  $cookies  defaults to $_COOKIE
     */
    public function __construct(?array $cookies = null)
    {
        $cookies ??= $_COOKIE;
        $raw = (string) ($cookies[self::COOKIE] ?? '');
        $id = self::sanitize($raw);

        if ($id === '') {
            $id = bin2hex(random_bytes(16));   // 32 hex chars
            $this->issue($id);
        }

        $this->id = $id;
    }

    /** Sanitized, path-safe guest id (always non-empty). */
    public function id(): string
    {
        return $this->id;
    }

    /** Keep only hex and bound the length, so the id is a safe path segment. */
    private static function sanitize(string $raw): string
    {
        $clean = (string) preg_replace('/[^a-f0-9]/i', '', $raw);

        return strlen($clean) >= 8 && strlen($clean) <= 64 ? strtolower($clean) : '';
    }

    private function issue(string $id): void
    {
        $_COOKIE[self::COOKIE] = $id;   // visible to the rest of this request too

        if (headers_sent()) {
            return;
        }

        setcookie(self::COOKIE, $id, [
            'expires' => time() + self::TTL,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
}
