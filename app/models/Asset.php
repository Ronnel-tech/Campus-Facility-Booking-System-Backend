<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class Asset extends Model
{
    protected $table = 'assets';
    protected $fillable = [
        'asset_tag',
        'name',
        'category_id',
        'description',
        'quantity',
        'quantity_available',
        'condition',
        'status',
        'current_location',
        'purchase_date',
        'purchase_price',
        'is_borrowable',
        'is_active',
    ];

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'category_id', 'id');
    }

    public function borrowings()
    {
        return $this->hasMany(AssetBorrowing::class, 'asset_id', 'id');
    }

    public function reservationAssets()
    {
        return $this->hasMany(ReservationAsset::class, 'asset_id', 'id');
    }
}
