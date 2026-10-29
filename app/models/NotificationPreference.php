<?php


class NotificationPreference extends Model
{
    protected $table = 'notification_preferences';
    protected $fillable = [
        'user_id',
        'email_reservation_approved',
        'email_reservation_rejected',
        'email_asset_checkout',
        'email_asset_return_reminder',
        'email_booking_reminder',
        'push_notification_enabled',
        'sms_reminder_enabled',
        'reminder_before_hours',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
