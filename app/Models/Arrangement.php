<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;

class Arrangement extends Model
{
    protected $fillable = [
        'title',
        'destination',
        'price',
        'duration_days',
        'description',
        'discount_percent',
        'is_last_minute',
    ];

    public function bookings(){
        return $this->hasMany(Booking::class);
    }
}
