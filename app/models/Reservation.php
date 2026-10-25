<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class Reservation extends Model
{
    protected $table = 'reservations';
    protected $fillable = [
        'user_id',
        'facility_id',
        'reservation_type',
        'purpose',
        'start_date',
        'end_date',
        'attendee_count',
        'status',
        'admin_remarks',
        'approved_at',
        'approved_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'id');
    }

    public function assets()
    {
        return $this->hasMany(ReservationAsset::class, 'reservation_id', 'id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }
}
