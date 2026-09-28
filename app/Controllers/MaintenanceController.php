<?php

namespace App\Controllers;

use App\Models\StoragesModel;
use App\Models\ContainersModel;
use App\Models\UnitsModel;

class MaintenanceController extends BaseController
{
    protected $storagesModel;
    protected $containersModel;
    protected $unitsModel;

    public function __construct()
    {
        $this->storagesModel   = new StoragesModel();
        $this->containersModel = new ContainersModel();
        $this->unitsModel      = new UnitsModel();
    }

    // ------------------- VIEW -------------------
    public function index()
    {
        $data['units'] = $this->unitsModel->orderBy('units_code', 'ASC')->findAll();
        $data['storages'] = $this->storagesModel->orderBy('units_code', 'ASC')->findAll();

        // Containers with storage info
        $builder = $this->containersModel
            ->select('containers.*, storages.storage_name, storages.units_code')
            ->join('storages', 'containers.storage_id = storages.storage_id', 'left')
            ->orderBy('storages.units_code')
            ->orderBy('containers.container_number')
            ->get();

        $data['containers'] = $builder->getResultArray();

        return view('tables/maintenance', $data);
    }

    // ------------------- UPDATE STATUS -------------------
    public function updateStatus($id)
    {
        $status = (int) $this->request->getPost('status');
        $type   = $this->request->getPost('type');
        $remark = $this->request->getPost('remark') ?? '';

        switch ($type) {
            case 'unit':
            case 'storage':
            case 'container':
                if (!in_array($status, [1, 2, 3])) {
                    return $this->response->setJSON(['success' => false, 'message' => 'Invalid status option']);
                }

                $data = ['status' => $status];
                if ($status != 1 && $remark) $data['remark'] = $remark;

                if ($type === 'unit') $this->unitsModel->update($id, $data);
                elseif ($type === 'storage') $this->storagesModel->update($id, $data);
                else $this->containersModel->update($id, $data);
                break;

            default:
                return $this->response->setJSON(['success' => false, 'message' => 'Unknown category type']);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Status modified successfully']);
    }

    // ------------------- CREATE NEW UNIT -------------------
    public function createUnit()
    {
        $units_code = strtoupper($this->request->getPost('units_code'));
        $units_name = $this->request->getPost('units_name');
        $status     = (int) $this->request->getPost('status');
        $remark     = $this->request->getPost('remark') ?? '';

        if (empty($units_code)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Code is required']);
        }
        if (empty($units_name)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Name is required']);
        }
        if (!in_array($status, [1, 2])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid status setup configuration']);
        }

        // Check for duplicate unit code
        $exists = $this->unitsModel->where('units_code', $units_code)->first();
        if ($exists) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Code already exists']);
        }

        $data = [
            'units_code' => $units_code,
            'units_name' => $units_name,
            'status'     => $status,
            'remark'     => $remark
        ];

        if ($this->unitsModel->insert($data)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Unit created successfully']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to create unit workspace']);
        }
    }

    // ------------------- UPDATE EXISTING UNIT -------------------
    public function updateUnit($id)
    {
        $id = (int) $id;
        $unit = $this->unitsModel->find($id);

        if (!$unit) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit not found']);
        }

        $units_code = strtoupper(trim($this->request->getPost('units_code') ?? ''));
        $units_name = trim($this->request->getPost('units_name') ?? '');
        $status     = (int) $this->request->getPost('status');
        $remark     = trim($this->request->getPost('remark') ?? '');

        if (empty($units_code)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Code is required']);
        }
        if (empty($units_name)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Name is required']);
        }
        if (!in_array($status, [1, 2])) {
            $status = 1;
        }

        // Semak jika Unit Code bertindih dengan rekod unit yang lain
        $exists = $this->unitsModel
            ->where('units_code', $units_code)
            ->where('units_id !=', $id)
            ->first();

        if ($exists) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Code already exists in another unit']);
        }

        $oldUnitsCode = $unit['units_code'];

        $data = [
            'units_code' => $units_code,
            'units_name' => $units_name,
            'status'     => $status,
            'remark'     => $remark
        ];

        if ($this->unitsModel->update($id, $data)) {
            // Jika kod unit berubah, selaraskan units_code dalam jadual storages berkaitan
            if ($oldUnitsCode !== $units_code) {
                $db = \Config\Database::connect();
                $db->table('storages')
                    ->where('units_code', $oldUnitsCode)
                    ->update(['units_code' => $units_code]);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Unit updated successfully']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to update unit']);
        }
    }

    // ------------------- CREATE NEW STORAGE (FREETEXT COMPATIBLE) -------------------
    public function createStorage()
    {
        $units_code   = $this->request->getPost('units_code');
        $duration_id  = (int) $this->request->getPost('duration_id');
        $storage_type = $this->request->getPost('storage_type');

        // 🌟 FIX 1: Ambil nama kustom (freetext) yang ditaip oleh pengguna dari input borang
        $storage_name = trim($this->request->getPost('storage_name') ?? '');
        $remark       = $this->request->getPost('remark') ?? '';

        if (!$units_code) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Code required']);
        }

        $unit = $this->unitsModel->where('units_code', $units_code)->first();
        if (!$unit) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid Unit Code']);
        }

        if (!in_array($duration_id, [1, 2, 3])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid duration parameters']);
        }

        if (!in_array($storage_type, ['Chiller', 'Freezer'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid storage type classification']);
        }

        if ($duration_id == 1 && $storage_type !== 'Chiller') {
            return $this->response->setJSON(['success' => false, 'message' => 'Duration 1 must be Chiller']);
        }
        if (in_array($duration_id, [2, 3]) && $storage_type !== 'Freezer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Duration 2/3 must be Freezer']);
        }

        // 🌟 FIX 2: Validasi bagi memastikan kotak teks freetext tidak dihantar kosong
        if (empty($storage_name)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Storage Identifier Title cannot be empty']);
        }

        // 🌟 FIX 3: Semak pendua menggunakan nilai freetext kustom yang dimasukkan pengguna
        $exists = $this->storagesModel
            ->where('units_code', $units_code)
            ->where('storage_name', $storage_name)
            ->first();

        if ($exists) {
            return $this->response->setJSON(['success' => false, 'message' => 'Storage profile title already exists in this unit']);
        }

        $data = [
            'units_code'   => $units_code,
            'duration_id'  => $duration_id,
            'storage_type' => $storage_type,
            'storage_name' => $storage_name, // Simpan teks kustom pengguna ke dalam database
            'remark'       => $remark,
            'status'       => 1
        ];

        $this->storagesModel->insert($data);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Storage configuration created successfully'
        ]);
    }

    // ------------------- UPDATE EXISTING STORAGE -------------------
    public function updateStorage($id)
    {
        $id = (int) $id;
        $storage = $this->storagesModel->find($id);

        if (!$storage) {
            return $this->response->setJSON(['success' => false, 'message' => 'Storage not found']);
        }

        $units_code   = $this->request->getPost('units_code');
        $duration_id  = (int) $this->request->getPost('duration_id');
        $storage_type = $this->request->getPost('storage_type');
        $storage_name = trim($this->request->getPost('storage_name') ?? '');
        $status       = (int) $this->request->getPost('status');
        $remark       = trim($this->request->getPost('remark') ?? '');

        if (!$units_code) {
            return $this->response->setJSON(['success' => false, 'message' => 'Unit Code is required']);
        }

        $unit = $this->unitsModel->where('units_code', $units_code)->first();
        if (!$unit) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid Unit Code']);
        }

        if (!in_array($duration_id, [1, 2, 3])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid duration parameters']);
        }

        if (!in_array($storage_type, ['Chiller', 'Freezer'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid storage type classification']);
        }

        if ($duration_id == 1 && $storage_type !== 'Chiller') {
            return $this->response->setJSON(['success' => false, 'message' => 'Duration 1 must be Chiller']);
        }
        if (in_array($duration_id, [2, 3]) && $storage_type !== 'Freezer') {
            return $this->response->setJSON(['success' => false, 'message' => 'Duration 2/3 must be Freezer']);
        }

        if (empty($storage_name)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Storage Identifier Title cannot be empty']);
        }

        if (!in_array($status, [1, 2, 3])) {
            $status = 1;
        }

        // Semak pendua menggunakan nilai freetext kustom yang dimasukkan pengguna (kecuali rekod semasa)
        $exists = $this->storagesModel
            ->where('units_code', $units_code)
            ->where('storage_name', $storage_name)
            ->where('storage_id !=', $id)
            ->first();

        if ($exists) {
            return $this->response->setJSON(['success' => false, 'message' => 'Storage profile title already exists in this unit']);
        }

        $data = [
            'units_id'     => $unit['units_id'],
            'units_code'   => $units_code,
            'duration_id'  => $duration_id,
            'storage_type' => $storage_type,
            'storage_name' => $storage_name,
            'status'       => $status,
            'remark'       => $remark
        ];

        if ($this->storagesModel->update($id, $data)) {
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Storage configuration updated successfully'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to update storage configuration'
            ]);
        }
    }

    /* ===================== UPDATE CONTAINER ===================== */
    public function updateContainer($id)
    {
        $id        = (int) $id;
        $container = $this->containersModel->find($id);

        if (!$container) {
            return $this->response->setJSON(['success' => false, 'message' => 'Container not found.']);
        }

        $number     = (int) $this->request->getPost('container_number');
        $label      = trim($this->request->getPost('container_label') ?? '');
        $day        = (int) $this->request->getPost('container_day');
        $durationId = (int) $this->request->getPost('duration_id');
        $status     = (int) $this->request->getPost('status');
        $remark     = trim($this->request->getPost('remark') ?? '');

        if ($number < 1) {
            return $this->response->setJSON(['success' => false, 'message' => 'Container number must be at least 1.']);
        }
        if (empty($label)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Container label is required.']);
        }
        if (!in_array($durationId, [1, 2, 3])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid duration.']);
        }
        if (!in_array($status, [1, 2, 3])) {
            $status = 1;
        }
        if (!in_array($day, [0, 1, 2, 3, 4, 5, 6])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid day selection.']);
        }

        // Semak pendua (container_number + container_day + storage_id) kecuali rekod semasa
        $duplicate = $this->containersModel
            ->where('storage_id',       $container['storage_id'])
            ->where('container_number', $number)
            ->where('container_day',    $day)
            ->where('container_id !=',  $id)
            ->first();

        if ($duplicate) {
            $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return $this->response->setJSON([
                'success' => false,
                'message' => "Container No. {$number} already exists for " . ($dayNames[$day] ?? 'this day') . " in this storage."
            ]);
        }

        $updated = $this->containersModel->update($id, [
            'container_number' => $number,
            'container_label'  => $label,
            'container_day'    => $day,
            'duration_id'      => $durationId,
            'status'           => $status,
            'remark'           => $remark,
        ]);

        if ($updated) {
            return $this->response->setJSON(['success' => true, 'message' => 'Container updated successfully.']);
        }

        return $this->response->setJSON(['success' => false, 'message' => 'Failed to update container.']);
    }

    /* ===================== CREATE CONTAINER (MULTI-DAY INSERT SUPPORT) ===================== */
    public function createContainer()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized entry format sequence context.'
            ]);
        }

        $storage_id       = $this->request->getPost('storage_id');
        $duration_id      = $this->request->getPost('duration_id');
        $container_number = (int)$this->request->getPost('container_number');
        $status           = (int)$this->request->getPost('status');
        $remark           = $this->request->getPost('remark');
        $days             = $this->request->getPost('container_day');

        if (!$storage_id)       return $this->response->setJSON(['success' => false, 'message' => 'Storage is required']);
        if (!$duration_id)      return $this->response->setJSON(['success' => false, 'message' => 'Duration parameter missing']);
        if (!$container_number) return $this->response->setJSON(['success' => false, 'message' => 'Container number tracker missing']);
        if (empty($days) || !is_array($days)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Select at least one workspace active schedule day']);
        }

        $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $db = \Config\Database::connect();

        // Comprehensive pre-check logic layout ensuring duplicate slots are blocked completely before writes loop starts
        foreach ($days as $day) {
            $duplicateCheck = $this->containersModel
                ->where('storage_id', $storage_id)
                ->where('container_number', $container_number)
                ->where('container_day', (int)$day)
                ->first();

            if ($duplicateCheck) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Container Number {$container_number} already mapped to " . ($dayNames[(int)$day] ?? 'Chosen Day') . " under this device setup."
                ]);
            }
        }

        $db->transBegin();

        try {
            foreach ($days as $day) {
                $dayInt = (int)$day;
                $label = 'No. ' . $container_number . ' - ' . ($dayNames[$dayInt] ?? '');

                $this->containersModel->insert([
                    'storage_id'       => $storage_id,
                    'duration_id'      => $duration_id,
                    'container_number' => $container_number,
                    'container_label'  => $label,
                    'container_day'    => $dayInt,
                    'status'           => $status,
                    'remark'           => $remark,
                    'created_at'       => date('Y-m-d H:i:s'),
                    'created_by'       => session('username') ?? session('email') ?? 'system'
                ]);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON(['success' => false, 'message' => 'Database transaction failed during generation loops.']);
            }

            $db->transCommit();
            return $this->response->setJSON(['success' => true, 'message' => 'Containers successfully initialized across all target active workspace loop schedules.']);
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[createContainer Engine Failure Block] ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Critical internal data processor failure.'
            ]);
        }
    }

    // ------------------- AJAX ENDPOINTS -------------------

    public function storages($unitCode)
    {
        $storages = $this->storagesModel
            ->where('units_code', $unitCode)
            ->orderBy('storage_name', 'ASC')
            ->findAll();

        return $this->response->setJSON($storages);
    }

    public function containerNumbers($storageId)
    {
        $containers = $this->containersModel
            ->select('DISTINCT(container_number) as container_number')
            ->where('storage_id', $storageId)
            ->orderBy('container_number', 'ASC')
            ->findAll();

        return $this->response->setJSON($containers);
    }

    // Kembalikan nombor container seterusnya (MAX + 1) bagi storage tertentu
    public function nextContainerNumber($storageId)
    {
        $storageId = (int) $storageId;

        $db     = \Config\Database::connect();
        $result = $db->table('containers')
            ->selectMax('container_number', 'max_num')
            ->where('storage_id', $storageId)
            ->get()
            ->getRowArray();

        $next = isset($result['max_num']) && $result['max_num'] !== null
            ? (int)$result['max_num'] + 1
            : 1;

        return $this->response->setJSON(['next' => $next]);
    }

    public function containersToday($storageId, $containerNumber)
    {
        $today = (int) date('w'); // Normalised numeric assignment representing contemporary weekday loop

        $containers = $this->containersModel
            ->where('storage_id', $storageId)
            ->where('container_number', $containerNumber)
            ->where('container_day', $today)
            ->orderBy('container_label', 'ASC')
            ->findAll();

        return $this->response->setJSON($containers);
    }

    public function slots($containerId)
    {
        $maxSlots = 100;

        $usedSlots = $this->containersModel->db->table('specimens')
            ->select('slot_no')
            ->where('container_id', $containerId)
            ->where('status', 1)
            ->get()->getResultArray();

        $used = array_column($usedSlots, 'slot_no');
        $slots = [];
        for ($i = 1; $i <= $maxSlots; $i++) {
            $slots[] = [
                'slot_no' => $i,
                'display' => $i,
                'disabled' => in_array($i, $used)
            ];
        }

        return $this->response->setJSON([
            'used' => count($used),
            'max' => $maxSlots,
            'slots' => $slots
        ]);
    }
}
