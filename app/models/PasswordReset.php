<?php


class PasswordReset extends Model
{
    protected $table = 'password_resets';
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public static function isTokenValid($token)
    {
        $reset = self::where('token', $token)->first();
        
        if (!$reset) {
            return false;
        }

        if (strtotime($reset['expires_at']) < time()) {
            self::where('id', $reset['id'])->delete();
            return false;
        }

        return $reset;
    }

    public static function cleanExpiredTokens()
    {
        self::where('expires_at', '<', date('Y-m-d H:i:s'))->delete();
    }
}
