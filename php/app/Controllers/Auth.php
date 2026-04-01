<?php

namespace App\Controllers;

use App\Libraries\WerkzeugHash;
use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    /**
     * Rate limiting: track failed login attempts per IP.
     */
    private function isRateLimited(): bool
    {
        $session = session();
        $ip = $this->request->getIPAddress();
        $key = 'login_failures_' . $ip;
        $failures = $session->get($key) ?? [];

        // Remove attempts older than 30 seconds
        $cutoff = time() - 30;
        $failures = array_filter($failures, fn($t) => $t > $cutoff);
        $session->set($key, $failures);

        return count($failures) >= 5;
    }

    private function recordFailure(): void
    {
        $session = session();
        $ip = $this->request->getIPAddress();
        $key = 'login_failures_' . $ip;
        $failures = $session->get($key) ?? [];
        $failures[] = time();
        $session->set($key, $failures);
    }

    /**
     * GET/POST /login
     */
    public function login()
    {
        if ($this->request->getMethod() === 'POST') {
            if ($this->isRateLimited()) {
                return redirect()->to(site_url('login'))
                    ->with('flash_danger', 'Too many login attempts. Please wait 30 seconds.');
            }

            $username = $this->request->getPost('username');
            $password = $this->request->getPost('password');

            $userModel = new UserModel();
            $user = $userModel->findByUsername($username);

            if ($user && WerkzeugHash::verify($password, $user->password_hash)) {
                // Re-hash if using old Werkzeug format
                if (WerkzeugHash::needsRehash($user->password_hash)) {
                    $userModel->update($user->id, [
                        'password_hash' => WerkzeugHash::hash($password),
                    ]);
                }

                // Store user in session
                session()->set([
                    'user_id'      => $user->id,
                    'username'     => $user->username,
                    'display_name' => $user->display_name,
                    'role'         => $user->role,
                    'logged_in'    => true,
                ]);

                // Role-based redirect
                if ($user->role === 'pilot') {
                    return redirect()->to(site_url('pilot'));
                }
                return redirect()->to(site_url('admin'));
            }

            $this->recordFailure();
            return redirect()->to(site_url('login'))
                ->with('flash_danger', 'Invalid username or password.');
        }

        return view('auth/login');
    }

    /**
     * POST /logout
     */
    public function logout()
    {
        session()->destroy();
        return redirect()->to(site_url('login'))
            ->with('flash_success', 'You have been logged out.');
    }
}
