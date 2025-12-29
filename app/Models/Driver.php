<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'photo_url',
        'phone',
        'current_lat',
        'current_lng',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_lat' => 'double',
        'current_lng' => 'double',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
