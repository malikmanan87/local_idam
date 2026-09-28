<?php

namespace App\Controllers;

use App\Models\UnitsModel;

class UserController extends BaseController
{
    public function newUser()
    {
        $unitsModel = new UnitsModel();

        $data['units'] = $unitsModel
            ->orderBy('units_name', 'ASC')
            ->findAll();

        return view('forms/newuser', $data);
    }

    public function submitNewuser()
    {
        // 1. Determine the source of the email
        // Priority: Session > Post Input
        $email = session()->get('email') ?? $this->request->getPost('email');

        // 2. Adjust Validation Rules
        // We only require the 'email' field validation if it's NOT in the session
        $rules = [
            'unit_id'          => 'required|integer',
            'reason_to_access' => 'required|min_length[5]|max_length[500]',
        ];

        if (!session()->has('email')) {
            $rules['email'] = 'required|valid_email|max_length[255]';
        }

        // 3. Validate
        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode('<br>', $this->validator->getErrors()));
        }

        // 4. Check for Existing User
        $usersModel = new \App\Models\UsersModel();
        $existingUser = $usersModel->where('email', $email)->first();

        if ($existingUser) {
            // If status is 9 (Rejected)
            if ($existingUser['status'] == 9) {
                // Fetch the remark, providing a fallback if it's empty
                $rejectReason = !empty($existingUser['remark'])
                    ? $existingUser['remark']
                    : 'No specific reason was provided by the administrator.';

                return redirect()->back()
                    ->withInput()
                    ->with('rejected_msg', $rejectReason);
            }

            // Default error for status 0 (Pending) or status 1 (Active)
            return redirect()->back()
                ->withInput()
                ->with('error', 'This email is already registered or has a pending request.');
        }
        // 5. Insert
        $usersModel->insert([
            'unit_id'          => $this->request->getPost('unit_id'),
            'email'            => $email,
            'role'             => 0,
            'status'           => 0,
            'reason_to_access' => $this->request->getPost('reason_to_access'),
        ]);

        return redirect()->back()->with('success', 'Your access request has been submitted.');
    }
}
