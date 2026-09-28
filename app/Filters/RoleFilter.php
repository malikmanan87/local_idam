<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // 1. Dapatkan path URI semasa
        $uri = trim(service('uri')->getPath(), '/');

        // 2. Senarai laluan awam asas
        $isOpenRoute = url_is('/') ||
            url_is('login') ||
            url_is('newuser') ||
            url_is('newuser/*') ||
            url_is('forms/logout') ||
            url_is('forms/session') ||
            url_is('forms/session/*');

        // =========================================================================
        // 🛠️ SEMAKAN MOD PENYELENGGARAAN GLOBAL (FORCE TO LOGOUT PAGE)
        // =========================================================================
        $db = \Config\Database::connect();
        $maintenance = $db->table('system_settings')->where('setting_key', 'maintenance_mode')->get()->getRow();

        if ($maintenance && (int)$maintenance->setting_value === 1) {
            $userRole = (int)($session->get('role') ?? 0);

            // Sekiranya dia BUKAN Admin (Role 3)
            if ($userRole !== 3) {
                
                // 🌟 JIKA TIADA SESI AKTIF (role = 0 / baru lepas click logout)
                if (!$session->has('user_id') || $userRole === 0) {
                    
                    // Kecualikan laluan 'forms/logout' supaya tidak berlaku loop melencong
                    if (!url_is('forms/logout')) {
                        return redirect()->to(base_url('forms/logout'));
                    }
                    
                    // Jika dia memang sudah berada di 'forms/logout', lepaskan filter di sini!
                    return; 
                }
                
                // 🌟 JIKA DIA MASIH ADA SESI (User/Superuser yang tengah aktif guna sistem)
                // Kita sekat dan paparkan skrin amaran maintenance
                $maintenanceAllowed = ['forms/logout', 'forms/session', 'forms/session/set'];
                if (!in_array($uri, $maintenanceAllowed, true)) {
                    $reasonRow = $db->table('system_settings')->where('setting_key', 'maintenance_reason')->get()->getRow();
                    $data['reason'] = $reasonRow ? $reasonRow->setting_value : 'The system is currently undergoing scheduled maintenance optimization.';

                    $response = service('response');
                    $response->setStatusCode(503);
                    $response->setBody(view('errors/html/production_maintenance', $data));
                    return $response;
                }
            }
        }
        // =========================================================================

        // 3. Jika laluan semasa adalah laluan awam (Mod Biasa), lepaskan terus
        if ($isOpenRoute) {
            return;
        }

        // 4. Sekatan jika tiada sesi aktif (Mod Biasa) -> Hantar ke halaman newuser
        if (!$session->has('user_id')) {
            return redirect()->to(base_url('newuser'));
        }

        // 5. Sekatan jika akaun masih pending (Status 0)
        if ((int)$session->get('status') === 0) {
            return redirect()->to(base_url('newuser'))
                ->with('error', 'Your access request has already been submitted and is currently awaiting Administrator approval.');
        }

        // 6. Penguatkuasaan sekatan peranan berasaskan argumen routes
        if ($arguments) {
            $role = (int)$session->get('role');
            if (!in_array($role, array_map('intval', $arguments))) {
                return redirect()->to(base_url('/'))->with('error', 'Access Denied: You do not possess the required security clearance for that workstation.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Disregard
    }
}