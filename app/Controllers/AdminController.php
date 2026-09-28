<?php

namespace App\Controllers;

class AdminController extends BaseController
{
    /**
     * Memaparkan Halaman Tetapan Suis Admin
     */
    public function settings()
    {
        // Sekatan keselamatan: Pastikan hanya Admin (Role 3) sahaja boleh masuk menu ini
        if ((int)session()->get('role') !== 3) {
            return redirect()->to(base_url('/'))->with('error', 'Access Denied: Administrative matrix clearance required.');
        }

        $db = \Config\Database::connect();

        // Ambil status semasa dari pangkalan data
        $modeRow = $db->table('system_settings')->where('setting_key', 'maintenance_mode')->get()->getRow();
        $reasonRow = $db->table('system_settings')->where('setting_key', 'maintenance_reason')->get()->getRow();

        $data['maintenance_active'] = $modeRow ? (int)$modeRow->setting_value : 0;
        $data['current_reason'] = $reasonRow ? $reasonRow->setting_value : '';

        return view('admin/settings', $data);
    }

    /**
     * Memproses Ambilan Data Dari Borang Dan Simpan Ke Database
     */
    public function saveSettings()
    {
        if ((int)session()->get('role') !== 3) {
            return redirect()->to(base_url('/'))->with('error', 'Unauthorized operation attempted.');
        }

        $db = \Config\Database::connect();

        // Sifat checkbox Bootstrap Switch: Jika tak di-tick, ia tidak dihantar dalam POST. Jadi kita defaultkan ke '0'
        $modeValue = $this->request->getPost('maintenance_mode') ?? '0';
        $reasonValue = $this->request->getPost('maintenance_reason') ?: 'The system is currently undergoing scheduled maintenance optimization.';

        // Kemaskini rekod dalam database
        $db->table('system_settings')->where('setting_key', 'maintenance_mode')->update(['setting_value' => $modeValue]);
        $db->table('system_settings')->where('setting_key', 'maintenance_reason')->update(['setting_value' => $reasonValue]);

        return redirect()->back()->with('success', 'Global system parameters synchronized successfully.');
    }
}
