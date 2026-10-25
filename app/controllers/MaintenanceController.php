<?php

namespace App\Controllers;

use App\Models\MaintenanceRecord;
use App\Models\Asset;

class MaintenanceController extends \Controller
{
    public function index()
    {
        $this->authorize('admin');

        try {
            $status = request()->get('status');
            $priority = request()->get('priority');
            $page = request()->get('page', 1);
            $limit = request()->get('limit', 10);

            $query = MaintenanceRecord::with(['asset', 'reported_by', 'assigned_to']);

            if ($status) {
                $query->where('status', $status);
            }

            if ($priority) {
                $query->where('priority', $priority);
            }

            $total = $query->count();
            $records = $query->orderBy('created_at', 'DESC')
                           ->limit($limit)
                           ->offset(($page - 1) * $limit)
                           ->get();

            return response()->json([
                'status' => 'success',
                'data' => $records,
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
                'message' => 'Failed to fetch maintenance records',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function show($id)
    {
        $this->authorize('admin');

        try {
            $record = MaintenanceRecord::with(['asset', 'reported_by', 'assigned_to'])
                                      ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $record,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Maintenance record not found',
            ], 404);
        }
    }

    public function store()
    {
        $this->authorize('admin');

        $this->validate([
            'asset_id' => 'required|exists:assets,id',
            'issue_description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        try {
            $record = MaintenanceRecord::create([
                'asset_id' => request()->get('asset_id'),
                'issue_description' => request()->get('issue_description'),
                'priority' => request()->get('priority'),
                'status' => 'pending',
                'reported_by' => auth()->user()->id,
                'assigned_to' => request()->get('assigned_to'),
            ]);

            // Update asset status
            Asset::findOrFail(request()->get('asset_id'))->update(['status' => 'maintenance']);

            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance record created successfully',
                'data' => $record,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create maintenance record',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function update($id)
    {
        $this->authorize('admin');

        try {
            $record = MaintenanceRecord::findOrFail($id);

            $record->update([
                'status' => request()->get('status', $record->status),
                'priority' => request()->get('priority', $record->priority),
                'resolution_notes' => request()->get('resolution_notes', $record->resolution_notes),
                'cost' => request()->get('cost', $record->cost),
            ]);

            if (request()->get('status') === 'completed') {
                $record->update(['completion_date' => now()]);
                Asset::findOrFail($record->asset_id)->update(['status' => 'available']);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance record updated successfully',
                'data' => $record,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update maintenance record',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    public function delete($id)
    {
        $this->authorize('admin');

        try {
            $record = MaintenanceRecord::findOrFail($id);
            $record->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance record deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete maintenance record',
            ], 400);
        }
    }
}
