<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Verifiers;

use Yeonik\LegacyPasswordUpgrader\Contracts\LegacyHashVerifier;

/**
 * Verifies an unsalted, hex-encoded SHA1 hash: sha1($plain).
 */
final class Sha1Verifier implements LegacyHashVerifier
{
    public function verify(string $plain, string $hash): bool
    {
        // Constant-time comparison; see Md5Verifier for the reasoning.
        return hash_equals($hash, sha1($plain));
    }
}
