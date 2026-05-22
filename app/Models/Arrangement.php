<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Arrangement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'destination',
        'price',
        'duration_days',
        'description',
        'discount_percent',
        'is_last_minute',
    ];

    protected $casts = [
        'is_last_minute' => 'boolean',
    ];

    public function bookings(){
        return $this->hasMany(Booking::class);
    }

    public function reviews(){
        return $this->hasMany(Review::class);
    }
}
