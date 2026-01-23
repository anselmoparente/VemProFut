<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Field extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'price_per_hour',
        'sports_center_id',
    ];

    protected $casts = [
        'price_per_hour' => 'decimal:2',
    ];

    public function sportsCenter()
    {
        return $this->belongsTo(SportsCenter::class);
    }

    public function games()
    {
        return $this->hasMany(Game::class);
    }
}
