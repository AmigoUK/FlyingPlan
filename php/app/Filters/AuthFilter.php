<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Authentication filter. Redirects to /login if not authenticated.
 * Optionally checks minimum role level.
 *
 * Usage in Routes.php:
 *   $routes->group('admin', ['filter' => 'auth:manager'], function ($routes) { ... });
 *   $routes->group('pilot', ['filter' => 'auth:pilot'], function ($routes) { ... });
 */
class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        if (!$session->get('logged_in')) {
            return redirect()->to('/login');
        }

        // Check minimum role if specified
        if (!empty($arguments)) {
            $minimumRole = $arguments[0];
            $roleRank = ['pilot' => 0, 'manager' => 1, 'admin' => 2];
            $userRank = $roleRank[$session->get('role')] ?? -1;
            $minRank = $roleRank[$minimumRole] ?? 999;

            if ($userRank < $minRank) {
                return service('response')->setStatusCode(403, 'Forbidden');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
