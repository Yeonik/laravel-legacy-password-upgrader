<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Tests;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Minimal user model for the test suite. The base framework user has no
 * "hashed" cast, so legacy values can be stored verbatim.
 *
 * @property string|null $password
 * @property string|null $legacy_password
 */
class TestUser extends Authenticatable
{
    protected $table = 'users';

    /** @var list<string> */
    protected $guarded = [];

    /** @var list<string> */
    protected $hidden = ['password', 'legacy_password'];
}
