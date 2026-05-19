<?php

namespace App\Libraries;

/**
 * Werkzeug Password Hash Compatibility
 *
 * Verifies passwords hashed by Python Werkzeug's generate_password_hash().
 * Existing hashes use format: pbkdf2:sha256:iterations$salt$hexhash
 *
 * Implements dual-verify strategy:
 *  1. Try PHP bcrypt (new format) first
 *  2. Fall back to Werkzeug PBKDF2 format
 *  3. On successful Werkzeug verify, re-hash with bcrypt for gradual migration
 */
class WerkzeugHash
{
    /**
     * Verify a password against a stored hash (Werkzeug or bcrypt).
     *
     * @param string $password  The plaintext password to check
     * @param string $hash      The stored hash string
     * @return bool
     */
    public static function verify(string $password, string $hash): bool
    {
        // Try PHP bcrypt/argon2 first (new format after re-hashing)
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$argon2')) {
            return password_verify($password, $hash);
        }

        // Try Werkzeug PBKDF2 format: pbkdf2:algo:iterations$salt$hexhash
        if (str_starts_with($hash, 'pbkdf2:')) {
            return self::verifyPbkdf2($password, $hash);
        }

        return false;
    }

    /**
     * Verify against Werkzeug's PBKDF2 hash format.
     *
     * Format: pbkdf2:sha256:iterations$salt$hexhash
     */
    private static function verifyPbkdf2(string $password, string $hash): bool
    {
        // Split method$salt$hash
        $parts = explode('$', $hash, 3);
        if (count($parts) !== 3) {
            return false;
        }

        $method = $parts[0];  // e.g., "pbkdf2:sha256:1000000"
        $salt   = $parts[1];
        $stored = $parts[2];  // hex-encoded hash

        // Parse method: "pbkdf2:algo:iterations"
        $methodParts = explode(':', $method);
        if (count($methodParts) !== 3 || $methodParts[0] !== 'pbkdf2') {
            return false;
        }

        $algo       = $methodParts[1];  // "sha256"
        $iterations = (int) $methodParts[2];

        if ($iterations <= 0 || !in_array($algo, hash_algos(), true)) {
            return false;
        }

        // Compute PBKDF2 hash
        // Werkzeug uses: hashlib.pbkdf2_hmac(algo, password.encode('utf-8'), salt.encode('utf-8'), iterations)
        // Then hex-encodes the result
        $computed = hash_pbkdf2($algo, $password, $salt, $iterations, 0);

        return hash_equals($stored, $computed);
    }

    /**
     * Generate a new password hash using PHP bcrypt.
     *
     * @param string $password
     * @return string
     */
    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Check if a hash needs re-hashing (is in old Werkzeug format).
     *
     * @param string $hash
     * @return bool
     */
    public static function needsRehash(string $hash): bool
    {
        return str_starts_with($hash, 'pbkdf2:');
    }
}
