<?php

namespace App\Models;

use CodeIgniter\Model;

class ContainersModel extends Model
{
    protected $table      = 'containers';
    protected $primaryKey = 'container_id';

    protected $useAutoIncrement = true;
    protected $returnType = 'array';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $allowedFields = [
        'storage_id',
        'duration_id',
        'container_number',   // added for hierarchy
        'container_label',
        'container_day',      // 0=Sun ... 6=Sat
        'created_by',
        'status',
        'remark'
    ];

    /**
     * Get all containers by storage (active only)
     */
    public function getByStorage($storage_id)
    {
        return $this->where('storage_id', $storage_id)
            ->where('status', 1)
            ->orderBy('container_number', 'ASC')
            ->orderBy('container_label', 'ASC')
            ->findAll();
    }

    /**
     * Get distinct container numbers for a storage
     */
    public function getContainerNumbersByStorage($storage_id)
    {
        return $this->select('DISTINCT container_number')
            ->where('storage_id', $storage_id)
            ->where('status', 1)
            ->orderBy('container_number', 'ASC')
            ->findAll();
    }

    /**
     * Get container labels for a given storage + container number
     * Optionally filter by today only
     */
    public function getContainerLabels($storage_id, $container_number, $todayOnly = true)
    {
        $builder = $this->where('storage_id', $storage_id)
            ->where('container_number', $container_number)
            ->where('status', 1);

        if ($todayOnly) {
            $builder->where('container_day', date('w')); // 0=Sun ... 6=Sat
        }

        return $builder->orderBy('container_label', 'ASC')->findAll();
    }
}
