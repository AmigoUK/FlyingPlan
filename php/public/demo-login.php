<?php
// Demo auto-login — redirects to dashboard after logging in as the specified user
$allowed = ['admin', 'pilot', 'pilot.singh', 'pilot.chen'];
$user = $_GET['user'] ?? '';

if (!in_array($user, $allowed)) {
    http_response_code(400);
    die('Invalid demo user.');
}

$passwords = [
    'admin'       => 'Admin123!',
    'pilot'       => 'Pilot123!',
    'pilot.singh' => 'Pilot123!',
    'pilot.chen'  => 'Pilot123!',
];

// Boot CI4 minimally to use the session
require_once __DIR__ . '/../system/Boot.php';
CodeIgniter\Boot::bootWeb(new Config\Paths());

$db = \Config\Database::connect();
$userRow = $db->table('users')->where('username', $user)->get()->getRow();

if (!$userRow) {
    die('User not found.');
}

// Verify password
if (!password_verify($passwords[$user], $userRow->password_hash)) {
    // Try Werkzeug hash
    if (class_exists('\App\Services\WerkzeugHash') && \App\Services\WerkzeugHash::verify($passwords[$user], $userRow->password_hash)) {
        // OK
    } else {
        die('Password mismatch.');
    }
}

// Set session
session()->set([
    'user_id'      => $userRow->id,
    'username'     => $userRow->username,
    'display_name' => $userRow->display_name,
    'role'         => $userRow->role,
    'logged_in'    => true,
]);

// Redirect based on role
$redirect = ($userRow->role === 'pilot') ? '/pilot' : '/admin';
header('Location: ' . rtrim(base_url(), '/') . $redirect);
exit;
