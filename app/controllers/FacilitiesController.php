<?php

namespace App\Controllers;

use App\Models\Facility;
use App\Models\FacilityCategory;

class FacilitiesController extends \Lava\Routing\Controller
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
                $query->where('name', 'LIKE', "%{$search}%")
                      ->orWhere('description', 'LIKE', "%{$search}%");
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
                'error' => $e->getMessage(),
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

            $availability = $facility->availability()
                                    ->whereBetween('date', [$startDate, $endDate])
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
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function update($id)
    {
        $this->authorize('admin');

        try {
            $facility = Facility::findOrFail($id);

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
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update facility',
                'error' => $e->getMessage(),
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
}
