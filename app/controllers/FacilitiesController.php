<?php


class FacilitiesController extends \Controller
{
    public function index()
    {
        try {
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 12);
            $category = request()->get('category');
            $search = request()->get('search');
            $status = request()->get('status', 'active');

            $query = Facility::where('is_active', 1);

            if ($status) {
                $query->where('status', $status);
            }

            if ($category) {
                $query->where('category_id', $category);
            }

            if ($search) {
                $query->where('name', 'LIKE', $search)
                      ->orWhere('description', 'LIKE', $search);
            }

            $total = $query->count();
            $facilities = $query->limit($limit)
                               ->offset(($page - 1) * $limit)
                               ->get();

            return response()->json([
                'status' => 'success',
                'data' => $facilities,
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
                'message' => 'Failed to fetch facilities',
            ], 400);
        }
    }

    public function show($id)
    {
        try {
            $facility = Facility::with(['category', 'reservations'])
                               ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $facility,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Facility not found',
            ], 404);
        }
    }

    public function getAvailability($id)
    {
        try {
            $facility = Facility::findOrFail($id);
            $startDate = request()->get('start_date');
            $endDate = request()->get('end_date');

            if (!$startDate || !$endDate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'start_date and end_date are required',
                ], 422);
            }

            $availability = $facility->availability()
                                    ->where('date', '>=', $startDate)
                                    ->where('date', '<=', $endDate)
                                    ->get();

            return response()->json([
                'status' => 'success',
                'data' => $availability,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch availability',
            ], 400);
        }
    }

    public function store()
    {
        $this->authorize('admin');

        $this->validate([
            'name' => 'required|string',
            'category_id' => 'required|exists:facility_categories,id',
            'capacity' => 'required|integer|min:1',
            'location' => 'required|string',
        ]);

        try {
            $facility = Facility::create([
                'name' => request()->get('name'),
                'category_id' => request()->get('category_id'),
                'description' => request()->get('description'),
                'capacity' => request()->get('capacity'),
                'location' => request()->get('location'),
                'amenities' => json_encode(request()->get('amenities', [])),
                'rules' => request()->get('rules'),
                'status' => 'active',
                'booking_limit_hours' => request()->get('booking_limit_hours', 4),
                'max_bookings_per_day' => request()->get('max_bookings_per_day', 2),
                'is_active' => 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Facility created successfully',
                'data' => $facility,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create facility',
            ], 400);
        }
    }

    public function update($id)
    {
        $this->authorize('admin');

        try {
            $facility = Facility::findOrFail($id);

            // CRITICAL FIX #6: Check if booking limits reduction violates existing reservations
            $newBookingLimitHours = request()->get('booking_limit_hours');
            $newMaxBookingsPerDay = request()->get('max_bookings_per_day');

            if ($newBookingLimitHours !== null && (int) $newBookingLimitHours < (int) $facility->booking_limit_hours) {
                $violatingReservations = Reservation::where('facility_id', $id)
                    ->where('status', 'approved')
                    ->get();

                $violatingCount = 0;
                foreach ($violatingReservations as $reservation) {
                    $startTimestamp = strtotime($reservation->start_date);
                    $endTimestamp = strtotime($reservation->end_date);
                    $durationHours = ($endTimestamp - $startTimestamp) / 3600;
                    if ($durationHours > (int) $newBookingLimitHours) {
                        $violatingCount++;
                    }
                }

                if ($violatingCount > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot reduce booking limit. ' . $violatingCount . ' existing approved reservation(s) exceed the new limit.',
                        'violating_reservations_count' => $violatingCount,
                    ], 422);
                }
            }

            if ($newMaxBookingsPerDay !== null && (int) $newMaxBookingsPerDay < (int) $facility->max_bookings_per_day) {
                $dayStart = date('Y-m-d');
                $dayEnd = date('Y-m-d', strtotime('+1 day'));

                $violatingDays = [];
                for ($i = 0; $i < 365; $i++) {
                    $checkDate = date('Y-m-d', strtotime("+$i days"));
                    $dailyBookings = Reservation::where('facility_id', $id)
                        ->where('status', 'approved')
                        ->where('start_date', '>=', $checkDate . ' 00:00:00')
                        ->where('start_date', '<', date('Y-m-d', strtotime("$checkDate +1 day")) . ' 00:00:00')
                        ->count();

                    if ($dailyBookings > (int) $newMaxBookingsPerDay) {
                        $violatingDays[] = $checkDate;
                    }
                }

                if (count($violatingDays) > 0) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Cannot reduce max bookings per day. ' . count($violatingDays) . ' day(s) have more approved bookings than the new limit.',
                        'violating_days_count' => count($violatingDays),
                    ], 422);
                }
            }

            $facility->update([
                'name' => request()->get('name', $facility->name),
                'category_id' => request()->get('category_id', $facility->category_id),
                'description' => request()->get('description', $facility->description),
                'capacity' => request()->get('capacity', $facility->capacity),
                'location' => request()->get('location', $facility->location),
                'amenities' => json_encode(request()->get('amenities', json_decode($facility->amenities))),
                'rules' => request()->get('rules', $facility->rules),
                'status' => request()->get('status', $facility->status),
                'booking_limit_hours' => request()->get('booking_limit_hours', $facility->booking_limit_hours),
                'max_bookings_per_day' => request()->get('max_bookings_per_day', $facility->max_bookings_per_day),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Facility updated successfully',
                'data' => $facility,
            ], 200);
        } catch (\Exception $e) {
            error_log('Facility update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update facility',
            ], 400);
        }
    }

    public function delete($id)
    {
        $this->authorize('admin');

        try {
            $facility = Facility::findOrFail($id);
            $facility->update(['is_active' => 0]);

            return response()->json([
                'status' => 'success',
                'message' => 'Facility deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete facility',
            ], 400);
        }
    }

    public function categories()
    {
        try {
            $categories = FacilityCategory::where('is_active', 1)->get();

            return response()->json([
                'status' => 'success',
                'data' => $categories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch categories',
            ], 400);
        }
    }

    public function storeAvailability()
    {
        $this->authorize('admin');

        $this->validate([
            'facility_id' => 'required|exists:facilities,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        try {
            $facilityId = request()->get('facility_id');
            $date = request()->get('date');
            $startTime = request()->get('start_time');
            $endTime = request()->get('end_time');

            // HIGH PRIORITY FIX #16: Facility Availability Conflict Detection
            $this->validateFacilityAvailabilityWindows($facilityId, $date, $startTime, $endTime);

            $availability = FacilityAvailability::create([
                'facility_id' => $facilityId,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_available' => 1,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Availability window created successfully',
                'data' => $availability,
            ], 201);
        } catch (\Exception $e) {
            error_log('Availability creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    public function updateAvailability($id)
    {
        $this->authorize('admin');

        try {
            $availability = FacilityAvailability::findOrFail($id);

            $facilityId = request()->get('facility_id', $availability->facility_id);
            $date = request()->get('date', $availability->date);
            $startTime = request()->get('start_time', $availability->start_time);
            $endTime = request()->get('end_time', $availability->end_time);

            // HIGH PRIORITY FIX #16: Facility Availability Conflict Detection
            $this->validateFacilityAvailabilityWindows($facilityId, $date, $startTime, $endTime, $id);

            $availability->update([
                'facility_id' => $facilityId,
                'date' => $date,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'is_available' => request()->get('is_available', $availability->is_available),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Availability window updated successfully',
                'data' => $availability,
            ], 200);
        } catch (\Exception $e) {
            error_log('Availability update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    private function validateFacilityAvailabilityWindows($facilityId, $date, $startTime, $endTime, $excludeId = null)
    {
        $startSeconds = strtotime($startTime);
        $endSeconds = strtotime($endTime);

        if ($startSeconds === false || $endSeconds === false) {
            throw new \RuntimeException('Invalid start_time or end_time format');
        }

        if ($endSeconds <= $startSeconds) {
            throw new \RuntimeException('end_time must be after start_time');
        }

        $query = FacilityAvailability::where('facility_id', $facilityId)
            ->where('date', $date);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingWindows = $query->get();

        foreach ($existingWindows as $window) {
            $existingStart = strtotime($window['start_time']);
            $existingEnd = strtotime($window['end_time']);

            // Check for overlap: new window starts before existing ends AND new window ends after existing starts
            if ($startSeconds < $existingEnd && $endSeconds > $existingStart) {
                throw new \RuntimeException('This availability window overlaps with an existing window on the same date');
            }
        }
    }
}
