<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Reservation;
use App\Models\Asset;
use App\Models\Facility;

class AdminController extends \Controller
{
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

            $facilityUsage = Facility::selectRaw('name, COUNT(reservations.id) as booking_count')
                                    ->leftJoin('reservations', 'facilities.id', '=', 'reservations.facility_id')
                                    ->groupBy('facilities.id', 'facilities.name')
                                    ->orderBy('booking_count', 'DESC')
                                    ->limit(5)
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
                    'facility_usage' => $facilityUsage,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function getUsers()
    {
        $this->authorize('admin');

        try {
            $role = request()->get('role');
            $status = request()->get('status');
            $search = request()->get('search');
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 10);

            $query = User::with('profile');

            if ($role) {
                $query->where('role', $role);
            }

            if ($status) {
                $query->whereHas('profile', function ($q) {
                    $q->where('is_suspended', request()->get('status') === 'suspended' ? 1 : 0);
                });
            }

            if ($search) {
                $query->where('username', 'LIKE', "%{$search}%")
                      ->orWhere('email', 'LIKE', "%{$search}%");
            }

            $total = $query->count();
            $users = $query->limit($limit)
                          ->offset(($page - 1) * $limit)
                          ->get();

            return response()->json([
                'status' => 'success',
                'data' => $users,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch users',
            ], 400);
        }
    }

    public function suspendUser($id)
    {
        $this->authorize('admin');

        try {
            $user = User::findOrFail($id);
            $profile = UserProfile::where('user_id', $id)->first();

            if (!$profile) {
                $profile = UserProfile::create(['user_id' => $id]);
            }

            $profile->update([
                'is_suspended' => 1,
                'suspension_reason' => request()->get('reason'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User suspended successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to suspend user',
            ], 400);
        }
    }

    public function unsuspendUser($id)
    {
        $this->authorize('admin');

        try {
            $profile = UserProfile::where('user_id', $id)->first();

            if (!$profile) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User profile not found',
                ], 404);
            }

            $profile->update([
                'is_suspended' => 0,
                'suspension_reason' => null,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User unsuspended successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to unsuspend user',
            ], 400);
        }
    }

    public function createUser()
    {
        $this->authorize('admin');

        $this->validate([
            'email' => 'required|email|unique:users',
            'username' => 'required|unique:users',
            'password' => 'required|min:8',
            'role' => 'required|in:student,faculty,staff,admin',
        ]);

        try {
            $user = User::create([
                'email' => request()->get('email'),
                'username' => request()->get('username'),
                'password' => bcrypt(request()->get('password')),
                'role' => request()->get('role'),
                'is_active' => 1,
            ]);

            UserProfile::create([
                'user_id' => $user->id,
                'first_name' => request()->get('first_name'),
                'last_name' => request()->get('last_name'),
                'violations_count' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $user,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create user',
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
