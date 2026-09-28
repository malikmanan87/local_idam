<?php

namespace App\Controllers;

use App\Models\UsersModel;
use App\Models\UnitsModel; // Loaded to fetch the master units list

class RoleController extends BaseController
{
    public function index()
    {
        $usersModel = new UsersModel();
        $unitsModel = new UnitsModel(); // Instantiated the units model

        // Fetch all active units to populate the dropdowns/checkboxes in the view
        $allUnits = $unitsModel->orderBy('units_name', 'ASC')->findAll();

        // New Users (status 0)
        $newUsers = $usersModel
            ->select('users.*, units.units_name')
            ->join('units', 'users.unit_id = units.units_id', 'left')
            ->where('users.status', 0)
            ->findAll();

        // Active / Inactive Users (status 1,2)
        $activeUsers = $usersModel
            ->select('users.*, units.units_name')
            ->join('units', 'users.unit_id = units.units_id', 'left')
            ->whereIn('users.status', [1, 2])
            ->findAll();

        // Rejected Users (status 9)
        $rejectedUsers = $usersModel
            ->select('users.*, units.units_name')
            ->join('units', 'users.unit_id = units.units_id', 'left')
            ->where('users.status', 9)
            ->findAll();

        // Passed the $allUnits array down to the view template context
        return view('tables/role', compact('newUsers', 'activeUsers', 'rejectedUsers', 'allUnits'));
    }

    // Approve user
    public function approve($userId)
    {
        $usersModel = new UsersModel();

        $user = $usersModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        $updated = $usersModel->update($userId, [
            'status' => 1,
            'role'   => 1 // default User
        ]);

        return $this->response->setJSON([
            'success' => (bool)$updated,
            'message' => $updated ? 'User approved successfully' : 'Approve failed'
        ]);
    }

    // Reject user with reason
    public function reject($userId)
    {
        $usersModel = new UsersModel();

        $user = $usersModel->find($userId);
        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        // Get reason from POST
        $remark = $this->request->getPost('remark');
        if (!$remark) {
            return $this->response->setJSON(['success' => false, 'message' => 'Reason is required']);
        }

        $updated = $usersModel->update($userId, [
            'status' => 9,       // rejected
            'remark' => $remark  // store reason
        ]);

        return $this->response->setJSON([
            'success' => (bool)$updated,
            'message' => $updated ? 'User rejected successfully' : 'Reject failed',
            'remark'  => $remark
        ]);
    }

    // Change role
    public function changeRole($userId, $newRole)
    {
        $validRoles = [1, 2, 3]; // User, PIC, Admin
        if (!in_array($newRole, $validRoles)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid role']);
        }

        $usersModel = new UsersModel();
        $user = $usersModel->find($userId);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        // Jika menukar peranan kepada Admin (3), kosongkan unit_id secara automatik kerana Admin memegang akses global
        $updateData = ['role' => $newRole];
        if ((int)$newRole === 3) {
            $updateData['unit_id'] = null;
        }

        $usersModel->update($userId, $updateData);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Role updated successfully'
        ]);
    }

    // Change status
    public function changeStatus($userId, $newStatus)
    {
        $validStatuses = [1, 2]; // Active, Inactive
        if (!in_array($newStatus, $validStatuses)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid status']);
        }

        $usersModel = new UsersModel();
        $user = $usersModel->find($userId);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User not found']);
        }

        $usersModel->update($userId, ['status' => $newStatus]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Status updated successfully'
        ]);
    }

    // Restore rejected user
    public function restore($userId)
    {
        $usersModel = new UsersModel();

        $user = $usersModel->find($userId);
        if (!$user || $user['status'] != 9) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid user']);
        }

        $usersModel->update($userId, [
            'status' => 0 // back to New Users
        ]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'User restored to New Users'
        ]);
    }

    /**
     * Asynchronously updates the user's mapped workspace unit assignment (Single unit legacy fallback)
     */
    public function changeUnit($userId, $unitId)
    {
        $usersModel = new UsersModel();
        $user = $usersModel->find($userId);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'User profile not found.']);
        }

        $usersModel->update($userId, ['unit_id' => (int)$unitId]);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Laboratory unit assignment modified successfully.'
        ]);
    }

    /**
     * BARU: Memproses kemasukan pelbagai unit makmal daripada tatanan checkbox modal (POST)
     */
    public function changeUnitMultiple()
    {
        $userId = $this->request->getPost('user_id');
        $unitIdsArray = $this->request->getPost('unit_ids') ?? [];

        if (!$userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'User context identifier is required.']);
        }

        $usersModel = new UsersModel();
        $user = $usersModel->find($userId);

        if (!$user) {
            return $this->response->setJSON(['success' => false, 'message' => 'Target user account profile not found.']);
        }

        if ((int)$user['role'] === 3) {
            $unitIdString = null;
        } else {
            $unitIdString = !empty($unitIdsArray) 
                ? implode(',', array_filter(array_map('intval', $unitIdsArray))) 
                : null;
        }

        $updated = $usersModel->update($userId, [
            'unit_id' => $unitIdString
        ]);

        if ($updated) {
            // ============================================================
            // PEMBAIKAN: Jika Admin sedang menukar unit akaun dirinya sendiri,
            // kemaskini sesi token pelayar secara live serta-merta!
            // ============================================================
            if ((int)session()->get('user_id') === (int)$userId) {
                session()->set('unit_id', $unitIdString);
            }
        }

        return $this->response->setJSON([
            'success' => (bool)$updated,
            'message' => $updated ? 'User multi-unit matrix alignment successfully updated.' : 'Failed to update.'
        ]);
    }
}