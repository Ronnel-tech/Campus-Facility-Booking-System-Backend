<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class AssetCategory extends Model
{
    protected $table = 'asset_categories';
    protected $fillable = [
        'name',
        'description',
        'borrow_limit_days',
        'late_fee_per_day',
        'is_active',
    ];

    public function assets()
    {
        return $this->hasMany(Asset::class, 'category_id', 'id');
    }
}
