<?php

namespace App\Models;

use CodeIgniter\Model;

class UsersModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'user_id';

    protected $allowedFields = [
        'unit_id',
        'email',
        'role',
        'status',
        'reason_to_access',
        'created_at',
        'remark'
    ];

    protected $useTimestamps = false;
}
