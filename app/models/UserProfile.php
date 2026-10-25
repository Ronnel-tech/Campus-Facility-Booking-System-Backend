<?php

namespace App\Models;

use Lava\Database\Orm\Model;

class UserProfile extends Model
{
    protected $table = 'user_profiles';
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'department',
        'student_id',
        'faculty_id',
        'staff_id',
        'profile_picture',
        'bio',
        'violations_count',
        'is_suspended',
        'suspension_reason',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
