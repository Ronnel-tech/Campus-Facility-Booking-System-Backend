<?php


class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];
    protected $hidden = ['password'];

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'user_id', 'id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id', 'id');
    }
}
