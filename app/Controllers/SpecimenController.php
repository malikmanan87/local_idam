<?php

namespace App\Controllers;

use App\Models\UnitsModel;
use App\Models\StoragesModel;
use App\Models\ContainersModel;
use App\Models\SpecimensModel;

class SpecimenController extends BaseController
{
    protected $maxSlots = 100;

    // Unit/Specimen statuses
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE   = 1;
    const STATUS_DISPOSED = 2;

    /**
     * DISPLAY BULK REGISTRATION WORKSPACE & WORKSPACE REGISTRY HISTORY
     */
    public function index()
    {
        $unitsModel = new \App\Models\UnitsModel();
        $specimensModel = new \App\Models\SpecimensModel();

        // Mengambil data senarai unit untuk paparan butang pilihan di borang atas
        $data['units'] = $unitsModel->orderBy('units_name', 'ASC')->findAll();

        // ============================================================
        // ROLE-BASED SESSION FILTERING & ACCESSIBILITY MATRIX
        // ============================================================
        $role = (int) session()->get('role');
        $rawSessionUnits = session()->get('unit_id') ?? [];

        // Normalisasi data unit_id dari sesi kepada bentuk Array Integer yang bersih
        if (is_string($rawSessionUnits)) {
            $sessionUnitIds = explode(',', $rawSessionUnits);
        } elseif (is_numeric($rawSessionUnits)) {
            $sessionUnitIds = [(int)$rawSessionUnits];
        } else {
            $sessionUnitIds = (array)$rawSessionUnits;
        }
        $sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));

        // Membina Query Asas bagi Pautan Sejarah Pendaftaran (48 Jam Terakhir)
        $query = $specimensModel
            ->select('specimens.*, units.units_name, containers.container_label, storages.storage_name')
            ->join('units', 'specimens.units_id = units.units_id', 'left')
            ->join('containers', 'specimens.container_id = containers.container_id', 'left')
            ->join('storages', 'containers.storage_id = storages.storage_id', 'left')
            ->where('specimens.created_at >=', date('Y-m-d H:i:s', strtotime('-48 hours')));

        // SEKATAN KESELAMATAN: Jika BUKAN Superuser (2) atau Admin (3), tapis data ikut skop unit pengguna sahaja
        if ($role !== 2 && $role !== 3) {
            if (!empty($sessionUnitIds)) {
                $query->whereIn('specimens.units_id', $sessionUnitIds);
            } else {
                // Skenario sandaran keselamatan: Sekiranya data sesi tidak sah, sekat semua rekod (pulangkan kosong)
                $query->where('specimens.units_id', 0);
            }
        }

        // Susun hasil akhir: Spesimen paling baru didaftarkan akan sentiasa berada di baris atas sekali (DESC)
        $data['recentHistory'] = $query->orderBy('specimens.created_at', 'DESC')->findAll();

        // Pulangkan data lengkap ke paparan View bulk_registration
        return view('forms/bulk_registration', $data);
    }

    // -----------------------------
    // AJAX: storages by unit
    // -----------------------------
    public function getStorages($units_code)
    {
        $unit = (new UnitsModel())->where('units_code', $units_code)->first();

        if (!$unit) {
            return $this->response->setJSON([]);
        }

        $storages = (new StoragesModel())
            ->where('units_code', $units_code)
            ->findAll();

        return $this->response->setJSON($storages);
    }

    // -----------------------------
    // AJAX: container numbers by storage
    // -----------------------------
    public function getContainerNumbers($storage_id)
    {
        $db = \Config\Database::connect();

        // GROUP BY container_number — pastikan setiap nombor box muncul sekali sahaja
        // Hanya container AKTIF (status=1) yang boleh digunakan untuk pendaftaran
        $numbers = $db->table('containers')
            ->select('container_number, MAX(status) as status')
            ->where('storage_id', (int)$storage_id)
            ->where('status', 1) // ← Hanya container aktif
            ->groupBy('container_number')
            ->orderBy('container_number', 'ASC')
            ->get()
            ->getResultArray();

        return $this->response->setJSON($numbers);
    }


    // -----------------------------
    // AJAX: today-only containers by storage + number (FIXED FOR ALL ROLES)
    // -----------------------------
    public function getTodayContainers($storage_id, $container_number)
    {
        $today = (int) date('w');
        $containersModel = new ContainersModel();

        $containers = $containersModel
            ->select('container_id, container_label, container_day, status')
            ->where('storage_id', $storage_id)
            ->where('container_number', $container_number)
            ->where('container_day', $today)
            ->whereIn('status', [1, 2, 3]) // Allowing all statuses so dynamic selector chain triggers safely
            ->orderBy('container_id', 'ASC')
            ->findAll();

        return $this->response->setJSON($containers);
    }

    // -----------------------------
    // AJAX: specimen slots
    // -----------------------------
    public function getSpecimens($container_id)
    {
        $model = new SpecimensModel();

        $usedSlots = $model->select('slot_no')
            ->where('container_id', $container_id)
            ->where('status', self::STATUS_ACTIVE)
            ->findAll();

        $usedMap = array_column($usedSlots, 'slot_no', 'slot_no');

        $slots = [];
        for ($i = 1; $i <= $this->maxSlots; $i++) {
            $slots[] = [
                'slot_no' => $i,
                'display' => "Slot {$i}",
                'disabled' => isset($usedMap[$i])
            ];
        }

        return $this->response->setJSON([
            'used'  => count($usedMap),
            'max'   => $this->maxSlots,
            'slots' => $slots
        ]);
    }

    // -----------------------------
    // Save specimen
    // -----------------------------
    public function save()
    {
        $model = new SpecimensModel();
        $data  = $this->request->getPost();

        // Validation
        $rules = [
            'specimens_code' => 'required|min_length[3]|max_length[50]',
            'unit_id'        => 'required|is_natural_no_zero',
            'container_id'   => 'required|is_natural_no_zero',
            'slot_no'        => 'required|is_natural_no_zero'
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->with('error', implode(', ', $this->validator->getErrors()));
        }

        // Ensure container exists and is for today
        $container = (new ContainersModel())->find($data['container_id']);
        if (! $container) {
            return redirect()->back()->with('error', 'Invalid container selected.');
        }

        $today = (int) date('w');
        if ((int)$container['container_day'] !== $today) {
            return redirect()->back()->with('error', 'Selected container is not valid for today.');
        }

        // Check if specimen code already active
        $activeCount = $model
            ->where('specimens_code', $data['specimens_code'])
            ->where('status', self::STATUS_ACTIVE)
            ->countAllResults();
        if ($activeCount > 0) {
            return redirect()->back()->with('error', 'Specimen code already exists and is active.');
        }

        // Check if slot occupied
        $slotOccupied = $model
            ->where('container_id', $data['container_id'])
            ->where('slot_no', $data['slot_no'])
            ->where('status', self::STATUS_ACTIVE)
            ->countAllResults();
        if ($slotOccupied > 0) {
            return redirect()->back()->with('error', 'Slot already occupied.');
        }

        // Insert specimen
        $model->insert([
            'specimens_code' => $data['specimens_code'],
            'container_id'   => $data['container_id'],
            'slot_no'        => $data['slot_no'],
            'units_id'       => $data['unit_id'],
            'status'         => self::STATUS_ACTIVE,
            'created_by'     => session()->get('email') ?? 'system'
        ]);

        return redirect()->back()->with('success', 'Specimen registered successfully.');
    }

    /**
     * PROCESS HIGH-VOLUME SEQUENTIAL BULK REGISTRATION 
     */
    public function saveBulk()
    {
        $model = new SpecimensModel();

        $unitId = $this->request->getPost('unit_id');
        $containerId = $this->request->getPost('container_id');
        $rawCodesText = $this->request->getPost('specimens_codes') ?? '';

        if (!$unitId || !$containerId || empty(trim($rawCodesText))) {
            return redirect()->back()->with('error', 'Required bulk parameters or code inputs are missing.');
        }

        // Explode codes line-by-line, trim spaces, drop blanks, enforce uniqueness
        $codes = array_values(array_unique(array_filter(array_map('trim', explode("\n", $rawCodesText)), 'strlen')));

        if (empty($codes)) {
            return redirect()->back()->with('error', 'No valid specimen strings found.');
        }

        $container = (new ContainersModel())->find($containerId);
        if (!$container) {
            return redirect()->back()->with('error', 'Target container box identifier does not exist.');
        }

        // Semak status container — sekat jika inactive atau under maintenance
        if ((int)$container['status'] !== 1) {
            $statusLabel = (int)$container['status'] === 2 ? 'Inactive' : 'Under Maintenance';
            return redirect()->back()->with('error', "Registration Blocked: Container box is currently [{$statusLabel}] and cannot accept new specimens.");
        }

        // Semak status storage induk — sekat jika inactive atau under maintenance
        $storage = (new StoragesModel())->find($container['storage_id']);
        if (!$storage || (int)$storage['status'] !== 1) {
            $storageLabel = isset($storage['status']) && (int)$storage['status'] === 3 ? 'Under Maintenance' : 'Inactive';
            return redirect()->back()->with('error', "Registration Blocked: Parent storage [{$storage['storage_name']}] is currently [{$storageLabel}] and is locked from new registrations.");
        }

        $today = (int) date('w');
        if ((int)$container['container_day'] !== $today) {
            return redirect()->back()->with('error', 'Selected container box is invalid for today\'s configuration window.');
        }

        // Map consecutive unassigned slots within the target container
        $occupiedSlots = $model->select('slot_no')
            ->where('container_id', $containerId)
            ->where('status', self::STATUS_ACTIVE)
            ->findAll();
        $occupiedMap = array_column($occupiedSlots, 'slot_no', 'slot_no');

        $availableSlots = [];
        for ($slot = 1; $slot <= $this->maxSlots; $slot++) {
            if (!isset($occupiedMap[$slot])) {
                $availableSlots[] = $slot;
            }
        }

        if (count($codes) > count($availableSlots)) {
            return redirect()->back()->withInput()->with('error', 'Batch size (' . count($codes) . ') exceeds available container load spaces (' . count($availableSlots) . ' slots left).');
        }

        // Execute transactions cleanly
        $db = \Config\Database::connect();
        $db->transBegin();

        $successCount = 0;
        $operatorEmail = session()->get('email') ?? 'system';

        try {
            foreach ($codes as $index => $specimenCode) {
                // Safeguard against active duplicates across any unit box
                $isActive = $model->where('specimens_code', $specimenCode)
                    ->where('status', self::STATUS_ACTIVE)
                    ->countAllResults() > 0;

                if ($isActive) {
                    $db->transRollback();
                    return redirect()->back()->withInput()->with('error', 'Registration Aborted: Specimen Code "' . esc($specimenCode) . '" is already active inside a laboratory system rack.');
                }

                $targetSlotNo = $availableSlots[$index];

                $model->insert([
                    'specimens_code' => $specimenCode,
                    'container_id'   => $containerId,
                    'slot_no'        => $targetSlotNo,
                    'units_id'       => $unitId,
                    'status'         => self::STATUS_ACTIVE,
                    'created_by'     => $operatorEmail
                ]);

                $successCount++;
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return redirect()->back()->withInput()->with('error', 'Database layer lock error encountered.');
            }

            $db->transCommit();
            return redirect()->to(base_url('specimen'))->with('success', 'Successfully registered ' . $successCount . ' specimen assets into consecutive coordinates.');
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', '[saveBulk Exception Root Handler] ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'A critical system error occurred during batch writes.');
        }
    }

    /**
     * ASYNCHRONOUS ENGINE: SOFT CANCEL SPECIMEN REGISTRATION (SET STATUS = 0)
     * KEMASKINI: Menggabungkan alasan pembatalan dengan emel pengguna ke dalam lajur remark
     */
    public function cancel()
    {
        // Hanya membenarkan request berbentuk AJAX POST sahaja demi keselamatan
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Direct script access validation failed.'
            ]);
        }

        $specimenCode = $this->request->getPost('code');
        $reason = trim($this->request->getPost('reason') ?? '');

        if (empty($specimenCode) || empty($reason)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Missing target parameters or cancellation logic.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash()
            ]);
        }

        $model = new SpecimensModel();

        // Cari rekod spesimen aktif (status = 1) berdasarkan kod spesimen unik
        $specimen = $model->where('specimens_code', $specimenCode)
            ->where('status', self::STATUS_ACTIVE)
            ->first();

        if (!$specimen) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Specimen not found or already cancelled.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash()
            ]);
        }

        // Ambil email pengguna daripada sesi active
        $fullEmail = session()->get('email') ?? 'system@unisza.edu.my';
        $operatorEmail = explode('@', $fullEmail)[0];

        // 🌟 DAPATKAN TARIKH & MASA SEMASA
        $currentDateTime = date('d/m/Y h:i A'); // Contoh hasil: 06/07/2026 12:46 PM

        // 🌟 GABUNGKAN ALASAN + EMAIL + WAKTU KE DALAM REMARK
        $finalRemark = "Reason : " . $reason . " | Cancel By : " . $operatorEmail . " At " . $currentDateTime;

        $updateData = [
            'cancelled_by' => $operatorEmail, // Rekod siapa yang membatalkan
            'status' => 0,            // Set status kepada inactive (0)
            'remark' => $finalRemark  // Kemaskini ruangan remark dengan format gabungan baharu
        ];

        $updated = $model->update($specimen['specimens_id'], $updateData);

        // Sentiasa pulangkan hash CSRF baharu dalam respons JSON selepas data diproses
        return $this->response->setJSON([
            'success'    => (bool)$updated,
            'message'    => $updated ? 'Registration ledger for "' . esc($specimenCode) . '" successfully set to inactive.' : 'Database infrastructure write fault.',
            'csrf_token' => csrf_token(),
            'csrf_hash'  => csrf_hash()
        ]);
    }

    // -----------------------------
    // Global Specimen Tables View
    // -----------------------------
    public function all_list()
    {
        $db = \Config\Database::connect();

        // 1. Ambil data sekuriti peranan dan unit daripada sesi aktif pelayar
        $userRole = (int)(session()->get('role') ?? 0);
        $rawSessionUnits = session()->get('unit_id') ?? [];

        // Normalisasi data unit daripada string/array kepada tatanan array integer bersih
        if (is_string($rawSessionUnits)) {
            $sessionUnitIds = explode(',', $rawSessionUnits);
        } elseif (is_numeric($rawSessionUnits)) {
            $sessionUnitIds = [(int)$rawSessionUnits];
        } else {
            $sessionUnitIds = (array)$rawSessionUnits;
        }
        $sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));

        $statusMap = [
            0 => ['label' => 'Inactive', 'class' => 'bg-danger'],
            1 => ['label' => 'Active', 'class' => 'bg-success'],
            2 => ['label' => 'Disposed', 'class' => 'bg-secondary'],
        ];

        $tubeColors = [
            1 => '#1E90FF',
            2 => '#FFD700',
            3 => '#FF4500',
        ];

        $defaultColor = '#e9ecef';

        // 2. Query Builder untuk Spesimen Aktif dalam Kotak Kontena (Grid Matrix Visualizer)
        $specimensQuery = $db->table('specimens s')
            ->select([
                's.specimens_id',
                's.specimens_code',
                's.slot_no',
                's.container_id',
                's.units_id',
                's.created_at',
                's.created_by',
                's.status',
                's.remark',
                'c.container_label',
                'st.storage_name',
                'u.units_name',
                'c.duration_id'
            ])
            ->join('containers c', 's.container_id = c.container_id', 'left')
            ->join('storages st', 'c.storage_id = st.storage_id', 'left')
            ->join('units u', 's.units_id = u.units_id', 'left')
            ->where('s.status', self::STATUS_ACTIVE)
            ->where('s.container_id IS NOT NULL')
            ->where('s.slot_no IS NOT NULL')
            ->where('s.specimens_code !=', '');

        // SEKURITI SPESIMEN: Jika BUKAN Superuser/Admin (Role 3), hadkan ikut unit dalam sesi
        if ($userRole !== 3) {
            if (!empty($sessionUnitIds)) {
                $specimensQuery->whereIn('s.units_id', $sessionUnitIds);
            } else {
                $specimensQuery->where('s.units_id', -1); // Paksa kosong jika tiada unit bound pada akaun
            }
        }

        $specimens = $specimensQuery->orderBy('s.created_at', 'DESC')->get()->getResultArray();

        // 3. Query Builder untuk Senarai Kotak Kontena (Containers Grid)
        $containersQuery = $db->table('containers c')
            ->select('c.container_id, c.container_label, c.duration_id, st.storage_name, u.units_name, u.units_code')
            ->join('storages st', 'c.storage_id = st.storage_id', 'left')
            ->join('units u', 'st.units_code = u.units_code', 'left');

        // SEKURITI KONTENA: Diubah ke u.units_id (Membaca alias jadual units yang di-join)
        if ($userRole !== 3) {
            if (!empty($sessionUnitIds)) {
                $containersQuery->whereIn('u.units_id', $sessionUnitIds);
            } else {
                $containersQuery->where('u.units_id', -1);
            }
        }

        $containers = $containersQuery->orderBy('u.units_code', 'ASC')
            ->orderBy('c.container_id', 'ASC')
            ->get()
            ->getResultArray();

        // 4. Pemetaan Kedudukan Slot Aktif & Pengumpulan Kontena Mengikut Unit
        $occupiedMap = [];
        foreach ($specimens as $s) {
            if (!empty($s['container_id']) && !empty($s['slot_no']) && $s['status'] == self::STATUS_ACTIVE) {
                $occupiedMap[$s['container_id']][$s['slot_no']] = $s['duration_id'];
            }
        }

        $containersByUnit = [];
        foreach ($containers as $c) {
            $containersByUnit[$c['units_code']][] = $c;
        }

        // 5. Query Master Units untuk Menu Tab/Dropdown Penapis Atas
        $unitsQuery = $db->table('units')->select('units_code, units_name')->where('status', 1);

        // SEKURITI TAB DROPDOWN: Hadkan tab atas mengikut skop unit milik staf sahaja
        if ($userRole !== 3) {
            if (!empty($sessionUnitIds)) {
                $unitsQuery->whereIn('units_id', $sessionUnitIds);
            } else {
                $unitsQuery->where('units_id', -1);
            }
        }

        $units = $unitsQuery->orderBy('units_code', 'ASC')->get()->getResultArray();

        // 6. Suntikan Label Status, Gaya Bootstrap & Warna Tiub Makmal
        foreach ($specimens as &$s) {
            $s['specimenCode'] = $s['specimens_code'] ?? '-';
            $statusValue = $s['status'] ?? 0;
            $s['status_label'] = $statusMap[$statusValue]['label'] ?? 'Unknown';
            $s['status_class'] = $statusMap[$statusValue]['class'] ?? 'bg-dark';
            $s['tube_color'] = $tubeColors[$s['duration_id'] ?? null] ?? $defaultColor;
        }

        // 7. Pulangkan Data ke Fail Paparan View
        return view('tables/all_list', [
            'allSpecimens'     => $specimens,
            'containersByUnit' => $containersByUnit,
            'occupiedMap'      => $occupiedMap,
            'units'            => $units
        ]);
    }

    // -----------------------------
    // AJAX: Server-Side DataTables for All Specimens
    // -----------------------------
    public function allSpecimensAjax()
    {
        $request = service('request');
        $db = \Config\Database::connect();

        // 1. Ambil data sekuriti peranan dan unit daripada sesi aktif pelayar
        $userRole = (int)(session()->get('role') ?? 0);
        $rawSessionUnits = session()->get('unit_id') ?? [];

        if (is_string($rawSessionUnits)) {
            $sessionUnitIds = explode(',', $rawSessionUnits);
        } elseif (is_numeric($rawSessionUnits)) {
            $sessionUnitIds = [(int)$rawSessionUnits];
        } else {
            $sessionUnitIds = (array)$rawSessionUnits;
        }
        $sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));

        $statusMap = [
            0 => ['label' => 'Inactive', 'class' => 'bg-danger'],
            1 => ['label' => 'Active', 'class' => 'bg-success'],
            2 => ['label' => 'Disposed', 'class' => 'bg-secondary'],
        ];

        $tubeColors = [
            1 => '#1E90FF', // 7-day
            2 => '#FFD700', // 14-day
            3 => '#FF4500', // 21-day
        ];
        $defaultColor = '#e9ecef';

        // Parameter DataTables
        $draw = (int)($request->getPost('draw') ?? 1);
        $start = (int)($request->getPost('start') ?? 0);
        $length = (int)($request->getPost('length') ?? 10);
        $searchValue = trim($request->getPost('search')['value'] ?? '');
        $order = $request->getPost('order') ?? [];

        // Base query builder
        $baseBuilder = $db->table('specimens s')
            ->join('containers c', 's.container_id = c.container_id', 'left')
            ->join('storages st', 'c.storage_id = st.storage_id', 'left')
            ->join('units u', 's.units_id = u.units_id', 'left')
            ->whereIn('s.status', [0, 1, 2])
            ->where('s.specimens_code !=', '');

        // SEKURITI SPESIMEN: Role 1 & 2 terhad kepada unit sendiri, Role 3 (Admin) semua
        if ($userRole !== 3) {
            if (!empty($sessionUnitIds)) {
                $baseBuilder->whereIn('s.units_id', $sessionUnitIds);
            } else {
                $baseBuilder->where('s.units_id', -1);
            }
        }

        // Jumlah rekod keseluruhan sebelum filter carian
        $recordsTotal = (clone $baseBuilder)->countAllResults();

        // Penapisan Carian (Search)
        if ($searchValue !== '') {
            $baseBuilder->groupStart()
                ->like('s.specimens_code', $searchValue)
                ->orLike('s.slot_no', $searchValue)
                ->orLike('c.container_label', $searchValue)
                ->orLike('st.storage_name', $searchValue)
                ->orLike('u.units_name', $searchValue)
                ->orLike('s.created_by', $searchValue)
                ->orLike('s.remark', $searchValue);

            $searchLower = strtolower($searchValue);
            if (strpos('active', $searchLower) !== false) {
                $baseBuilder->orWhere('s.status', 1);
            }
            if (strpos('inactive', $searchLower) !== false) {
                $baseBuilder->orWhere('s.status', 0);
            }
            if (strpos('disposed', $searchLower) !== false) {
                $baseBuilder->orWhere('s.status', 2);
            }

            $baseBuilder->groupEnd();
        }

        // Jumlah rekod selepas ditapis carian
        $recordsFiltered = (clone $baseBuilder)->countAllResults();

        // Pemetaan Lajur untuk Penyusunan (Ordering)
        $columnsMap = [
            0 => 's.specimens_id',
            1 => 's.specimens_code',
            2 => 's.slot_no',
            3 => 'c.container_label',
            4 => 'st.storage_name',
            5 => 'u.units_name',
            6 => 's.created_at',
            7 => 's.created_by',
            8 => 's.created_at',
            9 => 's.status',
            10 => 's.remark',
        ];

        if (!empty($order) && is_array($order)) {
            foreach ($order as $ord) {
                $colIdx = (int)($ord['column'] ?? 0);
                $colDir = strtoupper($ord['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
                if ($colIdx === 8) {
                    // Umur: umur bertambah jika tarikh dicipta lebih awal
                    $colDir = $colDir === 'DESC' ? 'ASC' : 'DESC';
                    $baseBuilder->orderBy('s.created_at', $colDir);
                } elseif (isset($columnsMap[$colIdx])) {
                    $baseBuilder->orderBy($columnsMap[$colIdx], $colDir);
                }
            }
        } else {
            $baseBuilder->orderBy('s.status', 'ASC')->orderBy('s.created_at', 'DESC');
        }

        // Paginasi (Pagination)
        if ($length > 0) {
            $baseBuilder->limit($length, $start);
        }

        $records = $baseBuilder->select([
            's.specimens_id',
            's.specimens_code',
            's.slot_no',
            's.container_id',
            's.units_id',
            's.created_at',
            's.created_by',
            's.status',
            's.remark',
            'c.container_label',
            'st.storage_name',
            'u.units_name',
            'DATEDIFF(NOW(), s.created_at) AS age_days',
            'c.duration_id'
        ])->get()->getResultArray();

        $data = [];
        $i = $start + 1;
        foreach ($records as $s) {
            $durationId = $s['duration_id'] ?? null;
            $tubeColor = $tubeColors[$durationId] ?? $defaultColor;
            $specimenCode = trim($s['specimens_code'] ?? '-');

            $currentStatus = isset($s['status']) ? (int)$s['status'] : 0;
            $currentMeta = $statusMap[$currentStatus] ?? ['label' => 'Inactive', 'class' => 'bg-danger'];

            $svgHtml = '<svg width="120" height="34" viewBox="0 0 120 34" xmlns="http://www.w3.org/2000/svg" class="barcode-tube" style="cursor:pointer" '
                . 'role="img" '
                . 'aria-label="Specimen ' . esc($specimenCode) . '" '
                . 'data-specimen="' . esc($specimenCode, 'attr') . '">'
                . '<rect x="4" y="4" rx="14" ry="14" width="108" height="26" fill="#e9ecef" stroke="#adb5bd" stroke-width="1.5" />'
                . '<rect x="108" y="6" rx="6" ry="6" width="10" height="22" fill="#6c757d" />'
                . '<rect x="6" y="6" rx="12" ry="12" width="90" height="22" fill="' . $tubeColor . '" opacity="0.85" />'
                . '<text x="50" y="22" text-anchor="middle" font-size="9" fill="#fff" font-weight="700">' . esc($specimenCode) . '</text>'
                . '</svg>';

            $data[] = [
                $i++,
                $svgHtml,
                '<span class="fw-bold">' . esc($s['slot_no'] ?? '-') . '</span>',
                esc($s['container_label'] ?? '-'),
                '<small class="text-muted"><i class="fa-solid fa-database me-1"></i>' . esc($s['storage_name'] ?? '-') . '</small>',
                '<span class="badge bg-light text-dark border py-1 px-2">' . esc($s['units_name'] ?? '-') . '</span>',
                !empty($s['created_at']) ? date('d-m-Y H:i', strtotime($s['created_at'])) : '-',
                !empty($s['created_by']) ? esc($s['created_by']) : '-',
                '<span class="badge bg-secondary">' . esc($s['age_days'] ?? '-') . '</span>',
                '<span class="badge ' . $currentMeta['class'] . '">' . $currentMeta['label'] . '</span>',
                !empty($s['remark']) ? '<span style="max-width: 180px; word-wrap: break-word; display: inline-block;">' . esc($s['remark']) . '</span>' : '<span class="text-center d-block">-</span>',
            ];
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ]);
    }

    // -----------------------------
    // Disposal List View Execution
    // UPGRADE: Data dimuatkan via AJAX on-demand (disposalAjax)
    // -----------------------------
    public function disposal()
    {
        $role = (int) session()->get('role');
        return view('forms/disposal_list', ['role' => $role]);
    }

    // -----------------------------
    // AJAX: Server-Side DataTables for Disposal List (7/14/21-day queues)
    // -----------------------------
    public function disposalAjax()
    {
        try {
        $days         = (int)($this->request->getPost('days') ?? 7);
        $durationMap  = [7 => 1, 14 => 2, 21 => 3];
        $thresholdMap = [7 => 6, 14 => 13, 21 => 20]; // min age (days) before appearing in queue
        $colorMap     = [1 => '#0d6efd', 2 => '#ffc107', 3 => '#dc3545'];

        $durationId = $durationMap[$days]  ?? 1;
        $minAgeDays = $thresholdMap[$days] ?? 6;
        $tubeColor  = $colorMap[$durationId] ?? '#0d6efd';

        // Session role & unit filtering
        $role            = (int)(session()->get('role') ?? 0);
        $rawSessionUnits = session()->get('unit_id') ?? [];

        if (is_string($rawSessionUnits)) {
            $sessionUnitIds = explode(',', $rawSessionUnits);
        } elseif (is_numeric($rawSessionUnits)) {
            $sessionUnitIds = [(int)$rawSessionUnits];
        } else {
            $sessionUnitIds = (array)$rawSessionUnits;
        }
        $sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));

        // DataTables standard parameters
        $draw        = (int)($this->request->getPost('draw') ?? 1);
        $start       = (int)($this->request->getPost('start') ?? 0);
        $length      = (int)($this->request->getPost('length') ?? 10);
        $searchValue = trim($this->request->getPost('search')['value'] ?? '');
        $order       = $this->request->getPost('order') ?? [];

        $db = \Config\Database::connect();

        // Base query — mirrors original disposal() join pattern
        $baseBuilder = $db->table('specimens s')
            ->join('containers c', 's.container_id = c.container_id')
            ->join('storages st', 'c.storage_id = st.storage_id')
            ->join('units u', 'st.units_code = u.units_code')
            ->where('s.status', self::STATUS_ACTIVE)
            ->where('c.duration_id', $durationId)
            ->where('s.created_at <=', date('Y-m-d H:i:s', strtotime("-{$minAgeDays} days")));

        // Role-based access control (Role 3 = Admin sees all units)
        if ($role !== 3) {
            if (!empty($sessionUnitIds)) {
                $baseBuilder->whereIn('u.units_id', $sessionUnitIds);
            } else {
                $baseBuilder->where('u.units_id', -1); // Force empty — no unit bound
            }
        }

        // Total records before search filter
        $recordsTotal = (clone $baseBuilder)->countAllResults();

        // Count specimens NOT yet requested (request_to_dispose_at IS NULL) — used by frontend
        // to show/hide the "Request to Dispose" button for this queue
        $unrequestedCount = (clone $baseBuilder)->where('s.request_to_dispose_at', null)->countAllResults();

        // Apply search filter
        if ($searchValue !== '') {
            $baseBuilder->groupStart()
                ->like('s.specimens_code', $searchValue)
                ->orLike('s.slot_no', $searchValue)
                ->orLike('c.container_label', $searchValue)
                ->orLike('st.storage_name', $searchValue)
                ->orLike('u.units_name', $searchValue)
            ->groupEnd();
        }

        // Total records after search filter
        $recordsFiltered = (clone $baseBuilder)->countAllResults();

        // Column map for ordering (column index => SQL expression)
        // Column 7 (Age) uses created_at as proxy — created_at ASC = oldest = most days old
        // DATEDIFF() expression avoided: CI4 QueryBuilder splits on comma inside parentheses → SQL error
        $columnsMap = [
            2 => 's.slot_no',
            3 => 'c.container_label',
            4 => 'st.storage_name',
            5 => 'u.units_name',
            6 => 's.created_at',
            7 => 's.created_at', // proxy for age: ASC = oldest first
            8 => 's.request_to_dispose_at',
        ];

        if (!empty($order) && is_array($order)) {
            foreach ($order as $ord) {
                $colIdx = (int)($ord['column'] ?? 7);
                $colDir = strtoupper($ord['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
                if (isset($columnsMap[$colIdx])) {
                    // Column 7 = Age: invert direction (age DESC = oldest = created_at ASC)
                    $sortDir = ($colIdx === 7) ? ($colDir === 'DESC' ? 'ASC' : 'DESC') : $colDir;
                    $baseBuilder->orderBy($columnsMap[$colIdx], $sortDir);
                }
            }
        } else {
            $baseBuilder->orderBy('s.created_at', 'ASC'); // default: oldest (most days) first
        }


        // Apply pagination
        if ($length > 0) {
            $baseBuilder->limit($length, $start);
        }

        $records = $baseBuilder->select([
            's.specimens_code',
            's.slot_no',
            's.created_at',
            's.request_to_dispose_at',
            's.request_to_dispose_by',
            'c.container_label',
            'st.storage_name',
            'u.units_name',
            'DATEDIFF(NOW(), s.created_at) AS age_days',
        ])->get()->getResultArray();

        // Build DataTables row data with rendered HTML cells
        $data = [];
        $i    = $start + 1;

        foreach ($records as $spec) {
            $specimenCode = trim($spec['specimens_code'] ?? '');
            $ageDays      = (int)($spec['age_days'] ?? 0);
            $isRequested  = !empty($spec['request_to_dispose_at']);
            $canDispose   = in_array($role, [2, 3], true) && $isRequested && $ageDays >= ($days - 1);

            // Specimen tube SVG
            $svgHtml = '<svg width="120" height="34" viewBox="0 0 120 34" xmlns="http://www.w3.org/2000/svg">'
                . '<rect x="4" y="4" rx="14" ry="14" width="108" height="26" fill="#e9ecef" stroke="#adb5bd" stroke-width="1.5"/>'
                . '<rect x="108" y="6" rx="6" ry="6" width="10" height="22" fill="#6c757d"/>'
                . '<rect x="6" y="6" rx="12" ry="12" width="90" height="22" fill="' . $tubeColor . '" opacity="0.85"/>'
                . '<text x="50" y="22" text-anchor="middle" font-size="9" font-weight="700" fill="#fff">' . esc($specimenCode) . '</text>'
                . '</svg>';

            // Request status badge
            if ($isRequested) {
                $statusHtml = '<span class="badge bg-info text-wrap text-start p-2" style="line-height:1.3;">'
                    . 'Requested<br>'
                    . esc(date('d-m-Y h:i A', strtotime($spec['request_to_dispose_at']))) . '<br>'
                    . '<small>by ' . esc($spec['request_to_dispose_by'] ?? 'system') . '</small>'
                    . '</span>';
            } else {
                $statusHtml = '<span class="text-muted small">Not requested</span>';
            }

            // Action column — checkbox or status indicator
            if ($canDispose) {
                $actionHtml = '<input type="checkbox" class="form-check-input dispose-checkbox"'
                    . ' value="' . esc($specimenCode, 'attr') . '"'
                    . ' style="transform:scale(1.3);cursor:pointer;" checked>';
            } elseif ($role === 1 && $isRequested) {
                $actionHtml = '<span class="text-muted small">Awaiting approval</span>';
            } elseif ($role === 1 && !$isRequested) {
                $actionHtml = '<span class="text-success small"><i class="fa-solid fa-circle-check me-1"></i>Eligible</span>';
            } else {
                $actionHtml = '<input type="checkbox" class="form-check-input" style="transform:scale(1.1);" disabled>';
            }

            $data[] = [
                $i++,
                $svgHtml,
                esc($spec['slot_no'] ?? '-'),
                esc($spec['container_label'] ?? '-'),
                esc($spec['storage_name'] ?? '-'),
                '<span class="badge bg-light text-dark border">' . esc($spec['units_name'] ?? '-') . '</span>',
                !empty($spec['created_at']) ? esc(date('d-m-Y h:i A', strtotime($spec['created_at']))) : '-',
                '<span class="badge bg-secondary">' . $ageDays . '</span>',
                $statusHtml,
                $actionHtml,
            ];
        }

        return $this->response->setJSON([
                'draw'             => $draw,
                'recordsTotal'     => $recordsTotal,
                'recordsFiltered'  => $recordsFiltered,
                'data'             => $data,
                'unrequestedCount' => $unrequestedCount,
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[disposalAjax] ' . $e->getMessage() . ' | File: ' . $e->getFile() . ':' . $e->getLine());
            return $this->response->setStatusCode(500)->setJSON([
                'draw'            => 1,
                'recordsTotal'    => 0,
                'recordsFiltered' => 0,
                'data'            => [],
                'error'           => '[DEBUG] ' . $e->getMessage() . ' | ' . basename($e->getFile()) . ':' . $e->getLine(),
            ]);
        }
    }

    // -----------------------------
    // Request Disposal Action (Bulk Execution Sync)
    // -----------------------------
    public function requestDisposeAll()
    {
        $days   = (int) ($this->request->getGet('days') ?? 7);
        $reason = trim($this->request->getGet('reason') ?? '');

        if ($reason === '') {
            return redirect()->back()->with('error', 'Disposal reason is required.');
        }

        $eligibleDays = $days - 1;
        $durationMap  = [7 => 1, 14 => 2, 21 => 3];

        $durationId = $durationMap[$days] ?? null;
        if (!$durationId) {
            return redirect()->back()->with('error', 'Invalid duration.');
        }

        $model = new SpecimensModel();
        $fullEmail = session()->get('email') ?? 'system';
        $email = explode('@', $fullEmail)[0];

        // Perform standard update on remaining unrequested items
        $model->where('status', self::STATUS_ACTIVE)
            ->where('created_at <=', date("Y-m-d H:i:s", strtotime("-{$eligibleDays} days")))
            ->where('request_to_dispose_at', null)
            ->whereIn('container_id', function ($sub) use ($durationId) {
                $sub->select('container_id')
                    ->from('containers')
                    ->where('duration_id', $durationId);
            })
            ->set([
                'request_to_dispose_at' => date('Y-m-d H:i:s'),
                'request_to_dispose_by' => $email,
                'remark'                => $reason,
            ])
            ->update();

        return redirect()->back()->with('success', "Disposal request batch processed for {$days}-day profile elements.");
    }

    /**
     * PROCESS BULK BATCH DISPOSAL DESTRUCTION VIA AJAX POST (SET STATUS = 2)
     * KEMASKINI: Mengekalkan remark asal (Aged Sample), tiada overwrite berlaku.
     */
    public function disposeSelectedAjax()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Direct script access validation failed.']);
        }

        $specimenCodes = $this->request->getPost('specimen_codes');

        if (empty($specimenCodes) || !is_array($specimenCodes)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No valid specimen data packages identified for destructive write operations.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash()
            ]);
        }

        $db = \Config\Database::connect();
        $db->transBegin();

        try {
            // 🌟 AMBIL DATA SESI AKTIF (Siapa yang sedang tekan butang pelupusan)
            // $currentUserId = session()->get('user_id') ?? 1; // Fallback ke ID 1 jika sesi terputus
            $currentUserId = session()->get('user_email') ?? session()->get('email');
            $currentDateTime = date('Y-m-d H:i:s'); // Tarikh masa pelupusan waktu ini

            foreach ($specimenCodes as $code) {
                $db->table('specimens')
                    ->where('specimens_code', $code)
                    ->where('status', 1)
                    ->update([
                        'status'      => 2,                // Status 2 = Disposed
                        'disposed_at' => $currentDateTime, // 🌟 FIX: Isi tarikh pelupusan untuk Tab 2 Laporan
                        'disposed_by' => $currentUserId   // 🌟 FIX: Rekod siapa yang melupuskannya
                        
                    ]);
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Database layer transactional lock error encountered.',
                    'csrf_token' => csrf_token(),
                    'csrf_hash'  => csrf_hash()
                ]);
            }

            $db->transCommit();

            return $this->response->setJSON([
                'success'    => true,
                'message'    => 'Successfully cleared and disposed ' . count($specimenCodes) . ' specimen asset unit parameters safely.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash()
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => 'A critical backend exception system error occurred.',
                'csrf_token' => csrf_token(),
                'csrf_hash'  => csrf_hash()
            ]);
        }
    }

    // -----------------------------
    // Dashboard Status Metrics Lookup
    // -----------------------------
    public function getReadyToDispose()
    {
        // ============================================================
        // ROLE-BASED UNIT FILTERING
        // ============================================================
        $role = (int) session()->get('role');
        $rawSessionUnits = session()->get('unit_id') ?? [];

        if (is_string($rawSessionUnits)) {
            $sessionUnitIds = explode(',', $rawSessionUnits);
        } elseif (is_numeric($rawSessionUnits)) {
            $sessionUnitIds = [(int)$rawSessionUnits];
        } else {
            $sessionUnitIds = (array)$rawSessionUnits;
        }
        $sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));

        $db = \Config\Database::connect();
        $builder = $db->table('specimens s')
            ->select('s.specimens_code, u.units_name as unit_name, u.units_id, st.storage_name, s.created_by, s.created_at, c.duration_id')
            ->join('containers c', 's.container_id = c.container_id')
            ->join('storages st', 'c.storage_id = st.storage_id')
            ->join('units u', 'st.units_code = u.units_code')
            ->where('s.status', self::STATUS_ACTIVE);

        // Tapis mengikut unit jika Role 2 (Superuser), Role 3 (Admin) nampak semua
        if ($role !== 3) {
            if (!empty($sessionUnitIds)) {
                $builder->whereIn('u.units_id', $sessionUnitIds);
            } else {
                $builder->where('u.units_id', 0); // Sekat semua jika tiada unit
            }
        }

        $specimens = $builder->orderBy('s.created_at', 'DESC')->get()->getResultArray();

        $thresholds = [1 => 6, 2 => 13, 3 => 20];
        $readyToDispose = [];
        $today = new \DateTime();

        foreach ($specimens as $s) {
            if (!empty($s['created_at'])) {
                $created = new \DateTime($s['created_at']);
                $ageDays = $today->diff($created)->days;
                $durationId = (int) $s['duration_id'];

                if (isset($thresholds[$durationId]) && $ageDays >= $thresholds[$durationId]) {
                    $s['age_days'] = $ageDays;
                    $readyToDispose[$durationId][] = $s;
                }
            }
        }

        return $this->response->setJSON($readyToDispose);
    }
}
