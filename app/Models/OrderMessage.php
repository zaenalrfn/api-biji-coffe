<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderMessage extends Model
{
    protected $fillable = ['order_id', 'sender_id', 'message', 'is_read'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}