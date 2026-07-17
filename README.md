# laravel-legacy-password-upgrader

Transparently upgrade legacy password hashes (MD5 / SHA1 / salted MD5) to bcrypt
the first time a user signs in. Users migrate themselves, silently, with no mass
reset email, no forced migration, and no downtime.

## Why on login, and nowhere else

You cannot re-hash a password you do not have. A stored MD5 hash is a one-way
value — there is no way to turn it back into bcrypt in a batch job. The only
moment the plaintext ever exists is the instant the user submits the login form.

So the upgrade has to happen exactly there: verify the submitted password
against the old hash and, if it matches, immediately re-hash it with bcrypt and
discard the legacy value. Everyone who logs in is migrated; everyone who never
logs in keeps their old hash until they do. This is the detail that decides
whether a real "rewrite and migrate" ships or stalls.

## Requirements

PHP 8.3+, Laravel 12+.

Laravel 11 is not supported: the 11.x branch is past security support and every
release in it is affected by unpatched advisories (CVE-2026-48019 among them),
so Composer refuses to install it. A package that exists to improve password
security should not pull in a vulnerable framework.

## Install

```bash
composer require yeonik/laravel-legacy-password-upgrader
```

The service provider is auto-discovered. Publish the config if you want to
change the defaults:

```bash
php artisan vendor:publish --tag=legacy-password-config
```

## Schema

A user row carries a modern hash **or** a legacy one, never both. Add a nullable
legacy column and make the password column nullable:

```php
Schema::table('users', function (Blueprint $table): void {
    $table->string('password')->nullable()->change();
    $table->string('legacy_password')->nullable();
});
```

Import each legacy account with its old hash in `legacy_password` and `password`
left `null`. After the user's first successful login the columns flip: `password`
holds bcrypt and `legacy_password` is back to `null`.

## Enable the driver

Point your user provider at the `legacy-eloquent` driver in `config/auth.php`:

```php
'providers' => [
    'users' => [
        'driver' => 'legacy-eloquent',
        'model' => App\Models\User::class,
    ],
],
```

Nothing else changes — `Auth::attempt()`, guards, and middleware all behave
exactly as before.

## Configuration

`config/legacy-password.php`:

```php
return [
    // Master switch. When false, behaviour is exactly Laravel's default.
    'enabled' => (bool) env('LEGACY_PASSWORD_UPGRADER_ENABLED', true),

    // The nullable column holding the old hash.
    'column' => 'legacy_password',

    // Legacy schemes to try, in order. Add your own by implementing
    // LegacyHashVerifier and listing the class here — the provider never changes.
    'verifiers' => [
        Yeonik\LegacyPasswordUpgrader\Verifiers\Md5Verifier::class,
        Yeonik\LegacyPasswordUpgrader\Verifiers\Sha1Verifier::class,
        Yeonik\LegacyPasswordUpgrader\Verifiers\SaltedMd5Verifier::class,
    ],
];
```

`SaltedMd5Verifier` expects the stored value in `digest:salt` form, where
`digest = md5($salt . $plain)`.

### Custom schemes

```php
use Yeonik\LegacyPasswordUpgrader\Contracts\LegacyHashVerifier;

final class Sha256Verifier implements LegacyHashVerifier
{
    public function verify(string $plain, string $hash): bool
    {
        return hash_equals($hash, hash('sha256', $plain));
    }
}
```

Add the class to the `verifiers` array. Use `hash_equals` so a failed check
runs in constant time.

## Security notes

- **Plaintext is never logged, stored, or kept.** It exists only as a local
  variable during the verifying request and is discarded when the request ends.
- **Comparisons are constant-time.** Every verifier uses `hash_equals`, so a
  failed check leaks nothing about how many bytes matched.
- **The rehash write happens once**, only on the successful-login request. A
  wrong password writes nothing.
- **Modern accounts are untouched.** If a row has no legacy hash, the request
  goes straight to Laravel's stock `EloquentUserProvider`, including its own
  bcrypt cost-rehashing.

## How it works

A `LegacyUserProvider` decorates Laravel's `EloquentUserProvider` and overrides
`validateCredentials()`:

1. If a row has no legacy hash, defer to the parent — the normal bcrypt path.
2. If it has a legacy hash, run the submitted plaintext through each enabled
   verifier.
3. On a match, set `password = Hash::make($plaintext)`, null the legacy column,
   persist, and return `true`. On no match, return `false` and write nothing.

## Tests

The suite asserts outcomes only — never attack payloads:

| Scenario | Expected |
| --- | --- |
| Correct legacy password | Login succeeds; `password` is bcrypt, `legacy_password` is `null` |
| Wrong password | Login fails; nothing is written |
| Modern bcrypt user | Unaffected; parent path, no legacy read |
| Package disabled | Exactly Laravel's default behaviour |
| Each verifier | Accepts its own scheme, rejects the others |

## Quality gates

Three gates run in CI on every push:

```bash
vendor/bin/pint --test          # code style
vendor/bin/phpstan analyse      # static analysis, level 6 (larastan), no baseline
vendor/bin/phpunit              # test suite
```

## License

MIT. See [LICENSE](LICENSE).
