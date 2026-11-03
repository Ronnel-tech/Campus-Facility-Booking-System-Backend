<?php


class AdminController extends \Controller
{
    public function recentActivity()
    {
        $this->authorize('admin');

        try {
            $limit = min(50, max(1, (int) request()->get('limit', 10)));
            $activity = Reservation::with(['user', 'facility'])
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $activity,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch recent activity',
            ], 400);
        }
    }

    public function dashboard()
    {
        $this->authorize('admin');

        try {
            $pendingReservations = Reservation::where('status', 'pending')->count();
            $pendingAssets = Reservation::where('reservation_type', 'asset')->where('status', 'pending')->count();
            $overdueAssets = Asset::where('status', 'overdue')->count();
            $activeReservations = Reservation::where('status', 'approved')->count();

            $recentActivity = Reservation::with(['user', 'facility'])
                                        ->orderBy('created_at', 'DESC')
                                        ->limit(10)
                                        ->get();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'stats' => [
                        'pending_reservations' => $pendingReservations,
                        'pending_assets' => $pendingAssets,
                        'overdue_assets' => $overdueAssets,
                        'active_reservations' => $activeReservations,
                    ],
                    'recent_activity' => $recentActivity,
                    'facility_usage' => [],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard data',
            ], 400);
        }
    }

}
