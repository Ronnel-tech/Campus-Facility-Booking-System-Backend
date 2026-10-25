<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class FacilityCategory extends Model
{
    protected $table = 'facility_categories';
    protected $fillable = [
        'name',
        'description',
        'icon',
        'is_active',
    ];

    public function facilities()
    {
        return $this->hasMany(Facility::class, 'category_id', 'id');
    }
}
