<?php

namespace App\Models;

use CodeIgniter\Model;

class UnitsModel extends Model
{
    protected $table = 'units';
    protected $primaryKey = 'units_id';
    protected $allowedFields = ['units_name','units_code', 'status', 'remark'];
}
