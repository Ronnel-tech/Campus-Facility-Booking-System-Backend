<?php


class Notification extends Model
{
    protected $table = 'notifications';
    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'related_id',
        'related_type',
        'is_read',
        'read_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
