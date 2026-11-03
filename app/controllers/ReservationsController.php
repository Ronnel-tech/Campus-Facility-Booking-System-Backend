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
            $type = request()->get('reservation_type');
            $facilityId = request()->get('facility_id');
            $start = request()->get('start_date');
            $end = request()->get('end_date');
            $assets = request()->get('assets', []);

            if (in_array($type, ['facility', 'combined'], true)) {
                $facility = Facility::findOrFail($facilityId);
                $this->assertFacilityCanBeReserved($facility, $start, $end, request()->get('attendee_count'));
                $this->assertNoFacilityConflict($facilityId, $start, $end);
            } elseif ($facilityId) {
                throw new \RuntimeException('Facility is not valid for this reservation type');
            }

            if (in_array($type, ['asset', 'combined'], true)) {
                $assets = $this->validateRequestedAssets($assets);
            } elseif (!empty($assets)) {
                throw new \RuntimeException('Assets are only allowed for asset or combined reservations');
            }

            $transactionModel = new Reservation();
            $transactionModel->transaction();
            try {
                $reservation = Reservation::create([
                    'user_id' => $user->id,
                    'facility_id' => $facilityId ?: null,
                    'reservation_type' => $type,
                    'purpose' => request()->get('purpose'),
                    'start_date' => $start,
                    'end_date' => $end,
                    'attendee_count' => request()->get('attendee_count'),
                    'status' => 'pending',
                ]);

                foreach ($assets as $assetData) {
                    ReservationAsset::create([
                        'reservation_id' => $reservation->id,
                        'asset_id' => $assetData['asset_id'],
                        'quantity' => $assetData['quantity'],
                        'status' => 'pending',
                    ]);
                }

                AuditLog::create([
                    'user_id' => $user->id,
                    'action' => 'reservation_created',
                    'entity_type' => 'reservation',
                    'entity_id' => $reservation->id,
                    'new_values' => json_encode(['status' => 'pending', 'reservation_type' => $type]),
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                ]);

                $this->sendNotification(
                    $user->id,
                    'reservation_submitted',
                    'Reservation Submitted',
                    'Your reservation request has been submitted for review',
                    $reservation->id,
                    'reservation'
                );
                $transactionModel->commit();
            } catch (\Exception $e) {
                $transactionModel->rollback();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Reservation created successfully',
                'data' => $reservation,
            ], 201);
        } catch (\Exception $e) {
            error_log('Reservation creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to create reservation. Please check the submitted details.',
            ], 422);
        }
    }

    public function cancel($id)
    {
        try {
            $user = auth()->user();
            $reservation = Reservation::findOrFail($id);

            if (
                ($user['role'] ?? null) !== 'admin'
                && (int) $reservation->user_id !== (int) $user['id']
            ) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }

            if (!in_array($reservation->status, ['pending', 'approved'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending or approved reservations can be cancelled',
                ], 400);
            }

            $oldStatus = $reservation->status;
            $reservation->update(['status' => 'cancelled']);
            ReservationAsset::where('reservation_id', $id)
                ->update(['status' => 'cancelled']);
            AuditLog::create([
                'user_id' => $user->id,
                'action' => 'reservation_cancelled',
                'entity_type' => 'reservation',
                'entity_id' => $id,
                'old_values' => json_encode(['status' => $oldStatus]),
                'new_values' => json_encode(['status' => 'cancelled']),
            ]);
            $this->sendNotification(
                $reservation->user_id,
                'reservation_cancelled',
                'Reservation Cancelled',
                'Your reservation has been cancelled',
                $id,
                'reservation'
            );

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

            $query = Reservation::where('id', '>', 0);

            if ($status) {
                $query->where('status', $status);
            }

            $total = $query->count();
            $reservations = $query->orderBy('created_at', 'DESC')
                                 ->limit($limit)
                                 ->offset(($page - 1) * $limit)
                                 ->get();
            $reservations = array_map(static function ($reservation) {
                $user = User::find($reservation['user_id']);
                $facility = $reservation['facility_id']
                    ? Facility::find($reservation['facility_id'])
                    : null;

                $reservation['user'] = $user ? [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                ] : null;
                $reservation['facility'] = $facility ? [
                    'id' => $facility['id'],
                    'name' => $facility['name'],
                ] : null;

                return $reservation;
            }, $reservations);

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

    public function approve($id)
    {
        $this->authorize('admin');

        try {
            $reservation = Reservation::findOrFail($id);
            if ($reservation->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending reservations can be approved',
                ], 400);
            }
            $oldStatus = $reservation->status;

            $reservation->update([
                'status' => 'approved',
                'approved_by' => auth()->user()->id,
                'approved_at' => now(),
            ]);

            ReservationAsset::where('reservation_id', $id)
                           ->update(['status' => 'allocated']);

            AuditLog::create([
                'user_id' => auth()->user()->id,
                'action' => 'reservation_approved',
                'entity_type' => 'reservation',
                'entity_id' => $id,
                'old_values' => json_encode(['status' => $oldStatus]),
                'new_values' => json_encode(['status' => 'approved']),
            ]);
            $this->sendNotification($reservation->user_id, 'reservation_approved', 'Reservation Approved', 'Your reservation has been approved', $id, 'reservation');

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
            if ($reservation->status !== 'pending') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only pending reservations can be rejected',
                ], 400);
            }
            $oldStatus = $reservation->status;

            $reservation->update([
                'status' => 'rejected',
                'admin_remarks' => request()->get('remarks'),
                'approved_by' => auth()->user()->id,
            ]);

            AuditLog::create([
                'user_id' => auth()->user()->id,
                'action' => 'reservation_rejected',
                'entity_type' => 'reservation',
                'entity_id' => $id,
                'old_values' => json_encode(['status' => $oldStatus]),
                'new_values' => json_encode(['status' => 'rejected']),
            ]);
            $this->sendNotification($reservation->user_id, 'reservation_rejected', 'Reservation Rejected', 'Your reservation has been rejected', $id, 'reservation');

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

    private function assertFacilityCanBeReserved($facility, $start, $end, $attendeeCount)
    {
        if (!$facility->is_active || $facility->status !== 'active') {
            throw new \RuntimeException('The selected facility is not available');
        }

        if ($attendeeCount !== null && (int) $attendeeCount > (int) $facility->capacity) {
            throw new \RuntimeException('Attendee count exceeds facility capacity');
        }

        $startTimestamp = strtotime($start);
        $endTimestamp = strtotime($end);
        if ($startTimestamp === false || $endTimestamp === false) {
            throw new \RuntimeException('Reservation dates are invalid');
        }

        $durationHours = ($endTimestamp - $startTimestamp) / 3600;
        $bookingLimitHours = (int) ($facility->booking_limit_hours ?? 0);
        if ($bookingLimitHours > 0 && $durationHours > $bookingLimitHours) {
            throw new \RuntimeException('Reservation exceeds the facility booking limit');
        }

        $dayStart = date('Y-m-d', $startTimestamp);
        $dayEnd = date('Y-m-d', $endTimestamp);
        $dailyBookings = Reservation::where('facility_id', $facility->id)
            ->where('start_date', '>=', $dayStart . ' 00:00:00')
            ->where('start_date', '<', date('Y-m-d', strtotime($dayEnd . ' +1 day')) . ' 00:00:00')
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->count();
        if ($dailyBookings >= (int) $facility->max_bookings_per_day) {
            throw new \RuntimeException('The facility has reached its daily booking limit');
        }

        $availability = FacilityAvailability::where('facility_id', $facility->id)
            ->where('date', '>=', $dayStart)
            ->where('date', '<=', $dayEnd)
            ->get();
        if (count($availability) > 0) {
            $hasMatchingWindow = false;
            foreach ($availability as $slot) {
                if (!(int) $slot['is_available']) {
                    continue;
                }
                $slotStart = strtotime($slot['date'] . ' ' . $slot['start_time']);
                $slotEnd = strtotime($slot['date'] . ' ' . $slot['end_time']);
                if ($startTimestamp >= $slotStart && $endTimestamp <= $slotEnd) {
                    $hasMatchingWindow = true;
                    break;
                }
            }
            if (!$hasMatchingWindow) {
                throw new \RuntimeException('The facility is unavailable during the requested period');
            }
        }
    }

    private function assertNoFacilityConflict($facilityId, $start, $end)
    {
        $conflicts = Reservation::where('facility_id', $facilityId)
            ->where('status', '!=', 'cancelled')
            ->where('status', '!=', 'rejected')
            ->where('start_date', '<', $end)
            ->where('end_date', '>', $start)
            ->get();

        if (count($conflicts) > 0) {
            throw new \RuntimeException('The facility is already reserved during the requested period');
        }
    }

    private function validateRequestedAssets($assets)
    {
        if (!is_array($assets) || count($assets) === 0) {
            throw new \RuntimeException('At least one asset is required');
        }

        $validated = [];
        $requestedAssetIds = [];
        foreach ($assets as $assetData) {
            $assetId = (int) ($assetData['asset_id'] ?? 0);
            $quantity = (int) ($assetData['quantity'] ?? 0);
            if ($assetId < 1 || $quantity < 1) {
                throw new \RuntimeException('Each asset request must include a valid asset and quantity');
            }

            if (isset($requestedAssetIds[$assetId])) {
                throw new \RuntimeException('The same asset cannot be requested more than once');
            }
            $requestedAssetIds[$assetId] = true;

            $asset = Asset::findOrFail($assetId);
            if (!(int) $asset->is_active || !(int) $asset->is_borrowable || $asset->status !== 'available') {
                throw new \RuntimeException('One or more requested assets are not available');
            }
            if ($quantity > (int) $asset->quantity_available) {
                throw new \RuntimeException('Requested asset quantity is not available');
            }

            $validated[] = ['asset_id' => $assetId, 'quantity' => $quantity];
        }

        return $validated;
    }

    private function sendNotification($userId, $type, $title, $message, $relatedId = null, $relatedType = null)
    {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'related_id' => $relatedId,
            'related_type' => $relatedType,
            'is_read' => 0,
        ]);
    }
}
