<?php


class ReportsController extends \Controller
{
    public function facilityUsage()
    {
        $this->authorize('admin');

        try {
            $startDate = request()->get('start_date', date('Y-m-d', strtotime('-30 days')));
            $endDate = request()->get('end_date', date('Y-m-d'));

            $report = array_map(static function ($facility) use ($startDate, $endDate) {
                $reservations = Reservation::where('facility_id', $facility['id'])
                    ->where('start_date', '>=', $startDate)
                    ->where('start_date', '<=', $endDate)
                    ->where('status', 'approved')
                    ->get();
                $totalHours = 0;
                foreach ($reservations as $reservation) {
                    $start = strtotime($reservation['start_date']);
                    $end = strtotime($reservation['end_date']);
                    if ($start !== false && $end !== false && $end > $start) {
                        $totalHours += ($end - $start) / 3600;
                    }
                }

                return [
                    'id' => $facility['id'],
                    'name' => $facility['name'],
                    'bookings' => count($reservations),
                    'total_hours' => round($totalHours, 2),
                ];
            }, Facility::where('is_active', 1)->get());

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

            $report = [];
            foreach (Asset::where('is_active', 1)->get() as $asset) {
                $borrowings = AssetBorrowing::where('asset_id', $asset['id'])
                    ->where('checkout_date', '>=', $startDate)
                    ->where('checkout_date', '<=', $endDate)
                    ->get();
                $report[] = [
                    'id' => $asset['id'],
                    'name' => $asset['name'],
                    'condition' => $asset['condition'],
                    'borrow_count' => count($borrowings),
                ];
            }
            usort($report, static fn ($a, $b) => $b['borrow_count'] <=> $a['borrow_count']);

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
                'approved' => Reservation::where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate)->where('status', 'approved')->count(),
                'pending' => Reservation::where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate)->where('status', 'pending')->count(),
                'rejected' => Reservation::where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate)->where('status', 'rejected')->count(),
                'cancelled' => Reservation::where('created_at', '>=', $startDate)->where('created_at', '<=', $endDate)->where('status', 'cancelled')->count(),
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
            $overdueAssets = [];
            foreach (AssetBorrowing::all() as $borrowing) {
                if ($borrowing['actual_return_date'] !== null) {
                    continue;
                }

                $expectedReturn = strtotime($borrowing['expected_return_date']);
                if ($expectedReturn === false || $expectedReturn >= time()) {
                    continue;
                }

                $user = User::find($borrowing['user_id']);
                $asset = Asset::find($borrowing['asset_id']);
                $borrowing['email'] = $user ? $user->email : null;
                $borrowing['asset_name'] = $asset ? $asset->name : null;
                $borrowing['days_overdue'] = (int) floor((time() - $expectedReturn) / 86400);
                $overdueAssets[] = $borrowing;
            }
            usort($overdueAssets, static fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

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
                                           ->where('created_at', '>=', $startDate)
                                           ->where('created_at', '<=', $endDate)
                                           ->orderBy('created_at', 'DESC')
                                           ->get();

            $maintenanceInRange = MaintenanceRecord::where('created_at', '>=', $startDate)
                ->where('created_at', '<=', $endDate)
                ->get();
            $totalCost = 0;
            foreach ($maintenanceInRange as $record) {
                $totalCost += (float) ($record['cost'] ?? 0);
            }

            $stats = [
                'pending' => MaintenanceRecord::where('status', 'pending')->count(),
                'in_progress' => MaintenanceRecord::where('status', 'in-progress')->count(),
                'completed' => MaintenanceRecord::where('completion_date', '>=', $startDate)->where('completion_date', '<=', $endDate)->where('status', 'completed')->count(),
                'total_cost' => round($totalCost, 2),
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

            $counts = [];
            foreach (Reservation::where('updated_at', '>=', $startDate)
                ->where('updated_at', '<=', $endDate)
                ->where('status', 'cancelled')
                ->get() as $reservation) {
                $user = User::find($reservation['user_id']);
                $username = $user ? $user->username : 'Unknown';
                $counts[$username] = ($counts[$username] ?? 0) + 1;
            }
            $cancellations = [];
            foreach ($counts as $username => $count) {
                $cancellations[] = [
                    'username' => $username,
                    'cancellation_count' => $count,
                ];
            }
            usort($cancellations, static fn ($a, $b) => $b['cancellation_count'] <=> $a['cancellation_count']);

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
