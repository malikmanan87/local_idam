<?php

namespace App\Models;

use CodeIgniter\Model;

class StoragesModel extends Model
{
    protected $table = 'storages';
    protected $primaryKey = 'storage_id';

    protected $allowedFields = [
        'units_id',
        'units_code',
        'duration_id',
        'storage_type',
        'storage_name',
        'created_at',
        'created_by',
        'status',
        'remark'
    ];

    public function getByUnit($units_id)
    {
        return $this->where('units_id', $units_id)
            ->where('status', 1)
            ->findAll();
    }
}
