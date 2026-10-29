<?php


class FacilityAvailability extends Model
{
    protected $table = 'facility_availability';
    protected $fillable = [
        'facility_id',
        'date',
        'start_time',
        'end_time',
        'is_available',
        'reason',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class, 'facility_id', 'id');
    }
}
