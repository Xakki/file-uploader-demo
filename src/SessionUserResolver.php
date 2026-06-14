<?php

declare(strict_types=1);

namespace Demo;

use Xakki\FileUploader\Contracts\UserResolver;

/**
 * Maps the guest Session id onto the core identity seam, so the core tags each
 * file's metadata with an owner. Roles are unused in the demo.
 */
final class SessionUserResolver implements UserResolver
{
    public function __construct(
        private readonly Session $session,
    ) {}

    public function id(): ?string
    {
        return $this->session->id();
    }

    public function hasAnyRole(array $roles): bool
    {
        return false;
    }
}
