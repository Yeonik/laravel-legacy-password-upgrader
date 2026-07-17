<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Yeonik\LegacyPasswordUpgrader\Tests\TestCase;
use Yeonik\LegacyPasswordUpgrader\Tests\TestUser;

final class LegacyUpgradeTest extends TestCase
{
    public function test_correct_legacy_password_logs_in_and_upgrades_to_bcrypt(): void
    {
        $user = TestUser::create([
            'email' => 'ada@example.com',
            'password' => null,
            'legacy_password' => md5('correct-horse'),
        ]);

        $ok = Auth::attempt(['email' => 'ada@example.com', 'password' => 'correct-horse']);

        $this->assertTrue($ok);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->legacy_password);
        $this->assertNotNull($fresh->password);
        $this->assertTrue(Hash::check('correct-horse', (string) $fresh->password));
        // The stored value is a real bcrypt hash, not the legacy MD5.
        $this->assertStringStartsWith('$2y$', (string) $fresh->password);
    }

    public function test_wrong_password_neither_logs_in_nor_writes(): void
    {
        $legacy = md5('correct-horse');

        $user = TestUser::create([
            'email' => 'grace@example.com',
            'password' => null,
            'legacy_password' => $legacy,
        ]);

        $ok = Auth::attempt(['email' => 'grace@example.com', 'password' => 'wrong']);

        $this->assertFalse($ok);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame($legacy, $fresh->legacy_password);
        $this->assertNull($fresh->password);
    }

    public function test_modern_bcrypt_user_is_unaffected(): void
    {
        $user = TestUser::create([
            'email' => 'linus@example.com',
            'password' => Hash::make('already-modern'),
            'legacy_password' => null,
        ]);

        $ok = Auth::attempt(['email' => 'linus@example.com', 'password' => 'already-modern']);

        $this->assertTrue($ok);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->legacy_password);
        $this->assertTrue(Hash::check('already-modern', (string) $fresh->password));
    }

    public function test_disabled_package_behaves_like_default_laravel(): void
    {
        config(['legacy-password.enabled' => false]);
        Auth::forgetGuards();

        $legacy = md5('correct-horse');

        $user = TestUser::create([
            'email' => 'edsger@example.com',
            'password' => null,
            'legacy_password' => $legacy,
        ]);

        // Disabled: the legacy hash is never verified, so even the correct
        // legacy password fails and nothing is written — exactly Laravel's
        // stock behaviour for a user with no usable password hash.
        $ok = Auth::attempt(['email' => 'edsger@example.com', 'password' => 'correct-horse']);

        $this->assertFalse($ok);

        $fresh = $user->fresh();
        $this->assertNotNull($fresh);
        $this->assertSame($legacy, $fresh->legacy_password);
        $this->assertNull($fresh->password);
    }
}
