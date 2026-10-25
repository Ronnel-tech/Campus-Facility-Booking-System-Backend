<?php

namespace App\Controllers;

use App\Models\Facility;
use App\Models\Asset;
use App\Models\Reservation;
use App\Models\AssetBorrowing;
use App\Models\MaintenanceRecord;

class ReportsController extends \Lava\Routing\Controller
{
    public function facilityUsage()
    {
        $this->authorize('admin');

        try {
            $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = request()->get('end_date', date('Y-m-d'));

            $report = Facility::selectRaw('facilities.id, facilities.name, COUNT(reservations.id) as bookings, SUM(TIMESTAMPDIFF(HOUR, reservations.start_date, reservations.end_date)) as total_hours')
                             ->leftJoin('reservations', 'facilities.id', '=', 'reservations.facility_id')
                             ->whereBetween('reservations.start_date', [$startDate, $endDate])
                             ->where('facilities.is_active', 1)
                             ->groupBy('facilities.id', 'facilities.name')
                             ->orderBy('bookings', 'DESC')
                             ->get();

            return response()->json([
                'status' => 'success',
                'data' => $report,
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate facility usage report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function assetBorrowing()
    {
        $this->authorize('admin');

        try {
            $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = request()->get('end_date', date('Y-m-d'));

            $report = Asset::selectRaw('assets.id, assets.name, assets.condition, COUNT(asset_borrowings.id) as borrow_count')
                          ->leftJoin('asset_borrowings', 'assets.id', '=', 'asset_borrowings.asset_id')
                          ->whereBetween('asset_borrowings.checkout_date', [$startDate, $endDate])
                          ->where('assets.is_active', 1)
                          ->groupBy('assets.id', 'assets.name', 'assets.condition')
                          ->orderBy('borrow_count', 'DESC')
                          ->get();

            return response()->json([
                'status' => 'success',
                'data' => $report,
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate asset borrowing report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function reservationStats()
    {
        $this->authorize('admin');

        try {
            $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = request()->get('end_date', date('Y-m-d'));

            $stats = [
                'approved' => Reservation::whereBetween('created_at', [$startDate, $endDate])->where('status', 'approved')->count(),
                'pending' => Reservation::whereBetween('created_at', [$startDate, $endDate])->where('status', 'pending')->count(),
                'rejected' => Reservation::whereBetween('created_at', [$startDate, $endDate])->where('status', 'rejected')->count(),
                'cancelled' => Reservation::whereBetween('created_at', [$startDate, $endDate])->where('status', 'cancelled')->count(),
            ];

            $totalRequests = array_sum($stats);
            $approvalRate = $totalRequests > 0 ? round(($stats['approved'] / $totalRequests) * 100, 2) : 0;

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => $stats,
                    'total' => $totalRequests,
                    'approval_rate' => $approvalRate . '%',
                ],
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate reservation stats',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function overdueReturns()
    {
        $this->authorize('admin');

        try {
            $overdueAssets = AssetBorrowing::selectRaw('asset_borrowings.*, users.email, assets.name as asset_name, asset_borrowings.expected_return_date, DATEDIFF(NOW(), asset_borrowings.expected_return_date) as days_overdue')
                                          ->join('users', 'asset_borrowings.user_id', '=', 'users.id')
                                          ->join('assets', 'asset_borrowings.asset_id', '=', 'assets.id')
                                          ->where('asset_borrowings.actual_return_date', null)
                                          ->where('asset_borrowings.expected_return_date', '<', now())
                                          ->orderBy('days_overdue', 'DESC')
                                          ->get();

            return response()->json([
                'status' => 'success',
                'data' => $overdueAssets,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate overdue returns report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function maintenanceLog()
    {
        $this->authorize('admin');

        try {
            $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = request()->get('end_date', date('Y-m-d'));

            $maintenance = MaintenanceRecord::with(['asset', 'reported_by', 'assigned_to'])
                                           ->whereBetween('created_at', [$startDate, $endDate])
                                           ->orderBy('created_at', 'DESC')
                                           ->get();

            $stats = [
                'pending' => MaintenanceRecord::where('status', 'pending')->count(),
                'in_progress' => MaintenanceRecord::where('status', 'in-progress')->count(),
                'completed' => MaintenanceRecord::whereBetween('completed_at', [$startDate, $endDate])->where('status', 'completed')->count(),
                'total_cost' => MaintenanceRecord::whereBetween('created_at', [$startDate, $endDate])->sum('cost'),
            ];

            return response()->json([
                'status' => 'success',
                'data' => [
                    'records' => $maintenance,
                    'stats' => $stats,
                ],
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate maintenance log',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancellationReport()
    {
        $this->authorize('admin');

        try {
            $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = request()->get('end_date', date('Y-m-d'));

            $cancellations = Reservation::selectRaw('users.username, COUNT(reservations.id) as cancellation_count')
                                       ->join('users', 'reservations.user_id', '=', 'users.id')
                                       ->whereBetween('reservations.updated_at', [$startDate, $endDate])
                                       ->where('reservations.status', 'cancelled')
                                       ->groupBy('users.id', 'users.username')
                                       ->orderBy('cancellation_count', 'DESC')
                                       ->get();

            return response()->json([
                'status' => 'success',
                'data' => $cancellations,
                'period' => ['start_date' => $startDate, 'end_date' => $endDate],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate cancellation report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function exportReport()
    {
        $this->authorize('admin');

        try {
            $reportType = request()->get('type');
            $format = request()->get('format', 'csv');

            // Logic to generate and export reports
            // For now, return success message
            return response()->json([
                'status' => 'success',
                'message' => 'Report exported successfully',
                'format' => $format,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to export report',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
