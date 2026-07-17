<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Contracts;

/**
 * A strategy that knows how to check a plaintext password against a single
 * legacy hashing scheme (for example MD5, SHA1, or a salted variant).
 *
 * Implementations MUST use a constant-time comparison so that a failed check
 * reveals nothing about how many leading bytes matched.
 */
interface LegacyHashVerifier
{
    /**
     * Does the submitted plaintext produce the given legacy hash?
     *
     * @param  string  $plain  The password the user just submitted.
     * @param  string  $hash  The legacy hash stored for that user.
     */
    public function verify(string $plain, string $hash): bool;
}
