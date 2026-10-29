<?php


class AssetBorrowing extends Model
{
    protected $table = 'asset_borrowings';
    protected $fillable = [
        'reservation_asset_id',
        'user_id',
        'asset_id',
        'checkout_date',
        'expected_return_date',
        'actual_return_date',
        'condition_on_checkout',
        'condition_on_return',
        'damage_report',
        'late_fee',
        'damage_cost',
        'status',
    ];

    public function reservationAsset()
    {
        return $this->belongsTo(ReservationAsset::class, 'reservation_asset_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class, 'asset_id', 'id');
    }
}
