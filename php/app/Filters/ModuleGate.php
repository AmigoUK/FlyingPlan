<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Before filter that checks if a module is enabled.
 * Usage in Routes.php: 'filter' => 'module:planning'
 */
class ModuleGate implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (empty($arguments)) return;

        $module = $arguments[0];
        $settings = (new \App\Models\AppSettingsModel())->getSettings();
        $modules = json_decode($settings->modules_json ?? '{}', true) ?: [];

        if (empty($modules[$module])) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed
    }
}
