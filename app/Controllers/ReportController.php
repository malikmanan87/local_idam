<?php

namespace App\Controllers;

use App\Models\UnitsModel;
use App\Models\SpecimensModel;
use DateTime;

class ReportController extends BaseController
{
    /**
     * Memeriksa Security Clearance (Hanya Superuser: 2 & Admin: 3)
     */
    protected function enforceSecurityClearance()
    {
        $db = \Config\Database::connect();
        $maintenance = $db->table('system_settings')->where('setting_key', 'maintenance_mode')->get()->getRow();

        if ($maintenance && (int)$maintenance->setting_value === 1 && (int)session()->get('role') !== 3) {
            $reason = $db->table('system_settings')->where('setting_key', 'maintenance_reason')->get()->getRow()->setting_value ?? 'Under maintenance.';
            echo view('errors/html/production_maintenance', ['reason' => $reason]);
            exit;
        }

        if (!in_array((int)session()->get('role'), [2, 3])) {
            redirect()->to(base_url('/'))->with('error', 'Access Denied.')->send();
            exit;
        }
    }

    /**
     * Dapatkan senarai ID unit bagi sesi pengguna semasa
     */
    protected function getSessionUnitIds()
    {
        $raw = session()->get('unit_id') ?? [];
        if (is_string($raw)) {
            $ids = explode(',', $raw);
        } elseif (is_numeric($raw)) {
            $ids = [(int)$raw];
        } else {
            $ids = (array)$raw;
        }
        return array_filter(array_map('intval', array_map('trim', $ids)));
    }

    public function index()
    {
        $this->enforceSecurityClearance();
        $db = \Config\Database::connect();
        $unitsModel = new UnitsModel();

        $role = (int)session()->get('role');
        $sessionUnitIds = $this->getSessionUnitIds();

        // Indikator Tab Aktif (Default: stats)
        $activeTab = $this->request->getGet('tab') ?? 'stats';

        // Sekatan Unit: Admin (3) melihat semua unit; Super User (2) hanya unit sendiri
        if ($role === 3) {
            $unitsData = $unitsModel->where('status', '1')->findAll();
        } else {
            $unitsData = !empty($sessionUnitIds)
                ? $unitsModel->where('status', '1')->whereIn('units_id', $sessionUnitIds)->findAll()
                : [];
        }

        // ============================================================
        // 📊 [TAB 1]: PROSES DATA STATISTIK REGISTRASI GENERAL
        // ============================================================
        $duration  = $this->request->getGet('duration') ?? 7;
        $frequency = $this->request->getGet('frequency') ?? 'weekly';
        $startDateStats = date('Y-m-d H:i:s', strtotime("-{$duration} days"));

        // Bina pecahan kekerapan tarikh (Weekly vs Monthly)
        $frequencySelect = "";
        $frequencyGroupBy = "";
        
        if ($frequency == 'weekly') {
            $frequencySelect = ", YEAR(s.created_at) AS `year`, WEEK(s.created_at) AS `week`";
            $frequencyGroupBy = ", YEAR(s.created_at), WEEK(s.created_at)";
        } elseif ($frequency == 'monthly') {
            $frequencySelect = ", YEAR(s.created_at) AS `year`, MONTH(s.created_at) AS `month`";
            $frequencyGroupBy = ", YEAR(s.created_at), MONTH(s.created_at)";
        } else {
            $frequencySelect = ", YEAR(s.created_at) AS `year`";
            $frequencyGroupBy = ", YEAR(s.created_at)";
        }

        // Tapis statistik mengikut unit pengguna jika bukan Admin
        $unitFilterSql = "";
        $queryParams   = [$startDateStats];

        if ($role !== 3) {
            if (!empty($sessionUnitIds)) {
                $placeholders = implode(',', array_fill(0, count($sessionUnitIds), '?'));
                $unitFilterSql = " AND s.units_id IN ($placeholders) ";
                $queryParams   = array_merge($queryParams, $sessionUnitIds);
            } else {
                $unitFilterSql = " AND s.units_id = -1 ";
            }
        }

        // 🌟 RAW SQL: Terikat dengan sekatan unit bagi peranan bukan admin
        $rawSql = "
            SELECT 
                COALESCE(u.units_name, 'N/A') AS units_name, 
                COALESCE(st.storage_name, 'UNASSIGNED') AS storage_name, 
                COALESCE(c.container_label, 'UNASSIGNED') AS container_label, 
                COUNT(s.specimens_id) AS specimens_count
                {$frequencySelect}
            FROM `specimens` s
            LEFT JOIN `containers` c ON s.container_id = c.container_id
            LEFT JOIN `storages` st ON c.storage_id = st.storage_id
            LEFT JOIN `units` u ON s.units_id = u.units_id
            WHERE s.created_at >= ? {$unitFilterSql}
            GROUP BY u.units_name, st.storage_name, c.container_label {$frequencyGroupBy}
        ";

        $statsReports = $db->query($rawSql, $queryParams)->getResultArray();

        // ============================================================
        // 🗑️ [TAB 2 & 3]: DATA AUDIT DIMUATKAN SECARA AJAX ON-DEMAND
        // ============================================================
        $unitSelect    = $this->request->getGet('unit') ?? 'all';
        $storageSelect = $this->request->getGet('storage_duration') ?? 'all';
        $dateRange     = $this->request->getGet('daterange') ?? '';
        $cancelUnit    = $this->request->getGet('cancel_unit') ?? 'all';
        $cancelDate    = $this->request->getGet('cancel_daterange') ?? '';

        return view('tables/combined_report', [
            'title'              => 'Centralized Reports',
            'units'              => $unitsData,
            'userRole'           => $role,
            // Tab 1
            'statsReports'       => $statsReports,
            'selectedDuration'   => $duration,
            'selectedFrequency'  => $frequency,
            // Tab 2 (Dimuatkan secara AJAX)
            'disposalReports'    => [],
            'selectedUnit'       => $unitSelect,
            'selectedStorage'    => $storageSelect,
            'selectedDate'       => $dateRange,
            // Tab 3 (Dimuatkan secara AJAX)
            'cancelReports'      => [],
            'selectedCancelUnit' => $cancelUnit,
            'selectedCancelDate' => $cancelDate,
            // Active Tab State Controller
            'activeTab'          => $activeTab
        ]);
    }

