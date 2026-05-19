<?php

use CodeIgniter\Test\CIUnitTestCase;
use App\Libraries\WerkzeugHash;

/**
 * @internal
 */
final class WerkzeugHashTest extends CIUnitTestCase
{
    /**
     * Known Werkzeug PBKDF2 hash for pilot user "jmitchell" with password "demo123".
     * Format: pbkdf2:sha256:iterations$salt$hexhash
     */
    private const KNOWN_HASH = 'pbkdf2:sha256:1000000$TPixmelt5Ls1gXuZ$f4151ecbfe5b5d79e8afed32db92daeabbc706b80f9fdd1d829e12a446a2dc7c';
    private const KNOWN_PASSWORD = 'demo123';

    public function testVerifyCorrectPassword(): void
    {
        $result = WerkzeugHash::verify(self::KNOWN_PASSWORD, self::KNOWN_HASH);
        $this->assertTrue($result, 'Should verify correct password against Werkzeug PBKDF2 hash');
    }

    public function testVerifyWrongPassword(): void
    {
        $result = WerkzeugHash::verify('wrongpassword', self::KNOWN_HASH);
        $this->assertFalse($result, 'Should reject incorrect password');
    }

    public function testVerifyBcryptHash(): void
    {
        $bcrypt = password_hash('testpass', PASSWORD_BCRYPT);
        $this->assertTrue(WerkzeugHash::verify('testpass', $bcrypt));
        $this->assertFalse(WerkzeugHash::verify('wrong', $bcrypt));
    }

    public function testHashReturnsBcrypt(): void
    {
        $hash = WerkzeugHash::hash('mypassword');
        $this->assertStringStartsWith('$2y$', $hash, 'hash() should return bcrypt');
        $this->assertTrue(password_verify('mypassword', $hash));
    }

    public function testNeedsRehashForWerkzeug(): void
    {
        $this->assertTrue(WerkzeugHash::needsRehash(self::KNOWN_HASH));
    }

    public function testNeedsRehashForBcrypt(): void
    {
        $bcrypt = password_hash('test', PASSWORD_BCRYPT);
        $this->assertFalse(WerkzeugHash::needsRehash($bcrypt));
    }

    public function testVerifyMalformedHash(): void
    {
        $this->assertFalse(WerkzeugHash::verify('test', ''));
        $this->assertFalse(WerkzeugHash::verify('test', 'garbage'));
        $this->assertFalse(WerkzeugHash::verify('test', 'pbkdf2:'));
        $this->assertFalse(WerkzeugHash::verify('test', 'pbkdf2:sha256:bad$salt'));
    }

    public function testVerifyAgainstLiveDatabase(): void
    {
        // The demo database has known users. If DB is available, verify.
        // Use 'default' group explicitly to bypass test SQLite DB.
        try {
            $db = \Config\Database::connect('default');
        } catch (\Exception $e) {
            $this->markTestSkipped('Database not available: ' . $e->getMessage());
        }
        $user = $db->table('users')->where('username', 'admin')->get()->getRow();

        if ($user) {
            // admin password is "demo123" (set by seed_demo.py, may be bcrypt or werkzeug)
            $verified = WerkzeugHash::verify('demo123', $user->password_hash);
            $this->assertTrue($verified, 'Should verify admin password against live database hash');
            $this->assertFalse(
                WerkzeugHash::verify('wrongpass', $user->password_hash),
                'Should reject wrong password for admin user'
            );
        } else {
            $this->markTestSkipped('Admin user not found in database');
        }
    }
}
