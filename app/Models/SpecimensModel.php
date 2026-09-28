<?php

namespace App\Models;

use CodeIgniter\Model;

class SpecimensModel extends Model
{
    protected $table = 'specimens';
    protected $primaryKey = 'specimens_id';

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = ''; // disable updated_at

    protected $allowedFields = [
        'specimens_code',
        'container_id',
        'units_id',
        'slot_no',
        'created_by',
        'request_to_dispose_at',
        'request_to_dispose_by',
        'status',
        'disposed_at',
        'disposed_by',
        'cancelled_by', //baru tambah untuk report cancelled
        'remark'
    ];

    /**
     * Get available slots for a container
     * Returns array of slots with disabled flag
     */
    public function getSlotsByContainer($container_id, $maxSlots = 100)
    {
        // Get all active specimens in this container
        $usedSlots = $this->select('slot_no')
                          ->where('container_id', $container_id)
                          ->where('status', 1) // only active specimens
                          ->findAll();

        // Map used slots for quick lookup
        $usedMap = array_column($usedSlots, 'slot_no', 'slot_no');

        $slots = [];
        for ($i = 1; $i <= $maxSlots; $i++) {
            $slots[] = [
                'slot_no'  => $i,
                'display'  => "Slot {$i}",
                'disabled' => isset($usedMap[$i])
            ];
        }

        return [
            'used'  => count($usedMap),
            'max'   => $maxSlots,
            'slots' => $slots
        ];
    }

    /**
     * Count specimens registered today in a container
     */
    public function countTodayByContainer($container_id)
    {
        return $this->where('container_id', $container_id)
                    ->where('status', 1)
                    ->where('DATE(created_at)', date('Y-m-d'))
                    ->countAllResults();
    }

    /**
     * Check if a slot is occupied
     */
    public function isSlotOccupied($container_id, $slot_no)
    {
        return $this->where('container_id', $container_id)
                    ->where('slot_no', $slot_no)
                    ->where('status', 1)
                    ->countAllResults() > 0;
    }

    /**
     * Check if a specimen code is already active
     */
    public function isCodeActive($specimens_code)
    {
        return $this->where('specimens_code', $specimens_code)
                    ->where('status', 1)
                    ->countAllResults() > 0;
    }
}
