<?php

declare(strict_types=1);

namespace Yeonik\LegacyPasswordUpgrader\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yeonik\LegacyPasswordUpgrader\Verifiers\Md5Verifier;
use Yeonik\LegacyPasswordUpgrader\Verifiers\SaltedMd5Verifier;
use Yeonik\LegacyPasswordUpgrader\Verifiers\Sha1Verifier;

final class VerifiersTest extends TestCase
{
    public function test_md5_verifier_accepts_its_scheme_and_rejects_others(): void
    {
        $verifier = new Md5Verifier;

        $this->assertTrue($verifier->verify('secret', md5('secret')));
        $this->assertFalse($verifier->verify('secret', sha1('secret')));
        $this->assertFalse($verifier->verify('secret', md5('different')));
        $this->assertFalse($verifier->verify('secret', md5('salt').':salt'));
    }

    public function test_sha1_verifier_accepts_its_scheme_and_rejects_others(): void
    {
        $verifier = new Sha1Verifier;

        $this->assertTrue($verifier->verify('secret', sha1('secret')));
        $this->assertFalse($verifier->verify('secret', md5('secret')));
        $this->assertFalse($verifier->verify('secret', sha1('different')));
    }

    public function test_salted_md5_verifier_accepts_its_scheme_and_rejects_others(): void
    {
        $verifier = new SaltedMd5Verifier;

        $salt = 'a1b2c3';
        $stored = md5($salt.'secret').':'.$salt;

        $this->assertTrue($verifier->verify('secret', $stored));
        $this->assertFalse($verifier->verify('secret', md5('secret')));           // no salt segment
        $this->assertFalse($verifier->verify('wrong', $stored));                  // wrong plaintext
        $this->assertFalse($verifier->verify('secret', md5('x'.'other').':x'));   // digest of a different password
        $this->assertFalse($verifier->verify('secret', ':'));                     // empty segments
    }
}
