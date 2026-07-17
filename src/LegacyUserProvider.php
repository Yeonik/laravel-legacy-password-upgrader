<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Model;
use SensitiveParameter;
use Yeonik\LegacyPasswordUpgrader\Contracts\LegacyHashVerifier;

/**
 * An EloquentUserProvider that transparently upgrades legacy password hashes to
 * bcrypt the first time a user signs in successfully.
 *
 * You cannot re-hash a password you do not have. The only moment the plaintext
 * exists is when the user submits the login form, so that is where the upgrade
 * must happen: verify the submission against the old hash and, on a match,
 * immediately re-hash it with the modern hasher and discard the legacy value.
 */
class LegacyUserProvider extends EloquentUserProvider
{
    /**
     * @param  list<LegacyHashVerifier>  $verifiers  Enabled legacy schemes, tried in order.
     */
    public function __construct(
        Hasher $hasher,
        string $model,
        private readonly bool $enabled,
        private readonly string $legacyColumn,
        private readonly array $verifiers,
    ) {
        parent::__construct($hasher, $model);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, #[SensitiveParameter] array $credentials): bool
    {
        // Disabled: behave exactly like Laravel's stock provider.
        if (! $this->enabled) {
            return parent::validateCredentials($user, $credentials);
        }

        $plain = $credentials['password'] ?? null;

        if (! is_string($plain) || $plain === '') {
            return false;
        }

        $legacyHash = $this->legacyHashFor($user);

        // No legacy hash on this row: it is a normal, modern account. Defer to
        // the parent so the untouched bcrypt path (and its own rehash-on-cost
        // logic) applies.
        if ($legacyHash === null) {
            return parent::validateCredentials($user, $credentials);
        }

        foreach ($this->verifiers as $verifier) {
            if ($verifier->verify($plain, $legacyHash)) {
                $this->upgrade($user, $plain);

                return true;
            }
        }

        return false;
    }

    /**
     * Read the stored legacy hash for this user, or null if there is none.
     */
    private function legacyHashFor(Authenticatable $user): ?string
    {
        if (! $user instanceof Model) {
            return null;
        }

        $value = $user->getAttribute($this->legacyColumn);

        if (! is_string($value) || $value === '') {
            return null;
        }

        return $value;
    }

    /**
     * Replace the legacy hash with a modern one. Runs exactly once, only on the
     * successful-login request.
     */
    private function upgrade(Authenticatable $user, #[SensitiveParameter] string $plain): void
    {
        if (! $user instanceof Model) {
            return;
        }

        $user->setAttribute($user->getAuthPasswordName(), $this->hasher->make($plain));
        $user->setAttribute($this->legacyColumn, null);
        $user->save();

        // $plain is a local parameter. It is never assigned to a property,
        // logged, or persisted anywhere; it is discarded when this method
        // returns and the request ends.
    }
}
