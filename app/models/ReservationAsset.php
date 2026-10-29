<?php


class ReservationAsset extends Model
{
    protected $table = 'reservation_assets';
    protected $fillable = [
        'reservation_id',
        'asset_id',
        'quantity',
        'status',
        'checkout_date',
        'return_date',
        'condition_on_checkout',
        'condition_on_return',
        'damage_report',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class, 'reservation_id', 'id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }
}
