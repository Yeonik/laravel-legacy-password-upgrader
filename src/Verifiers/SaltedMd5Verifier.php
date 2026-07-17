<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Verifiers;

use Yeonik\LegacyPasswordUpgrader\Contracts\LegacyHashVerifier;

/**
 * Verifies a salted MD5 hash stored as "digest:salt", where
 * digest = md5($salt . $plain).
 *
 * Storing the salt alongside the digest is what old PHP apps commonly did; the
 * salt is not secret, it only defeats precomputed rainbow tables. Keeping the
 * two-part format here means the provider can migrate such rows without any
 * per-user configuration.
 */
final class SaltedMd5Verifier implements LegacyHashVerifier
{
    public function verify(string $plain, string $hash): bool
    {
        if (! str_contains($hash, ':')) {
            return false;
        }

        [$digest, $salt] = explode(':', $hash, 2);

        if ($digest === '' || $salt === '') {
            return false;
        }

        // Constant-time comparison; see Md5Verifier for the reasoning.
        return hash_equals($digest, md5($salt.$plain));
    }
}