    /**
     * AJAX Endpoint: Pemuatan Data Server-Side untuk Jadual Pelupusan (#disposeTable)
     */
    public function disposalAuditAjax()
    {
        $role = (int)(session()->get('role') ?? 0);
        $sessionUnitIds = $this->getSessionUnitIds();

        $draw        = (int)($this->request->getPost('draw') ?? 1);
        $start       = (int)($this->request->getPost('start') ?? 0);
        $length      = (int)($this->request->getPost('length') ?? 10);
        $searchValue = trim($this->request->getPost('search')['value'] ?? '');
        $order       = $this->request->getPost('order') ?? [];

        $unitSelect    = $this->request->getPost('unit') ?? 'all';
        $storageSelect = $this->request->getPost('storage_duration') ?? 'all';
        $dateRange     = $this->request->getPost('daterange') ?? '';

        $db = \Config\Database::connect();
        $builder = $db->table('specimens s')
            ->select('s.*, u.units_name, c.container_label, st.storage_name, us1.email AS created_email, us2.email AS disposed_email')
            ->join('containers c', 's.container_id = c.container_id', 'left')
            ->join('storages st', 'c.storage_id = st.storage_id', 'left')
            ->join('units u', 'u.units_id = s.units_id', 'left')
            ->join('users us1', 'us1.email = s.created_by', 'left')
            ->join('users us2', 'us2.user_id = s.disposed_by', 'left')
            ->where('s.disposed_at IS NOT NULL');

        // Sekatan Keselamatan: Role bukan 3 (Admin) terhad kepada spesimen unit sendiri
        if ($role !== 3) {
            if (!empty($sessionUnitIds)) {
                $builder->whereIn('s.units_id', $sessionUnitIds);
            } else {
                $builder->where('s.units_id', -1);
            }
        }

        if ($unitSelect !== 'all' && !empty($unitSelect)) {
            $builder->where('u.units_code', $unitSelect);
        }
        if ($storageSelect !== 'all' && !empty($storageSelect)) {
            $builder->where('st.storage_id', $storageSelect);
        }
        if (!empty($dateRange) && strpos($dateRange, ' to ') !== false) {
            [$startD, $endD] = explode(' to ', $dateRange);
            $startDate = \DateTime::createFromFormat('d-m-Y', trim($startD));
            $endDate   = \DateTime::createFromFormat('d-m-Y', trim($endD));
            if ($startDate && $endDate) {
                $builder->where('s.created_at >=', $startDate->format('Y-m-d') . ' 00:00:00');
                $builder->where('s.created_at <=', $endDate->format('Y-m-d') . ' 23:59:59');
            }
        }

        $recordsTotal = (clone $builder)->countAllResults();

        if ($searchValue !== '') {
            $builder->groupStart()
                ->like('s.specimens_code', $searchValue)
                ->orLike('u.units_name', $searchValue)
                ->orLike('st.storage_name', $searchValue)
                ->orLike('c.container_label', $searchValue)
                ->orLike('s.slot_no', $searchValue)
                ->orLike('s.created_by', $searchValue)
                ->orLike('s.request_to_dispose_by', $searchValue)
                ->orLike('s.disposed_by', $searchValue)
                ->orLike('s.remark', $searchValue)
            ->groupEnd();
        }

        $recordsFiltered = (clone $builder)->countAllResults();

        $columnsMap = [
            1 => 's.specimens_code',
            2 => 's.created_at', // duration proxy
            3 => 's.created_at',
            4 => 's.created_by',
            5 => 'u.units_name',
            6 => 'st.storage_name',
            7 => 's.request_to_dispose_by',
            8 => 's.disposed_at',
            9 => 's.disposed_by',
            11 => 's.remark',
        ];

        if (!empty($order) && is_array($order)) {
            foreach ($order as $ord) {
                $colIdx = (int)($ord['column'] ?? 8);
                $colDir = strtoupper($ord['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
                if (isset($columnsMap[$colIdx])) {
                    $sortDir = ($colIdx === 2) ? ($colDir === 'DESC' ? 'ASC' : 'DESC') : $colDir;
                    $builder->orderBy($columnsMap[$colIdx], $sortDir);
                }
            }
        } else {
            $builder->orderBy('s.disposed_at', 'DESC');
        }

        if ($length > 0) {
            $builder->limit($length, $start);
        }

        $records = $builder->get()->getResultArray();

        $data = [];
        $i = $start + 1;
        foreach ($records as $r) {
            $createdAt = !empty($r['created_at']) ? new DateTime($r['created_at']) : null;
            $disposedAt = !empty($r['disposed_at']) ? new DateTime($r['disposed_at']) : null;
            $days = ($createdAt && $disposedAt) ? $createdAt->diff($disposedAt)->days : 0;

            $createdBy = esc(explode('@', $r['created_by'] ?? 'N/A')[0]);
            $requestedBy = esc(explode('@', $r['request_to_dispose_by'] ?? 'N/A')[0]);
            $disposedBy = esc(explode('@', $r['disposed_by'] ?? 'SYSTEM@')[0]);

            $storageInfo = 'STR: ' . esc($r['storage_name'] ?? 'N/A')
                . '<br>CON: ' . esc($r['container_label'] ?? 'N/A')
                . '<br>SLOT: ' . esc($r['slot_no'] ?? '-');

            $data[] = [
                $i++,
                '<span class="fw-bold">' . esc($r['specimens_code']) . '</span>',
                '<div class="text-center fw-semibold">' . $days . ' DAYS</div>',
                $createdAt ? $createdAt->format('d/m/Y h:i A') : '-',
                $createdBy,
                esc($r['units_name'] ?? 'N/A'),
                $storageInfo,
                $requestedBy,
                $disposedAt ? $disposedAt->format('d/m/Y h:i A') : '-',
                '<span class="text-danger fw-bold">' . $disposedBy . '</span>',
                '<div class="text-center"><span class="badge bg-danger">DISPOSED</span></div>',
                esc($r['remark'] ?? '')
            ];
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }

    /**
     * AJAX Endpoint: Pemuatan Data Server-Side untuk Jadual Pembatalan (#cancelTable)
     */
    public function cancelledAuditAjax()
    {
        $role = (int)(session()->get('role') ?? 0);
        $sessionUnitIds = $this->getSessionUnitIds();

        $draw        = (int)($this->request->getPost('draw') ?? 1);
        $start       = (int)($this->request->getPost('start') ?? 0);
        $length      = (int)($this->request->getPost('length') ?? 10);
        $searchValue = trim($this->request->getPost('search')['value'] ?? '');
        $order       = $this->request->getPost('order') ?? [];

        $cancelUnit = $this->request->getPost('cancel_unit') ?? 'all';
        $cancelDate = $this->request->getPost('cancel_daterange') ?? '';

        $db = \Config\Database::connect();
        $builder = $db->table('specimens s')
            ->select('s.*, u.units_name, c.container_label, st.storage_name, us1.email AS created_email, us3.email AS cancelled_email')
            ->join('containers c', 's.container_id = c.container_id', 'left')
            ->join('storages st', 'c.storage_id = st.storage_id', 'left')
            ->join('units u', 'u.units_id = s.units_id', 'left')
            ->join('users us1', 'us1.email = s.created_by', 'left')
            ->join('users us3', 'us3.user_id = s.created_by', 'left')
            ->where('s.status', 0);

        // Sekatan Keselamatan: Role bukan 3 (Admin) terhad kepada spesimen unit sendiri
        if ($role !== 3) {
            if (!empty($sessionUnitIds)) {
                $builder->whereIn('s.units_id', $sessionUnitIds);
            } else {
                $builder->where('s.units_id', -1);
            }
        }

        if ($cancelUnit !== 'all' && !empty($cancelUnit)) {
            $builder->where('u.units_code', $cancelUnit);
        }
        if (!empty($cancelDate) && strpos($cancelDate, ' to ') !== false) {
            [$cStart, $cEnd] = explode(' to ', $cancelDate);
            $startDate = \DateTime::createFromFormat('d-m-Y', trim($cStart));
            $endDate   = \DateTime::createFromFormat('d-m-Y', trim($cEnd));
            if ($startDate && $endDate) {
                $builder->where('s.created_at >=', $startDate->format('Y-m-d') . ' 00:00:00');
                $builder->where('s.created_at <=', $endDate->format('Y-m-d') . ' 23:59:59');
            }
        }

        $recordsTotal = (clone $builder)->countAllResults();

        if ($searchValue !== '') {
            $builder->groupStart()
                ->like('s.specimens_code', $searchValue)
                ->orLike('u.units_name', $searchValue)
                ->orLike('st.storage_name', $searchValue)
                ->orLike('c.container_label', $searchValue)
                ->orLike('s.slot_no', $searchValue)
                ->orLike('s.created_by', $searchValue)
                ->orLike('s.remark', $searchValue)
            ->groupEnd();
        }

        $recordsFiltered = (clone $builder)->countAllResults();

        $columnsMap = [
            1 => 's.specimens_code',
            2 => 's.created_at',
            3 => 's.created_at',
            4 => 's.created_by',
            5 => 'u.units_name',
            6 => 'st.storage_name',
            7 => 's.created_by',
            9 => 's.remark',
        ];

        if (!empty($order) && is_array($order)) {
            foreach ($order as $ord) {
                $colIdx = (int)($ord['column'] ?? 3);
                $colDir = strtoupper($ord['dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
                if (isset($columnsMap[$colIdx])) {
                    $sortDir = ($colIdx === 2) ? ($colDir === 'DESC' ? 'ASC' : 'DESC') : $colDir;
                    $builder->orderBy($columnsMap[$colIdx], $sortDir);
                }
            }
        } else {
            $builder->orderBy('s.created_at', 'DESC');
        }

        if ($length > 0) {
            $builder->limit($length, $start);
        }

        $records = $builder->get()->getResultArray();

        $data = [];
        $i = $start + 1;
        $now = new DateTime();
        foreach ($records as $c) {
            $createdAt = !empty($c['created_at']) ? new DateTime($c['created_at']) : null;
            $cancelledAt = !empty($c['cancelled_at']) ? new DateTime($c['cancelled_at']) : null;
            $days = $createdAt ? ($cancelledAt ? $createdAt->diff($cancelledAt)->days : $createdAt->diff($now)->days) : 0;

            $createdBy = esc(explode('@', $c['created_by'] ?? 'N/A')[0]);
            $cancelledBy = esc(explode('@', $c['cancelled_by'] ?? 'STAFF@')[0]);

            $storageInfo = 'STR: ' . esc($c['storage_name'] ?? 'N/A')
                . '<br>CON: ' . esc($c['container_label'] ?? 'N/A')
                . '<br>SLOT: ' . esc($c['slot_no'] ?? '-');

            $data[] = [
                $i++,
                '<span class="fw-bold text-secondary">' . esc($c['specimens_code']) . '</span>',
                '<div class="text-center">' . $days . ' DAYS</div>',
                $createdAt ? $createdAt->format('d/m/Y h:i A') : '-',
                $createdBy,
                esc($c['units_name'] ?? 'N/A'),
                $storageInfo,
                '<span class="text-warning fw-bold">' . $cancelledBy . '</span>',
                '<div class="text-center"><span class="badge bg-secondary text-white">INACTIVE</span></div>',
                esc($c['remark'] ?? '')
            ];
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data
        ]);
    }

    /**
     * Endpoint untuk Eksport Data Penuh (PDF / Excel) mengikut Penapis Semasa
     */
    public function exportAuditAjax()
    {
        $this->enforceSecurityClearance();

        $role = (int)(session()->get('role') ?? 0);
        $sessionUnitIds = $this->getSessionUnitIds();

        $type = $this->request->getPost('type') ?? 'disposal';
        $db = \Config\Database::connect();

        if ($type === 'disposal') {
            $unitSelect    = $this->request->getPost('unit') ?? 'all';
            $storageSelect = $this->request->getPost('storage_duration') ?? 'all';
            $dateRange     = $this->request->getPost('daterange') ?? '';

            $builder = $db->table('specimens s')
                ->select('s.*, u.units_name, c.container_label, st.storage_name')
                ->join('containers c', 's.container_id = c.container_id', 'left')
                ->join('storages st', 'c.storage_id = st.storage_id', 'left')
                ->join('units u', 'u.units_id = s.units_id', 'left')
                ->where('s.disposed_at IS NOT NULL');

            // Sekatan Keselamatan: Role bukan 3 (Admin) terhad kepada spesimen unit sendiri
            if ($role !== 3) {
                if (!empty($sessionUnitIds)) {
                    $builder->whereIn('s.units_id', $sessionUnitIds);
                } else {
                    $builder->where('s.units_id', -1);
                }
            }

            if ($unitSelect !== 'all' && !empty($unitSelect)) {
                $builder->where('u.units_code', $unitSelect);
            }
            if ($storageSelect !== 'all' && !empty($storageSelect)) {
                $builder->where('st.storage_id', $storageSelect);
            }
            if (!empty($dateRange) && strpos($dateRange, ' to ') !== false) {
                [$startD, $endD] = explode(' to ', $dateRange);
                $startDate = \DateTime::createFromFormat('d-m-Y', trim($startD));
                $endDate   = \DateTime::createFromFormat('d-m-Y', trim($endD));
                if ($startDate && $endDate) {
                    $builder->where('s.created_at >=', $startDate->format('Y-m-d') . ' 00:00:00');
                    $builder->where('s.created_at <=', $endDate->format('Y-m-d') . ' 23:59:59');
                }
            }

            $records = $builder->orderBy('s.disposed_at', 'DESC')->limit(10000)->get()->getResultArray();

            $exportData = [];
            $i = 1;
            foreach ($records as $r) {
                $createdAt = !empty($r['created_at']) ? new DateTime($r['created_at']) : null;
                $disposedAt = !empty($r['disposed_at']) ? new DateTime($r['disposed_at']) : null;
                $days = ($createdAt && $disposedAt) ? $createdAt->diff($disposedAt)->days : 0;

                $exportData[] = [
                    'no'                   => $i++,
                    'specimens_code'       => $r['specimens_code'],
                    'specimen_type'        => 'Darah',
                    'duration'             => $days . ' DAYS',
                    'created_at'           => $createdAt ? $createdAt->format('d/m/Y h:i A') : '-',
                    'created_by'           => explode('@', $r['created_by'] ?? 'N/A')[0],
                    'units_name'           => $r['units_name'] ?? 'N/A',
                    'storage_name'         => $r['storage_name'] ?? 'N/A',
                    'container_label'      => $r['container_label'] ?? 'N/A',
                    'slot_no'              => $r['slot_no'] ?? '-',
                    'request_to_dispose_by'=> explode('@', $r['request_to_dispose_by'] ?? 'N/A')[0],
                    'disposed_at'          => $disposedAt ? $disposedAt->format('d/m/Y h:i A') : '-',
                    'disposed_by'          => explode('@', $r['disposed_by'] ?? 'SYSTEM@')[0],
                    'status'               => 'DISPOSED',
                    'remark'               => $r['remark'] ?? ''
                ];
            }

            return $this->response->setJSON(['status' => 'success', 'data' => $exportData]);
        } elseif ($type === 'cancel') {
            $cancelUnit = $this->request->getPost('cancel_unit') ?? 'all';
            $cancelDate = $this->request->getPost('cancel_daterange') ?? '';

            $builder = $db->table('specimens s')
                ->select('s.*, u.units_name, c.container_label, st.storage_name')
                ->join('containers c', 's.container_id = c.container_id', 'left')
                ->join('storages st', 'c.storage_id = st.storage_id', 'left')
                ->join('units u', 'u.units_id = s.units_id', 'left')
                ->where('s.status', 0);

            // Sekatan Keselamatan: Role bukan 3 (Admin) terhad kepada spesimen unit sendiri
            if ($role !== 3) {
                if (!empty($sessionUnitIds)) {
                    $builder->whereIn('s.units_id', $sessionUnitIds);
                } else {
                    $builder->where('s.units_id', -1);
                }
            }

            if ($cancelUnit !== 'all' && !empty($cancelUnit)) {
                $builder->where('u.units_code', $cancelUnit);
            }
            if (!empty($cancelDate) && strpos($cancelDate, ' to ') !== false) {
                [$cStart, $cEnd] = explode(' to ', $cancelDate);
                $startDate = \DateTime::createFromFormat('d-m-Y', trim($cStart));
                $endDate   = \DateTime::createFromFormat('d-m-Y', trim($cEnd));
                if ($startDate && $endDate) {
                    $builder->where('s.created_at >=', $startDate->format('Y-m-d') . ' 00:00:00');
                    $builder->where('s.created_at <=', $endDate->format('Y-m-d') . ' 23:59:59');
                }
            }

            $records = $builder->orderBy('s.created_at', 'DESC')->limit(10000)->get()->getResultArray();

            $exportData = [];
            $i = 1;
            $now = new DateTime();
            foreach ($records as $c) {
                $createdAt = !empty($c['created_at']) ? new DateTime($c['created_at']) : null;
                $cancelledAt = !empty($c['cancelled_at']) ? new DateTime($c['cancelled_at']) : null;
                $days = $createdAt ? ($cancelledAt ? $createdAt->diff($cancelledAt)->days : $createdAt->diff($now)->days) : 0;

                $exportData[] = [
                    'no'              => $i++,
                    'specimens_code'  => $c['specimens_code'],
                    'duration'        => $days . ' DAYS',
                    'created_at'      => $createdAt ? $createdAt->format('d/m/Y h:i A') : '-',
                    'created_by'      => explode('@', $c['created_by'] ?? 'N/A')[0],
                    'units_name'      => $c['units_name'] ?? 'N/A',
                    'storage_name'    => $c['storage_name'] ?? 'N/A',
                    'container_label' => $c['container_label'] ?? 'N/A',
                    'slot_no'         => $c['slot_no'] ?? '-',
                    'cancelled_by'    => explode('@', $c['cancelled_by'] ?? 'STAFF@')[0],
                    'status'          => 'INACTIVE',
                    'remark'          => $c['remark'] ?? ''
                ];
            }

            return $this->response->setJSON(['status' => 'success', 'data' => $exportData]);
        }

        return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid export type']);
    }
}
