<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SportsCenter extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'street',
        'number',
        'complement',
        'neighborhood',
        'city',
        'state',
        'zip_code',
        'latitude',
        'longitude',
        'owner_id',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function fields()
    {
        return $this->hasMany(Field::class);
    }

    public function operatingHours()
    {
        return $this->hasMany(OperatingHour::class);
    }
}
