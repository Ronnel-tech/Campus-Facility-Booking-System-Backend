<?php


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
            error_log('Maintenance listing failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch maintenance records',
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
            $asset = Asset::findOrFail(request()->get('asset_id'));
            $transactionModel = new MaintenanceRecord();
            $transactionModel->_transaction();
            try {
                $record = MaintenanceRecord::create([
                    'asset_id' => $asset->id,
                    'issue_description' => request()->get('issue_description'),
                    'priority' => request()->get('priority'),
                    'status' => 'pending',
                    'reported_by' => auth()->user()->id,
                    'assigned_to' => request()->get('assigned_to'),
                ]);
                $asset->update(['status' => 'maintenance']);
                AuditLog::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'maintenance_created',
                    'entity_type' => 'maintenance_record',
                    'entity_id' => $record->id,
                    'new_values' => json_encode(['asset_id' => $asset->id, 'status' => 'pending']),
                ]);
                $transactionModel->commit();
            } catch (\Exception $e) {
                $transactionModel->_rollback();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance record created successfully',
                'data' => $record,
            ], 201);
        } catch (\Exception $e) {
            error_log('Maintenance creation failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create maintenance record',
            ], 400);
        }
    }

    public function update($id)
    {
        $this->authorize('admin');

        try {
            $record = MaintenanceRecord::findOrFail($id);
            $oldStatus = $record->status;
            $newStatus = request()->get('status', $oldStatus);
            $allowedStatuses = ['pending', 'in-progress', 'completed', 'cancelled'];
            if (!in_array($newStatus, $allowedStatuses, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid maintenance status',
                ], 422);
            }
            if ($oldStatus === 'completed' && $newStatus !== 'completed') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Completed maintenance records cannot be reopened',
                ], 422);
            }
            if ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cancelled maintenance records cannot be reopened',
                ], 422);
            }

            $transactionModel = new MaintenanceRecord();
            $transactionModel->_transaction();
            try {
                $record->update([
                    'status' => $newStatus,
                    'priority' => request()->get('priority', $record->priority),
                    'resolution_notes' => request()->get('resolution_notes', $record->resolution_notes),
                    'cost' => request()->get('cost', $record->cost),
                    'start_date' => request()->get('start_date', $record->start_date),
                    'completion_date' => $newStatus === 'completed' ? ($record->completion_date ?: now()) : $record->completion_date,
                ]);

                if (in_array($newStatus, ['completed', 'cancelled'], true)) {
                    $activeMaintenance = MaintenanceRecord::where('asset_id', $record->asset_id)
                        ->where('id', '!=', $record->id)
                        ->where('status', '!=', 'completed')
                        ->where('status', '!=', 'cancelled')
                        ->count();
                    if ($activeMaintenance === 0) {
                        Asset::findOrFail($record->asset_id)->update(['status' => 'available']);
                    }
                } else {
                    Asset::findOrFail($record->asset_id)->update(['status' => 'maintenance']);
                }

                AuditLog::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'maintenance_updated',
                    'entity_type' => 'maintenance_record',
                    'entity_id' => $record->id,
                    'old_values' => json_encode(['status' => $oldStatus]),
                    'new_values' => json_encode(['status' => $newStatus]),
                ]);
                if ($record->reported_by) {
                    $this->sendNotification($record->reported_by, 'maintenance_updated', 'Maintenance Updated', 'A maintenance record has been updated', $record->id, 'maintenance_record');
                }
                $transactionModel->commit();
            } catch (\Exception $e) {
                $transactionModel->_rollback();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance record updated successfully',
                'data' => $record,
            ], 200);
        } catch (\Exception $e) {
            error_log('Maintenance update failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update maintenance record',
            ], 400);
        }
    }

    public function delete($id)
    {
        $this->authorize('admin');

        try {
            $record = MaintenanceRecord::findOrFail($id);
            if (in_array($record->status, ['completed', 'cancelled'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Completed or cancelled maintenance records cannot be deleted',
                ], 422);
            }

            $transactionModel = new MaintenanceRecord();
            $transactionModel->_transaction();
            try {
                $record->delete();
                $activeMaintenance = MaintenanceRecord::where('asset_id', $record->asset_id)
                    ->where('status', '!=', 'completed')
                    ->where('status', '!=', 'cancelled')
                    ->count();
                if ($activeMaintenance === 0) {
                    Asset::findOrFail($record->asset_id)->update(['status' => 'available']);
                }
                AuditLog::create([
                    'user_id' => auth()->user()->id,
                    'action' => 'maintenance_deleted',
                    'entity_type' => 'maintenance_record',
                    'entity_id' => $id,
                    'old_values' => json_encode(['status' => $record->status, 'asset_id' => $record->asset_id]),
                ]);
                $transactionModel->commit();
            } catch (\Exception $e) {
                $transactionModel->_rollback();
                throw $e;
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Maintenance record deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            error_log('Maintenance deletion failed: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete maintenance record',
            ], 400);
        }
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
