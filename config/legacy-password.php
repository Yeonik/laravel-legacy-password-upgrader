<?php

declare(strict_types=1);

use Yeonik\LegacyPasswordUpgrader\Verifiers\Md5Verifier;
use Yeonik\LegacyPasswordUpgrader\Verifiers\SaltedMd5Verifier;
use Yeonik\LegacyPasswordUpgrader\Verifiers\Sha1Verifier;

return [

    /*
    |--------------------------------------------------------------------------
    | Master switch
    |--------------------------------------------------------------------------
    |
    | When disabled, the custom user provider defers every credential check to
    | Laravel's stock EloquentUserProvider. No legacy column is read and no
    | rehash is ever written: behaviour is exactly Laravel's default.
    |
    */

    'enabled' => (bool) env('LEGACY_PASSWORD_UPGRADER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Legacy hash column
    |--------------------------------------------------------------------------
    |
    | The nullable column that holds the old hash. A row carries either a
    | modern `password` or this legacy value, never both. Once a user logs in
    | successfully, the legacy value is nulled and `password` receives bcrypt.
    |
    */

    'column' => 'legacy_password',

    /*
    |--------------------------------------------------------------------------
    | Enabled verifiers
    |--------------------------------------------------------------------------
    |
    | Each verifier is a strategy that knows one legacy scheme. On login the
    | submitted plaintext is checked against the stored legacy hash by every
    | enabled verifier, in order, until one matches. Add a scheme by adding a
    | class here; the provider never changes.
    |
    */

    'verifiers' => [
        Md5Verifier::class,
        Sha1Verifier::class,
        SaltedMd5Verifier::class,
    ],

];
