<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperatingHour extends Model
{
    protected $fillable = [
        'sports_center_id',
        'day_of_week',
        'open_time',
        'close_time',
    ];

    public function sportsCenter()
    {
        return $this->belongsTo(SportsCenter::class);
    }
}
