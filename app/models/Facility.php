<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class Facility extends Model
{
    protected $table = 'facilities';
    protected $fillable = [
        'name',
        'category_id',
        'description',
        'capacity',
        'location',
        'amenities',
        'rules',
        'status',
        'booking_limit_hours',
        'max_bookings_per_day',
        'is_active',
    ];

    protected $casts = [
        'amenities' => 'json',
    ];

    public function category()
    {
        return $this->belongsTo(FacilityCategory::class, 'category_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'facility_id', 'id');
    }

    public function availability()
    {
        return $this->hasMany(FacilityAvailability::class, 'facility_id', 'id');
    }
}
