<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Verifiers;

use Yeonik\LegacyPasswordUpgrader\Contracts\LegacyHashVerifier;

/**
 * Verifies an unsalted, hex-encoded MD5 hash: md5($plain).
 */
final class Md5Verifier implements LegacyHashVerifier
{
    public function verify(string $plain, string $hash): bool
    {
        // hash_equals compares in constant time: it does not stop at the first
        // differing byte, so the duration of a failed check leaks nothing.
        return hash_equals($hash, md5($plain));
    }
}
