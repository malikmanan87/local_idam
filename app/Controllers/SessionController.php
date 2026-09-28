<?php

namespace App\Controllers;

use App\Models\UsersModel;

class SessionController extends BaseController
{
    public function index()
    {
        return view('forms/session');
    }

    public function set()
    {
        $rawInput = trim($this->request->getVar('idpengguna') ?? '');

        if (empty($rawInput)) {
            return redirect()->to(base_url('forms/session'))
                ->with('error', 'Authentication failed: Missing identification token from external provider.');
        }

        $usernamePrefix = str_replace('@unisza.edu.my', '', $rawInput);
        $email = strtolower($usernamePrefix . '@unisza.edu.my');

        $userModel = new UsersModel();
        $user = $userModel->where('email', $email)->first();

        // If NEW USER (Not found in DB) -> Redirect straight to newuser form
        if (!$user) {
            return redirect()->to(base_url('newuser'))
                ->withInput()
                ->with('error', 'Account "' . esc($email) . '" is not registered. Please submit an access request using the form below.');
        }

        // If user exists but is pending/inactive (Status 0) -> Redirect to newuser page
        if ((int)$user['status'] !== 1) {
            return redirect()->to(base_url('newuser'))
                ->with('error', 'Your access request for account "' . esc($email) . '" is currently pending Administrator approval.');
        }

        // Data Normalization Pipeline
        $rawUnitIds = $user['unit_id'] ?? [];
        $normalizedUnitArray = [];

        if (is_array($rawUnitIds)) {
            $normalizedUnitArray = $rawUnitIds;
        } elseif (is_string($rawUnitIds) && strpos($rawUnitIds, ',') !== false) {
            $normalizedUnitArray = explode(',', $rawUnitIds);
        } elseif (!empty($rawUnitIds)) {
            $normalizedUnitArray = [$rawUnitIds];
        }

        $finalSessionUnits = array_map('intval', array_map('trim', $normalizedUnitArray));

        // Set Active Session Storage Parameters
        session()->set([
            'user_id'   => $user['user_id'],
            'email'     => $user['email'],
            'username'  => $usernamePrefix,
            'role'      => (int)$user['role'],
            'unit_id'   => $finalSessionUnits,
            'status'    => (int)$user['status'],
            'logged_in' => true
        ]);

        return redirect()->to(base_url('/'))
            ->with('success', 'Welcome back! Session synchronized successfully.');
    }

    /**
     * Clears all session traces and renders standalone English instructions view
     */
    // public function logoutPage()
    // {
    //     session()->destroy();
    //     return view('forms/logout');
    // }

    public function logoutPage()
    {
        $session = session();
        $session->destroy(); // 🌟 Memusnahkan semua data sesi (role, user_id dll)

        // Kembalikan ke halaman utama, di mana RoleFilter akan automatik menangkap mod maintenance
        // return redirect()->to(base_url('/')); 
        return view('forms/logout');
    }
}
