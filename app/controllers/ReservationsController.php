<?php


class ReservationsController extends \Controller
{
    public function index()
    {
        try {
            $user = auth()->user();
            $status = request()->get('status');
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 10);

            $query = Reservation::where('user_id', $user->id);

            if ($status) {
                $query->where('status', $status);
            }

            $total = $query->count();
            $reservations = $query->with(['facility', 'assets'])
                                 ->orderBy('start_date', 'DESC')
                                 ->limit($limit)
                                 ->offset(($page - 1) * $limit)
                                 ->get();

            return response()->json([
                'status' => 'success',
                'data' => $reservations,
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
                'message' => 'Failed to fetch reservations',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $user = auth()->user();
            $reservation = Reservation::with(['facility', 'assets', 'user'])
                                     ->findOrFail($id);

            if (
                !in_array($user['role'] ?? null, ['admin', 'faculty', 'staff'], true)
                && (int) $reservation->user_id !== (int) $user['id']
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Reservation not found',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $reservation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Reservation not found',
            ], 404);
        }
    }

    public function store()
    {
        $this->authorizeAnyRole(['faculty', 'staff']);

        $this->validate([
            'reservation_type' => 'required|in:facility,asset,combined',
            'facility_id' => 'required_if:reservation_type,facility,combined',
            'purpose' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'attendee_count' => 'required_if:reservation_type,facility,combined|integer|min:1',
        ]);

        try {
            $user = auth()->user();

            $reservation = Reservation::create([
                'user_id' => $user->id,
                'facility_id' => request()->get('facility_id'),
                'reservation_type' => request()->get('reservation_type'),
                'purpose' => request()->get('purpose'),
                'start_date' => request()->get('start_date'),
                'end_date' => request()->get('end_date'),
                'attendee_count' => request()->get('attendee_count'),
                'status' => 'pending',
            ]);

            // Handle asset reservations
            $assets = request()->get('assets', []);
            foreach ($assets as $assetData) {
                ReservationAsset::create([
                    'reservation_id' => $reservation->id,
                    'asset_id' => $assetData['asset_id'],
                    'quantity' => $assetData['quantity'],
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation created successfully',
                'data' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create reservation',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function cancel($id)
    {
        try {
            $reservation = Reservation::findOrFail($id);
            $this->authorize('update', $reservation);

            if ($reservation->status == 'completed' || $reservation->status == 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot cancel this reservation',
                ], 400);
            }

            $reservation->update(['status' => 'cancelled']);

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation cancelled successfully',
                'data' => $reservation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to cancel reservation',
            ], 400);
        }
    }

    // Admin endpoints
    public function allReservations()
    {
        $this->authorize('admin');

        try {
            $status = request()->get('status');
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 10);

            $query = Reservation::query();

            if ($status) {
                $query->where('status', $status);
            }

            $total = $query->count();
            $reservations = $query->with(['user', 'facility', 'assets'])
                                 ->orderBy('created_at', 'DESC')
                                 ->limit($limit)
                                 ->offset(($page - 1) * $limit)
                                 ->get();

            return response()->json([
                'status' => 'success',
                'data' => $reservations,
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
                'message' => 'Failed to fetch reservations',
            ], 400);
        }
    }

    public function approve($id)
    {
        $this->authorize('admin');

        try {
            $reservation = Reservation::findOrFail($id);

            $reservation->update([
                'status' => 'approved',
                'approved_by' => auth()->user()->id,
                'approved_at' => now(),
            ]);

            ReservationAsset::where('reservation_id', $id)
                           ->update(['status' => 'allocated']);

            // Send notification
            $this->sendNotification($reservation->user_id, 'reservation_approved', 'Reservation Approved', 'Your reservation has been approved');

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation approved successfully',
                'data' => $reservation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to approve reservation',
            ], 400);
        }
    }

    public function reject($id)
    {
        $this->authorize('admin');

        try {
            $reservation = Reservation::findOrFail($id);

            $reservation->update([
                'status' => 'rejected',
                'admin_remarks' => request()->get('remarks'),
                'approved_by' => auth()->user()->id,
            ]);

            // Send notification
            $this->sendNotification($reservation->user_id, 'reservation_rejected', 'Reservation Rejected', 'Your reservation has been rejected');

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation rejected successfully',
                'data' => $reservation,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reject reservation',
            ], 400);
        }
    }

    private function sendNotification($userId, $type, $title, $message)
    {
        // Notification logic
        $notificationModel = model('Notification');
        $notificationModel->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => 0,
        ]);
    }
}
