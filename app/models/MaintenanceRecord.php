<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class MaintenanceRecord extends Model
{
    protected $table = 'maintenance_records';
    protected $fillable = [
        'asset_id',
        'issue_description',
        'priority',
        'status',
        'reported_by',
        'assigned_to',
        'reported_at',
        'start_date',
        'completion_date',
        'resolution_notes',
        'cost',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }

    public function reported_by()
    {
        return $this->belongsTo(User::class, 'reported_by', 'id');
    }

    public function assigned_to()
    {
        return $this->belongsTo(User::class, 'assigned_to', 'id');
    }
}
