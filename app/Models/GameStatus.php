<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameStatus extends Model
{
    protected $table = 'game_statuses';

    protected $fillable = [
        'code',
        'description',
    ];

    public function games()
    {
        return $this->hasMany(Game::class, 'status_id');
    }
}
