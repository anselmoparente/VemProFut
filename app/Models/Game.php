<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'game_date',
        'start_time',
        'end_time',
        'max_players',
        'status_id',
        'field_id',
        'organizer_id',
    ];

    protected $casts = [
        'game_date' => 'date:Y-m-d',
        'start_time' => 'string',
        'end_time' => 'string',
        'max_players' => 'integer',
        'status_id' => 'integer',
        'field_id' => 'integer',
        'organizer_id' => 'integer',
    ];

    public function status()
    {
        return $this->belongsTo(GameStatus::class, 'status_id');
    }

    public function field()
    {
        return $this->belongsTo(Field::class);
    }

    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function players()
    {
        return $this->belongsToMany(User::class, 'game_players', 'game_id', 'player_id')
            ->withPivot('joined_at');
    }
}
