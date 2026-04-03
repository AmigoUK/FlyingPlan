<?php

namespace App\Models;

use CodeIgniter\Model;

class SharedLinkModel extends Model
{
    protected $table = 'shared_links';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'flight_plan_id', 'token', 'expires_at', 'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    /**
     * Generate a cryptographically secure URL-safe token.
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Check if a shared link is valid (active and not expired).
     */
    public static function isValid(object $link): bool
    {
        if (!$link->is_active) {
            return false;
        }
        if ($link->expires_at && strtotime($link->expires_at) < time()) {
            return false;
        }
        return true;
    }
}
