<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_photo_path',
        'gauth_id',
        'gauth_type',
        'is_guest',
        'points',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getProfilePhotoUrlAttribute()
    {
        if ($this->profile_photo_path) {
            return asset('storage/' . $this->profile_photo_path);
        }

        // Default placeholder using UI Avatars
        $name = urlencode($this->name);
        return 'https://ui-avatars.com/api/?name=' . $name . '&color=7F9CF5&background=EBF4FF';
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function sendNotification($title, $body, $type = 'system', $relatedId = null)
    {
        return $this->notifications()->create([
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'related_id' => $relatedId,
        ]);
    }

    public function driver()
    {
        return $this->hasOne(Driver::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Add points to user and check for rewards
     * 
     * @param int $amount
     * @return void
     */
    public function addPoints($amount)
    {
        $this->increment('points', $amount);
        $this->checkAndGrantReward();
    }

    /**
     * Deduct points from user
     * 
     * @param int $amount
     * @return bool
     */
    public function deductPoints($amount)
    {
        if ($this->points >= $amount) {
            $this->decrement('points', $amount);
            return true;
        }
        return false;
    }

    /**
     * Check if user qualifies for reward and grant 50% discount coupon
     * 
     * @return void
     */
    public function checkAndGrantReward()
    {
        if ($this->points >= 10) {
            // Create 50% discount coupon for admin to issue to user
            $couponCode = 'PBC50-' . strtoupper(substr(md5($this->id . time()), 0, 6));

            Coupon::create([
                'code' => $couponCode,
                'type' => 'percentage',
                'value' => 50,
                'min_purchase' => 0,
                'expires_at' => now()->addDays(30),
                'is_active' => true,
            ]);

            // Deduct 10 points
            $this->decrement('points', 10);

            // Send notification to user
            $this->sendNotification(
                'Selamat! Anda mendapat kupon diskon 50%!',
                'Gunakan kode: ' . $couponCode . ' untuk diskon 50% di semua produk. Berlaku 30 hari.',
                'reward',
                null
            );
        }
    }
}
