<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password_hash',
        'role_id',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function sportsCenters()
    {
        return $this->hasMany(SportsCenter::class, 'owner_id');
    }

    public function organizedGames()
    {
        return $this->hasMany(Game::class, 'organizer_id');
    }

    public function games()
    {
        return $this->belongsToMany(
            Game::class,
            'game_players',
            'player_id',
            'game_id'
        )->withPivot('joined_at');
    }
}
