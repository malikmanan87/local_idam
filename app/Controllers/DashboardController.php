<?php

namespace App\Controllers;

use App\Models\SpecimensModel;

class DashboardController extends BaseController
{
    public function index()
    {
        $specimenModel = new SpecimensModel();
        $db = \Config\Database::connect();

        // ============================================================
        // ROLE-BASED SESSION FILTERING
        // ============================================================
        $role = (int) session()->get('role');
        $rawSessionUnits = session()->get('unit_id') ?? [];

        // Normalisasi unit_id sesi kepada array integer bersih
        if (is_string($rawSessionUnits)) {
            $sessionUnitIds = explode(',', $rawSessionUnits);
        } elseif (is_numeric($rawSessionUnits)) {
            $sessionUnitIds = [(int)$rawSessionUnits];
        } else {
            $sessionUnitIds = (array)$rawSessionUnits;
        }
        $sessionUnitIds = array_map('intval', array_map('trim', $sessionUnitIds));

        // Helper: tapis query ikut unit jika role bukan 3 (Admin)
        $applyUnitFilter = function ($query, $unitColumn = 'specimens.units_id') use ($role, $sessionUnitIds) {
            if ($role !== 3) {
                if (!empty($sessionUnitIds)) {
                    $query->whereIn($unitColumn, $sessionUnitIds);
                } else {
                    $query->where($unitColumn, 0); // Sekat semua jika tiada unit
                }
            }
            return $query;
        };

        // Total specimens by duration (include active + disposed)
        $durationQuery = $specimenModel
            ->select('c.duration_id, COUNT(*) as total')
            ->join('containers c', 'specimens.container_id = c.container_id')
            ->groupBy('c.duration_id');
        $applyUnitFilter($durationQuery);
        $countsByDuration = $durationQuery->findAll();

        $durationMap = [1 => 0, 2 => 0, 3 => 0];
        foreach ($countsByDuration as $row) {
            $durationMap[$row['duration_id']] = (int) $row['total'];
        }

        // Total count
        $totalSpecimens = array_sum($durationMap);

        // Specimens by status
        $statusQuery = $specimenModel
            ->select('status, COUNT(*) as total')
            ->groupBy('status');
        $applyUnitFilter($statusQuery);
        $countsByStatusRaw = $statusQuery->findAll();

        $countsByStatus = [
            'Inactive' => 0,
            'Active'   => 0,
            'Disposed' => 0
        ];

        foreach ($countsByStatusRaw as $row) {
            $statusValue = (int)$row['status'];
            if ($statusValue === 0) {
                $countsByStatus['Inactive'] = (int)$row['total'];
            } elseif ($statusValue === 1) {
                $countsByStatus['Active'] = (int)$row['total'];
            } elseif ($statusValue === 2) {
                $countsByStatus['Disposed'] = (int)$row['total'];
            }
        }

        // Specimens by storage
        $storageQuery = $specimenModel
            ->select('st.storage_name, COUNT(*) as total')
            ->join('containers c', 'specimens.container_id = c.container_id')
            ->join('storages st', 'c.storage_id = st.storage_id')
            ->groupBy('st.storage_name');
        $applyUnitFilter($storageQuery);
        $countsByStorageRaw = $storageQuery->findAll();

        $countsByStorage = [];
        foreach ($countsByStorageRaw as $row) {
            $countsByStorage[$row['storage_name']] = (int) $row['total'];
        }

        // Daily registrations last 7 days
        $dailyQuery = $specimenModel
            ->select('DATE(created_at) as reg_date, COUNT(*) as total')
            ->where('created_at >=', date('Y-m-d', strtotime('-6 days')))
            ->groupBy('DATE(created_at)')
            ->orderBy('reg_date', 'ASC');
        $applyUnitFilter($dailyQuery);
        $dailyRegistrationsRaw = $dailyQuery->findAll();

        $dailyRegistrations = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dailyRegistrations[$date] = 0;
        }
        foreach ($dailyRegistrationsRaw as $row) {
            $dailyRegistrations[$row['reg_date']] = (int) $row['total'];
        }

        // Ready to Dispose (eligible specimens)
        $readyQuery = $specimenModel
            ->select('c.duration_id, COUNT(*) as total')
            ->join('containers c', 'specimens.container_id = c.container_id')
            ->where('specimens.status', 1)
            ->where("( (c.duration_id = 1 AND specimens.created_at <= '" . date('Y-m-d H:i:s', strtotime('-6 days')) . "') 
                    OR (c.duration_id = 2 AND specimens.created_at <= '" . date('Y-m-d H:i:s', strtotime('-13 days')) . "')
                    OR (c.duration_id = 3 AND specimens.created_at <= '" . date('Y-m-d H:i:s', strtotime('-20 days')) . "') )")
            ->groupBy('c.duration_id');
        $applyUnitFilter($readyQuery);
        $readyDisposeRaw = $readyQuery->findAll();

        $readyDispose = [1 => 0, 2 => 0, 3 => 0];
        foreach ($readyDisposeRaw as $row) {
            $readyDispose[$row['duration_id']] = (int) $row['total'];
        }

        // Resolve unit label for dashboard title
        if ($role === 3) {
            $unitLabel = 'All Units';
        } elseif (!empty($sessionUnitIds)) {
            $unitNames = $db->table('units')
                ->select('units_name')
                ->whereIn('units_id', $sessionUnitIds)
                ->get()->getResultArray();
            $unitLabel = implode(' / ', array_column($unitNames, 'units_name'));
        } else {
            $unitLabel = 'Unassigned';
        }

        return view('/dashboard', [
            'countsByDuration'   => $durationMap,
            'totalSpecimens'     => $totalSpecimens,
            'countsByStatus'     => $countsByStatus,
            'countsByStorage'    => $countsByStorage,
            'dailyRegistrations' => $dailyRegistrations,
            'readyDispose'       => $readyDispose,
            'unitLabel'          => $unitLabel,
        ]);
    }
}
